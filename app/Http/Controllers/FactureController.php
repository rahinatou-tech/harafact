<?php
namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\LigneDocument;
use App\Models\Client;
use App\Models\ProduitService;
use App\Models\GroupeTaxe;
use App\Models\Paiement;
use App\Models\JournalAudit;
use App\Models\Devis;
use App\Jobs\EnvoyerFacturePdfJob;
use App\Jobs\EnvoyerFactureWhatsAppJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class FactureController extends Controller
{
    // ── LISTE ─────────────────────────────────────────────────
    public function index(Request $request)
    {
        $eid   = auth()->user()->entreprise_id;
        $query = Facture::with('client')->where('entreprise_id', $eid);

        if ($request->filled('type'))   $query->where('type_facture', $request->type);
        if ($request->filled('statut')) $query->where('statut', $request->statut);
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn($sq) => $sq
                ->where('numero_facture', 'like', "%$q%")
                ->orWhereHas('client', fn($cq) => $cq
                    ->where('nom',            'like', "%$q%")
                    ->orWhere('raison_sociale','like', "%$q%")
                ));
        }

        $factures = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(15)->withQueryString();

        $stats = [
            'total'      => Facture::where('entreprise_id', $eid)->count(),
            'ca_mois'    => Facture::where('entreprise_id', $eid)->whereIn('statut',['paye','partiellement_paye'])->whereMonth('date_emission',now()->month)->sum('montant_paye'),
            'payees'     => Facture::where('entreprise_id', $eid)->where('statut','paye')->count(),
            'impayes'    => Facture::where('entreprise_id', $eid)->sum('montant_du'),
            'en_retard'  => Facture::where('entreprise_id', $eid)->where('statut','en_retard')->count(),
        ];

        return view('factures.index', compact('factures', 'stats'));
    }

    // ── FORMULAIRE CRÉATION ───────────────────────────────────
    public function create(Request $request)
    {
        $eid        = auth()->user()->entreprise_id;
        $clients    = Client::where('entreprise_id', $eid)->where('est_actif', true)->orderBy('nom')->get();
        $produits   = ProduitService::where('entreprise_id', $eid)->where('est_actif', true)->with('groupeTaxe')->orderBy('nom')->get();
        $groupesTaxes = GroupeTaxe::where('entreprise_id', $eid)->where('est_actif', true)->get();
        $type       = $request->get('type', 'simple');

        // Pour avoir/solde : liste des factures parentes possibles
       $factures_parentes = Facture::where('entreprise_id', $eid)
    ->whereIn('type_facture', ['simple', 'proforma'])
    ->whereNotIn('statut', ['annule', 'brouillon'])
    ->with('client')
    ->orderByDesc('date_emission')
    ->get();

        // Si converti depuis un devis
        $devis_origine = null;
        if ($request->filled('devis_id')) {
            $devis_origine = Devis::with(['client', 'lignes.produit', 'lignes.groupeTaxe'])
                ->where('entreprise_id', $eid)
                ->find($request->devis_id);
        }

        // ✅ JSON préparé côté contrôleur pour éviter le bug Blade avec fn()=>
        $produitsJson = $produits->map(fn($p) => [
            'id'        => $p->id,
            'nom'       => $p->nom,
            'prix'      => $p->prix_unitaire_ht,
            'unite'     => $p->unite,
            'taux'      => $p->groupeTaxe?->taux_total ?? 0,
            'groupe_id' => $p->groupe_taxes_id,
            'reference' => $p->reference,
        ]);

        return view('factures.create', compact(
            'clients', 'produits', 'produitsJson', 'groupesTaxes',
            'type', 'factures_parentes', 'devis_origine'
        ));
    }






    // ── ENREGISTREMENT ────────────────────────────────────────
    public function store(Request $request)
    {
        $eid  = auth()->user()->entreprise_id;
        $type = $request->type_facture;

        // Validation de base
        $rules = [
            'type_facture'  => 'required|in:simple,proforma,avoir,acompte,solde,recurrente',
            'client_id'     => 'required|exists:clients,id',
            'date_emission' => 'required|date',
            'date_echeance' => 'nullable|date|after_or_equal:date_emission',
            'devise'        => 'required|string|size:3',
            'notes'         => 'nullable|string|max:1000',
            'notes_internes'=> 'nullable|string|max:1000',
        ];

        // Règles selon le type
        if (in_array($type, ['simple', 'proforma', 'acompte', 'recurrente'])) {
            $rules['lignes']                     = 'required|array|min:1';
            $rules['lignes.*.designation']       = 'required|string|max:500';
            $rules['lignes.*.quantite']          = 'required|numeric|min:0.001';
            $rules['lignes.*.prix_unitaire_ht']  = 'required|numeric|min:0';
            $rules['lignes.*.groupe_taxes_id']   = 'nullable|exists:groupes_taxes,id';
        }

        if ($type === 'avoir') {
            $rules['facture_parente_id'] = 'required|exists:factures,id';
            $rules['motif_avoir']        = 'required|string|max:500';
            $rules['lignes']             = 'required|array|min:1';
        }

        if ($type === 'solde') {
            $rules['facture_parente_id'] = 'required|exists:factures,id';
        }

        if ($type === 'acompte') {
            $rules['type_acompte']       = 'required|in:pourcentage,montant_fixe';
            $rules['valeur_acompte']     = 'required|numeric|min:0.01';
            $rules['facture_parente_id'] = 'required|exists:factures,id';
        }

         if ($type === 'recurrente') {
            $rules['frequence']      = 'required|in:quotidienne,hebdomadaire,mensuelle,trimestrielle,semestrielle,annuelle';
            $rules['date_debut']     = 'required|date';
            $rules['nom_recurrence'] = 'nullable|string|max:255';   // ← LIGNE AJOUTÉE
        }

        $data = $request->validate($rules);

        DB::transaction(function () use ($request, $data, $eid, $type) {

            // 1. Calculer les totaux
            [$totalHT, $totalRemises, $totalTaxes, $detailTaxes, $totalTTC, $lignesData]
                = $this->calculerTotaux($request->lignes ?? [], $eid, $type, $request);

            // 2. Cas spécial SOLDE : calculer automatiquement
            if ($type === 'solde' && $request->filled('facture_parente_id')) {
                [$totalHT, $totalRemises, $totalTaxes, $detailTaxes, $totalTTC]
                    = $this->calculerSolde($request->facture_parente_id);
            }

            // 3. Numéro de facture (via séquences JSON de l'entreprise)
            $entreprise = auth()->user()->entreprise;
            $numero     = $entreprise->prochainNumero($type);

            // 4. paiement_auto = toujours FALSE (paiement seulement à l'envoi par le Comptable)
            $paiementAuto = false;

            // 5. Snapshot client
            $client         = Client::find($request->client_id);
            $snapshotClient = [
                'id'             => $client->id,
                'nom_affichage'  => $client->nom_affichage,
                'email'          => $client->email,
                'telephone'      => $client->telephone,
                'adresse'        => $client->adresse_ligne1,
                'ville'          => $client->ville,
                'pays'           => $client->pays,
                'numero_fiscal'  => $client->numero_fiscal,
                'numero_tva'     => $client->numero_tva,
            ];

            // 6. Créer la facture
            $facture = Facture::create([
                'entreprise_id'     => $eid,
                'type_facture'      => $type,
                'numero_facture'    => $numero,
                'statut'            => 'en_attente_validation',  // Attente de validation du Comptable
                'client_id'         => $request->client_id,
                'snapshot_client'   => $snapshotClient,
                'date_emission'     => $request->date_emission,
                'date_echeance'     => $request->date_echeance,
                'devise'            => $request->devise ?? 'XOF',
                'total_ht'          => $totalHT,
                'total_remises_ht'  => $totalRemises,
                'total_taxes'       => $totalTaxes,
                'detail_taxes'      => $detailTaxes,
                'total_ttc'         => $totalTTC,
                'montant_paye'      => $paiementAuto ? $totalTTC : 0,
                'montant_du'        => $paiementAuto ? 0 : $totalTTC,
                'facture_parente_id'=> $request->facture_parente_id,
                'devis_origine_id'  => $request->devis_origine_id,
                'type_acompte'      => $request->type_acompte,
                'valeur_acompte'    => $request->valeur_acompte,
                'motif_avoir'       => $request->motif_avoir,
                'paiement_auto'     => $paiementAuto,
                'notes'             => $request->notes,
                'notes_internes'    => $request->notes_internes,
                'cree_par'          => auth()->id(),
                'token_public'      => \Illuminate\Support\Str::uuid(),
            ]);

            // 7. Créer les lignes
            foreach ($lignesData as $i => $ligne) {
                LigneDocument::create([
                    'type_document'    => 'facture',
                    'document_id'      => $facture->id,
                    'ordre_affichage'  => $i + 1,
                    'produit_id'       => $ligne['produit_id'] ?? null,
                    'designation'      => $ligne['designation'],
                    'quantite'         => $ligne['quantite'],
                    'unite'            => $ligne['unite'] ?? null,
                    'prix_unitaire_ht' => $ligne['prix_unitaire_ht'],
                    'groupe_taxes_id'  => $ligne['groupe_taxes_id'] ?? null,
                    'snapshot_taxes'   => $ligne['snapshot_taxes'] ?? null,
                    'type_remise'      => $ligne['type_remise'] ?? null,
                    'valeur_remise'    => $ligne['valeur_remise'] ?? 0,
                    'total_ht_ligne'   => $ligne['total_ht_ligne'],
                    'detail_taxes_ligne'=> $ligne['detail_taxes_ligne'] ?? [],
                    'total_taxes_ligne' => $ligne['total_taxes_ligne'],
                    'total_ttc_ligne'  => $ligne['total_ttc_ligne'],
                ]);
            }


            


            // 7b. Créer la config récurrence si type récurrente
            if ($type === 'recurrente') {
                $config = \App\Models\ConfigRecurrence::create([
                    'entreprise_id'           => $eid,
                    'client_id'               => $request->client_id,
                    'nom'                     => $request->nom_recurrence,
                    'frequence'               => $data['frequence'],
                    'intervalle'              => 1,
                    'date_debut'              => $data['date_debut'],
                    'date_fin'                => $request->date_fin ?? null,
                    'max_occurrences'         => $request->max_occurrences ?? null,
                    'jour_du_mois'            => $request->jour_du_mois ?? null,
                    'envoi_auto'              => true,
                    'devise'                  => $request->devise ?? 'XOF',
                    'statut'                  => 'actif',
                    'prochaine_generation_le' => $this->calculerProchaineDate($data['frequence'], $data['date_debut']),
                    'total_genere'            => 1,
                    'cree_par'                => auth()->id(),
                ]);

                $facture->update(['config_recurrence_id' => $config->id]);
            }

            
            

            // 8. Paiement automatique pour types non suivis
            if ($paiementAuto && $totalTTC > 0) {
                Paiement::create([
                    'entreprise_id'             => $eid,
                    'facture_id'                => $facture->id,
                    'date_paiement'             => now()->toDateString(),
                    'montant'                   => $totalTTC,
                    'devise'                    => $request->devise ?? 'XOF',
                    'montant_devise_principale' => $totalTTC,
                    'type_paiement'             => 'virement',
                    'libelle_paiement'          => 'Paiement automatique à émission',
                    'est_auto'                  => true,
                    'saisie_par'                => null,
                ]);
            }

            // 9. Marquer le devis comme converti
            if ($request->filled('devis_origine_id')) {
                Devis::find($request->devis_origine_id)?->update([
                    'statut'              => 'converti',
                    'converti_en'         => $type,
                    'facture_convertie_id'=> $facture->id,
                ]);
            }

            // 10. Audit
            JournalAudit::enregistrer('facture', $facture->id, 'creation', [], ['numero' => $facture->numero_facture, 'type' => $type, 'total_ttc' => $totalTTC]);

            session(['last_facture_id' => $facture->id]);
        });

        return redirect()
            ->route('factures.show', session('last_facture_id'))
            ->with('success', 'Facture créée et émise avec succès !');
    }










private function calculerProchaineDate(string $frequence, string $dateDebut): string
{
    $date = \Carbon\Carbon::parse($dateDebut);

    return match ($frequence) {
        'quotidienne'   => $date->addDay()->toDateString(),
        'hebdomadaire'  => $date->addWeek()->toDateString(),
        'mensuelle'     => $date->addMonth()->toDateString(),
        'trimestrielle' => $date->addMonths(3)->toDateString(),
        'semestrielle'  => $date->addMonths(6)->toDateString(),
        'annuelle'      => $date->addYear()->toDateString(),
        default         => $date->addMonth()->toDateString(),
    };
}






    // ── FICHE ─────────────────────────────────────────────────
    public function show(Facture $facture)
    {
        $this->autoriser($facture);
        $facture->load(['client', 'lignes.produit', 'lignes.groupeTaxe', 'paiements.saiseur', 'factureParente', 'facturesFilles', 'devisOrigine', 'createur']);

        return view('factures.show', compact('facture'));
    }

    // ── MODIFICATION (statut et date uniquement si émise) ─────
    public function edit(Facture $facture)
    {
        $this->autoriser($facture);

        if ($facture->statut === 'paye') {
            return redirect()->route('factures.show', $facture)->with('error', 'Une facture payée ne peut pas être modifiée.');
        }

        $clients = Client::where('entreprise_id', auth()->user()->entreprise_id)->where('est_actif', true)->get();
        return view('factures.edit', compact('facture', 'clients'));
    }

    public function update(Request $request, Facture $facture)
    {
        $this->autoriser($facture);
        $anciennes = $facture->toArray();

        $data = $request->validate([
            'date_echeance'  => 'nullable|date',
            'statut'         => 'required|in:brouillon,envoye,partiellement_paye,paye,en_retard,annule',
            'notes'          => 'nullable|string|max:1000',
            'notes_internes' => 'nullable|string|max:1000',
        ]);

        $facture->update($data);
        JournalAudit::enregistrer('facture', $facture->id, 'modification', $anciennes, $data);

        return redirect()->route('factures.show', $facture)->with('success', 'Facture mise à jour.');
    }

    public function destroy(Facture $facture)
    {
        $this->autoriser($facture);

        if ($facture->statut === 'paye') {
            return back()->with('error', 'Impossible de supprimer une facture payée.');
        }

        $numero = $facture->numero_facture;
        $facture->lignes()->delete();
        $facture->delete();

        JournalAudit::enregistrer('facture', $facture->id, 'suppression', ['numero' => $numero]);

        return redirect()->route('factures.index')->with('success', "Facture $numero supprimée.");
    }

    // ── DUPLICATION ───────────────────────────────────────────
    public function dupliquer(Facture $facture)
    {
        $this->autoriser($facture);
        $facture->load('lignes');

        DB::transaction(function () use ($facture) {
            $entreprise = auth()->user()->entreprise;
            $nouveau = $facture->replicate(['numero_facture', 'statut', 'montant_paye', 'montant_du', 'envoye_le', 'snapshot_client', 'token_public']);            $nouveau->numero_facture = $entreprise->prochainNumero($facture->type_facture);
            $nouveau->statut         = 'brouillon';
            $nouveau->montant_paye   = 0;
            $nouveau->montant_du     = $facture->total_ttc;
            $nouveau->date_emission  = now()->toDateString();
            $nouveau->paiement_auto  = $facture->paiement_auto;
            $nouveau->cree_par       = auth()->id();
            $nouveau->token_public = \Illuminate\Support\Str::uuid();
            $nouveau->save();

            foreach ($facture->lignes as $ligne) {
                $nl = $ligne->replicate(['document_id']);
                $nl->document_id = $nouveau->id;
                $nl->save();
            }

            session(['last_facture_id' => $nouveau->id]);
        });

        return redirect()
            ->route('factures.show', session('last_facture_id'))
            ->with('success', 'Facture dupliquée. Pensez à vérifier les dates.');
    }




    // ── CALCUL INTERNE ────────────────────────────────────────
    private function calculerTotaux(?array $lignesRequest, int $eid, string $type, Request $request): array
    {
        $lignesData  = [];
        $totalHT     = 0;
        $totalRemises= 0;
        $totalTaxes  = 0;
        $detailTaxes = [];

        if (!$lignesRequest) return [$totalHT, $totalRemises, $totalTaxes, $detailTaxes, 0, []];

        foreach ($lignesRequest as $i => $ligne) {
            $qte  = (float) ($ligne['quantite'] ?? 1);
            $pu   = (float) ($ligne['prix_unitaire_ht'] ?? 0);
            $remV = (float) ($ligne['valeur_remise'] ?? 0);
            $remT = $ligne['type_remise'] ?? null;

            $remMontant = $remT === 'pourcentage' ? ($qte * $pu * $remV / 100) : ($remT === 'montant_fixe' ? $remV : 0);
            $htLigne    = round(($qte * $pu) - $remMontant, 2);
            $totalHT    += $qte * $pu;
            $totalRemises += $remMontant;

            $detailTaxeLigne = [];
            $taxesLigne      = 0;
            $snapshotTaxes   = null;

            if (!empty($ligne['groupe_taxes_id'])) {
                $groupe = GroupeTaxe::find($ligne['groupe_taxes_id']);
                if ($groupe) {
                    $calcul          = $groupe->calculer($htLigne);
                    $taxesLigne      = $calcul['total_taxes'];
                    $detailTaxeLigne = $calcul['detail'];
                    $snapshotTaxes   = $groupe->composantes;
                    $totalTaxes     += $taxesLigne;

                    // Agréger pour detail_taxes facture
                    foreach ($calcul['detail'] as $d) {
                        $key = $d['code'];
                        if (!isset($detailTaxes[$key])) {
                            $detailTaxes[$key] = ['nom'=>$d['nom'],'code'=>$d['code'],'taux'=>$d['taux'],'montant'=>0,'code_comptable'=>$d['code_comptable']??''];
                        }
                        $detailTaxes[$key]['montant'] += $d['montant'];
                    }
                }
            }

            $lignesData[] = [
                'produit_id'        => $ligne['produit_id'] ?? null,
                'designation'       => $ligne['designation'],
                'quantite'          => $qte,
                'unite'             => $ligne['unite'] ?? null,
                'prix_unitaire_ht'  => $pu,
                'groupe_taxes_id'   => $ligne['groupe_taxes_id'] ?? null,
                'snapshot_taxes'    => $snapshotTaxes,
                'type_remise'       => $remT,
                'valeur_remise'     => $remV,
                'total_ht_ligne'    => $htLigne,
                'detail_taxes_ligne'=> $detailTaxeLigne,
                'total_taxes_ligne' => $taxesLigne,
                'total_ttc_ligne'   => round($htLigne + $taxesLigne, 2),
            ];
        }

        $htNet   = round($totalHT - $totalRemises, 2);
        $totalTTC = round($htNet + $totalTaxes, 2);
        $detailArray = array_values($detailTaxes);

        return [$htNet, round($totalRemises, 2), round($totalTaxes, 2), $detailArray, $totalTTC, $lignesData];
    }

    private function calculerSolde(int $factureParenteId): array
    {
        $parente    = Facture::findOrFail($factureParenteId);
        $acomptesPaies = Facture::where('facture_parente_id', $factureParenteId)
            ->where('type_facture', 'acompte')
            ->sum('montant_paye');

        $montantSolde = max(0, $parente->total_ttc - $acomptesPaies);

        return [
            $montantSolde, 0,
            round($montantSolde * 0.19, 2),
            [],
            $montantSolde,
        ];
    }

    // ── API : calcul temps réel ───────────────────────────────
    public function calculerApercu(Request $request)
    {
        $eid   = auth()->user()->entreprise_id;
        $lignes = $request->lignes ?? [];

        [,$totalRemises,$totalTaxes,$detailTaxes,$totalTTC,$lignesCalc]
            = $this->calculerTotaux($lignes, $eid, 'simple', $request);

        $totalHT = collect($lignesCalc)->sum('total_ht_ligne');

        return response()->json([
            'total_ht'     => round($totalHT, 2),
            'total_remises'=> round($totalRemises, 2),
            'total_taxes'  => round($totalTaxes, 2),
            'detail_taxes' => $detailTaxes,
            'total_ttc'    => round($totalTTC, 2),
            'lignes'       => $lignesCalc,
        ]);
    }

    // ── PDF GÉNÉRATION ────────────────────────────────────────────
    public function generatePdf(Facture $facture)
    {
        $this->autoriser($facture);
        $facture->load(['lignes', 'client', 'entreprise']);

        $entreprise = $facture->entreprise;
        $client_snapshot = $facture->snapshot_client ?? [];

        $pdf = PDF::loadView('factures.pdf', compact('facture', 'entreprise', 'client_snapshot'));
        
        $filename = "{$facture->numero_facture}.pdf";
        
        // Télécharger le PDF
        return $pdf->download($filename);
    }

    // ── SOUMETTRE (Employé) ────────────────────────────────────────
    public function soumettre(Request $request, Facture $facture)
    {
        $this->autoriser($facture);

        // Vérifier que c'est un Employé
        if (auth()->user()->role !== 'Employé') {
            return back()->with('error', 'Seul l\'employé peut soumettre les factures.');
        }

        // Vérifier que la facture est en brouillon
        if ($facture->statut_validation !== 'brouillon') {
            return back()->with('error', 'Seules les factures en brouillon peuvent être soumises.');
        }

        try {
            DB::transaction(function () use ($facture) {
                // Mettre à jour la facture
                $facture->update([
                    'statut_validation' => 'en_attente_validation',
                    'soumise_par' => auth()->id(),
                    'soumise_le' => now(),
                ]);

                // Enregistrer dans l'audit
                JournalAudit::enregistrer('facture', $facture->id, 'soumission', [], [
                    'soumise_par' => auth()->user()->prenom,
                    'numero_facture' => $facture->numero_facture,
                ]);
            });

            return back()->with('success', "✅ Facture {$facture->numero_facture} soumise au comptable !");
        } catch (\Exception $e) {
            \Log::error("Erreur soumission facture: " . $e->getMessage());
            return back()->with('error', 'Erreur lors de la soumission: ' . $e->getMessage());
        }
    }

    // ── VALIDER (Comptable) ────────────────────────────────────────
    // ── VALIDER (Comptable) ────────────────────────────────────────────────────
    public function valider(Request $request, Facture $facture)
    {
        $this->autoriser($facture);

        // Vérifier que c'est un Comptable
        if (auth()->user()->role !== 'Comptable') {
            return back()->with('error', 'Seul le comptable peut valider les factures.');
        }

        // Vérifier qu'elle est en attente
        if ($facture->statut !== 'en_attente_validation') {
            return back()->with('error', 'Cette facture n\'est pas en attente de validation.');
        }

        try {
            DB::transaction(function () use ($facture) {

                // Types qui passent à "paye" directement avec paiement auto
                $typesPaye   = ['simple', 'acompte', 'solde', 'recurrente'];
                // Types qui passent à "envoye" sans paiement
                $typesEnvoye = ['proforma', 'avoir'];

                if (in_array($facture->type_facture, $typesPaye)) {

                    // Récurrente → validee (bouton Envoyer apparaît)
                    // Autres → paye directement
                    $nouveauStatut = $facture->type_facture === 'recurrente' ? 'validee' : 'paye';

                    $facture->update([
                        'statut'       => $nouveauStatut,
                        'montant_paye' => $facture->type_facture === 'recurrente' ? 0 : $facture->total_ttc,
                        'montant_du'   => $facture->type_facture === 'recurrente' ? $facture->total_ttc : 0,
                        'valide_par'   => auth()->id(),
                        'valide_le'    => now(),
                    ]);

                    if ($facture->type_facture !== 'recurrente') {
                        Paiement::create([
                            'entreprise_id'             => $facture->entreprise_id,
                            'facture_id'                => $facture->id,
                            'date_paiement'             => now()->toDateString(),
                            'montant'                   => $facture->total_ttc,
                            'devise'                    => $facture->devise,
                            'montant_devise_principale' => $facture->total_ttc,
                            'type_paiement'             => 'autre',
                            'libelle_paiement'          => 'Paiement validé par le comptable',
                            'est_auto'                  => true,
                            'saisie_par'                => auth()->id(),
                        ]);

                        // Valider une facture simple/acompte/solde = la marquer
                        // payée ET l'envoyer immédiatement au client (PDF par
                        // email + WhatsApp si numéro renseigné).
                        if (!$facture->url_pdf) {
                            $chemin = \App\Services\PdfService::genererFacturePdf($facture);
                            $facture->update(['url_pdf' => $chemin]);
                        }
                        if ($facture->client && $facture->client->email) {
                            dispatch(new \App\Jobs\EnvoyerFacturePdfJob($facture, $facture->client->email));
                        }
                        if ($facture->client && $facture->client->telephone) {
                            dispatch(new \App\Jobs\EnvoyerFactureWhatsAppJob($facture, $facture->client->telephone));
                        }
                        $facture->update([
                            'envoye_le'        => now(),
                            'canal_envoi'      => 'email',
                            'envoye_client_le' => now(),
                        ]);
                    }

                } elseif (in_array($facture->type_facture, $typesEnvoye)) {

                    $facture->update([
                        'statut'     => 'envoye',
                        'envoye_le'  => now(),
                        'valide_par' => auth()->id(),
                        'valide_le'  => now(),
                    ]);

                    // Proforma / Avoir : envoi immédiat également au moment
                    // de la validation.
                    if (!$facture->url_pdf) {
                        $chemin = \App\Services\PdfService::genererFacturePdf($facture);
                        $facture->update(['url_pdf' => $chemin]);
                    }
                    if ($facture->client && $facture->client->email) {
                        dispatch(new \App\Jobs\EnvoyerFacturePdfJob($facture, $facture->client->email));
                    }
                    if ($facture->client && $facture->client->telephone) {
                        dispatch(new \App\Jobs\EnvoyerFactureWhatsAppJob($facture, $facture->client->telephone));
                    }
                }

                JournalAudit::enregistrer('facture', $facture->id, 'validation', [], [
                    'valide_par'     => auth()->user()->prenom,
                    'numero_facture' => $facture->numero_facture,
                    'statut'         => $facture->statut,
                ]);
            });

            $messages = [
                'paye'   => '✅ Facture validée et marquée comme payée !',
                'envoye' => '✅ Facture validée et marquée comme envoyée !',
            ];

            return back()->with('success', $messages[$facture->fresh()->statut] ?? '✅ Facture validée !');

        } catch (\Exception $e) {
            \Log::error("Erreur validation facture: " . $e->getMessage());
            return back()->with('error', 'Erreur lors de la validation: ' . $e->getMessage());
        }
    }

    // ── REJETER (Comptable) ────────────────────────────────────────────────────
    public function rejeter(Request $request, Facture $facture)
    {
        $this->autoriser($facture);

        // Vérifier que c'est un Comptable
        if (auth()->user()->role !== 'Comptable') {
            return back()->with('error', 'Seul le comptable peut rejeter les factures.');
        }

        // Vérifier qu'elle est en attente
        if ($facture->statut !== 'en_attente_validation') {
            return back()->with('error', 'Cette facture n\'est pas en attente de validation.');
        }

        $raison = $request->input('motif_rejet');
        if (!$raison) {
            return back()->with('error', 'Veuillez indiquer le motif du rejet.');
        }

        try {
            DB::transaction(function () use ($facture, $raison) {
                $facture->update([
                    'statut' => 'brouillon',
                    'motif_rejet' => $raison,
                ]);
            });

            return back()->with('success', '❌ Facture rejetée.');
        } catch (\Exception $e) {
            \Log::error("Erreur rejet facture: " . $e->getMessage());
            return back()->with('error', 'Erreur lors du rejet: ' . $e->getMessage());
        }
    }

    // ── ENVOI (Comptable) ────────────────────────────────────────────────────
    public function envoyer(Request $request, Facture $facture)
    {
        $this->autoriser($facture);

        // Vérifier que c'est un Comptable
        if (auth()->user()->role !== 'Comptable') {
            return back()->with('error', 'Seul le comptable peut envoyer les factures.');
        }

        // Vérifier que c'est validée
        if ($facture->statut !== 'validee') {
            return back()->with('error', 'Cette facture doit être validée avant envoi.');
        }

        $typeEnvoi = $request->input('type_envoi');
        if (!in_array($typeEnvoi, ['email', 'whatsapp', 'both', 'print'])) {
            return back()->with('error', 'Type d\'envoi invalide.');
        }

        try {
            DB::transaction(function () use ($facture, $typeEnvoi) {
                // 1. Générer et sauvegarder le PDF s'il n'existe pas
                if (!$facture->url_pdf) {
                    $chemin = \App\Services\PdfService::genererFacturePdf($facture);
                    $facture->update(['url_pdf' => $chemin]);
                }

                // 2. Envoyer par Email
                if (in_array($typeEnvoi, ['email', 'both']) && $facture->client->email) {
                    dispatch(new \App\Jobs\EnvoyerFacturePdfJob($facture, $facture->client->email));
                }

                // 3. Envoyer par WhatsApp
                if (in_array($typeEnvoi, ['whatsapp', 'both']) && $facture->client->telephone) {
                    dispatch(new \App\Jobs\EnvoyerFactureWhatsAppJob($facture, $facture->client->telephone));
                }

                // 4. Statut après envoi : "paye" uniquement pour les types à
                // paiement automatique à l'envoi (simple/avoir/solde). Une
                // facture récurrente passe en "envoye" : le paiement est
                // manuel et sera saisi plus tard par le Comptable.
                $paiementAutoEnvoi = in_array($facture->type_facture, ['simple', 'avoir', 'solde']);

                $facture->update([
                    'statut'           => $paiementAutoEnvoi ? 'paye' : 'envoye',
                    'montant_paye'     => $paiementAutoEnvoi ? $facture->total_ttc : $facture->montant_paye,
                    'montant_du'       => $paiementAutoEnvoi ? 0 : $facture->montant_du,
                    'envoye_le'        => now(),
                    'canal_envoi'      => $typeEnvoi,
                    'envoye_client_le' => now(),
                ]);

                // 5. Créer le paiement automatique SEULEMENT pour Simple/Avoir/Solde
                if ($paiementAutoEnvoi) {
                    Paiement::create([
                        'facture_id' => $facture->id,
                        'montant' => $facture->total_ttc,
                        'date_paiement' => now(),
                        'methode' => 'envoi_automatique',
                        'reference' => 'AUTO-' . $facture->numero_facture,
                        'notes' => "Paiement automatique à l'envoi de la facture",
                    ]);
                }

                // 6. Enregistrer dans l'audit (optionnel pour plus tard)
                // JournalAudit::enregistrer(...)
            });

            $estPayee = in_array($facture->type_facture, ['simple', 'avoir', 'solde']);
            $suffixe  = $estPayee ? 'et marquée comme payée' : 'en attente de paiement manuel';

            $messages = [
                'email'    => "📧 Facture envoyée par Email $suffixe",
                'whatsapp' => "💬 Facture envoyée par WhatsApp $suffixe",
                'both'     => "📧💬 Facture envoyée par Email et WhatsApp $suffixe",
                'print'    => "🖨️ Facture prête à être imprimée $suffixe",
            ];

            return back()->with('success', $messages[$typeEnvoi] ?? 'Facture traitée avec succès !');
        } catch (\Exception $e) {
            \Log::error("Erreur envoi facture: " . $e->getMessage());
            return back()->with('error', 'Erreur lors de l\'envoi: ' . $e->getMessage());
        }
    }

    private function autoriser(Facture $facture): void
    {
        if ($facture->entreprise_id !== auth()->user()->entreprise_id) abort(403);
    }

    // ── apiIndex ──────────────────────────────────────────────────────
public function apiIndex(Request $request)
{
    $eid   = auth()->user()->entreprise_id;
    $query = Facture::with('client')->where('entreprise_id', $eid);
 
    if ($request->filled('type'))   $query->where('type_facture', $request->type);
    if ($request->filled('statut')) $query->where('statut', $request->statut);
    if ($request->filled('q')) {
        $q = $request->q;
        $query->where(fn($sq) => $sq
            ->where('numero_facture', 'like', "%$q%")
            ->orWhereHas('client', fn($cq) => $cq
                ->where('nom', 'like', "%$q%")
                ->orWhere('raison_sociale', 'like', "%$q%")
            ));
    }
 
    $factures = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(20)->withQueryString();
 
    $stats = [
        'total'     => Facture::where('entreprise_id', $eid)->count(),
        'ca_mois'   => Facture::where('entreprise_id', $eid)
            ->whereIn('statut', ['paye', 'partiellement_paye'])
            ->whereMonth('date_emission', now()->month)->sum('montant_paye'),
        'impayes'   => Facture::where('entreprise_id', $eid)->sum('montant_du'),
        'en_retard' => Facture::where('entreprise_id', $eid)->where('statut', 'en_retard')->count(),
        'en_attente'=> Facture::where('entreprise_id', $eid)->where('statut', 'en_attente_validation')->count(),
    ];
 
    return response()->json([
        'data'  => $factures->items(),
        'meta'  => [
            'current_page'  => $factures->currentPage(),
            'last_page'     => $factures->lastPage(),
            'per_page'      => $factures->perPage(),
            'total'         => $factures->total(),
        ],
        'stats' => $stats,
    ]);
}
 
// ── apiStore ──────────────────────────────────────────────────────
public function apiStore(Request $request)
{
    // Même logique que store() mais retourne JSON
    // Appeler la méthode store() existante et convertir
    $eid  = auth()->user()->entreprise_id;
    $type = $request->type_facture;
 
    $rules = [
        'type_facture'  => 'required|in:simple,proforma,avoir,acompte,solde,recurrente',
        'client_id'     => 'required|exists:clients,id',
        'date_emission' => 'required|date',
        'date_echeance' => 'nullable|date|after_or_equal:date_emission',
        'devise'        => 'required|string|size:3',
        'notes'         => 'nullable|string|max:1000',
        'lignes'                     => 'required_unless:type_facture,solde|array|min:1',
        'lignes.*.designation'       => 'required_with:lignes|string|max:500',
        'lignes.*.quantite'          => 'required_with:lignes|numeric|min:0.001',
        'lignes.*.prix_unitaire_ht'  => 'required_with:lignes|numeric|min:0',
        'lignes.*.groupe_taxes_id'   => 'nullable|exists:groupes_taxes,id',
    ];
 
    if ($type === 'avoir') {
        $rules['facture_parente_id'] = 'required|exists:factures,id';
        $rules['motif_avoir']        = 'required|string|max:500';
    }
    if (in_array($type, ['solde', 'acompte'])) {
        $rules['facture_parente_id'] = 'required|exists:factures,id';
    }
 
    $request->validate($rules);
 
    $factureId = null;
    \DB::transaction(function () use ($request, $eid, $type, &$factureId) {
        [$totalHT, $totalRemises, $totalTaxes, $detailTaxes, $totalTTC, $lignesData]
            = $this->calculerTotaux($request->lignes ?? [], $eid, $type, $request);
 
        $entreprise = auth()->user()->entreprise;
        $numero     = $entreprise->prochainNumero($type);
        $client     = \App\Models\Client::find($request->client_id);
 
        $facture = \App\Models\Facture::create([
            'entreprise_id'     => $eid,
            'type_facture'      => $type,
            'numero_facture'    => $numero,
            'statut'            => 'en_attente_validation',
            'client_id'         => $request->client_id,
            'snapshot_client'   => ['id' => $client->id, 'nom_affichage' => $client->nom_affichage, 'email' => $client->email, 'telephone' => $client->telephone],
            'date_emission'     => $request->date_emission,
            'date_echeance'     => $request->date_echeance,
            'devise'            => $request->devise ?? 'XOF',
            'total_ht'          => $totalHT,
            'total_remises_ht'  => $totalRemises,
            'total_taxes'       => $totalTaxes,
            'detail_taxes'      => $detailTaxes,
            'total_ttc'         => $totalTTC,
            'montant_paye'      => 0,
            'montant_du'        => $totalTTC,
            'facture_parente_id'=> $request->facture_parente_id,
            'motif_avoir'       => $request->motif_avoir,
            'notes'             => $request->notes,
            'cree_par'          => auth()->id(),
            'token_public'      => \Illuminate\Support\Str::uuid(),
        ]);
 
        foreach ($lignesData as $i => $ligne) {
            \App\Models\LigneDocument::create([
                'type_document'    => 'facture',
                'document_id'      => $facture->id,
                'ordre_affichage'  => $i + 1,
            ] + $ligne);
        }
 
        \App\Models\JournalAudit::enregistrer('facture', $facture->id, 'creation', [], ['numero' => $numero]);
        $factureId = $facture->id;
    });
 
    $facture = \App\Models\Facture::with(['client', 'lignes'])->find($factureId);
    return response()->json($facture, 201);
}
 
// ── apiShow ───────────────────────────────────────────────────────
public function apiShow(int $id)
{
    $facture = Facture::with(['client', 'lignes.groupeTaxe', 'paiements.saiseur', 'createur', 'factureParente'])
        ->where('entreprise_id', auth()->user()->entreprise_id)
        ->findOrFail($id);
 
    return response()->json($facture);
}
 
// ── apiUpdate ─────────────────────────────────────────────────────
public function apiUpdate(Request $request, int $id)
{
    $facture = Facture::where('entreprise_id', auth()->user()->entreprise_id)->findOrFail($id);
 
    $data = $request->validate([
        'date_echeance'  => 'nullable|date',
        'notes'          => 'nullable|string|max:1000',
        'notes_internes' => 'nullable|string|max:1000',
    ]);
 
    $facture->update($data);
    return response()->json($facture->fresh());
}
 
// ── apiDestroy ────────────────────────────────────────────────────
public function apiDestroy(int $id)
{
    $facture = Facture::where('entreprise_id', auth()->user()->entreprise_id)->findOrFail($id);
 
    if ($facture->statut === 'paye') {
        return response()->json(['message' => 'Impossible de supprimer une facture payée.'], 422);
    }
 
    $facture->lignes()->delete();
    $facture->delete();
    return response()->json(['message' => 'Facture supprimée.']);
}
 
// ── apiSoumettre ──────────────────────────────────────────────────
public function apiSoumettre(Request $request, int $id)
{
    $facture = Facture::where('entreprise_id', auth()->user()->entreprise_id)->findOrFail($id);
 
    if ($facture->statut !== 'en_attente_validation' && $facture->statut !== 'brouillon') {
        return response()->json(['message' => 'Cette facture ne peut pas être soumise.'], 422);
    }
 
    $facture->update(['statut' => 'en_attente_validation']);
    \App\Models\JournalAudit::enregistrer('facture', $facture->id, 'soumission', []);
 
    return response()->json(['message' => "Facture {$facture->numero_facture} soumise.", 'facture' => $facture->fresh()]);
}
 
// ── apiValider ────────────────────────────────────────────────────
public function apiValider(Request $request, int $id)
{
    if (auth()->user()->role !== 'Comptable') {
        return response()->json(['message' => 'Accès refusé.'], 403);
    }
 
    $facture = Facture::with('client')->where('entreprise_id', auth()->user()->entreprise_id)->findOrFail($id);
 
    if ($facture->statut !== 'en_attente_validation') {
        return response()->json(['message' => 'Cette facture n\'est pas en attente de validation.'], 422);
    }
 
    \DB::transaction(function () use ($facture) {
        $typesPaye   = ['simple', 'acompte', 'solde'];
        $typesEnvoye = ['proforma', 'avoir'];
 
        if (in_array($facture->type_facture, $typesPaye)) {
            $facture->update([
                'statut'       => 'paye',
                'montant_paye' => $facture->total_ttc,
                'montant_du'   => 0,
                'valide_par'   => auth()->id(),
                'valide_le'    => now(),
                'envoye_le'    => now(),
            ]);
            \App\Models\Paiement::create([
                'entreprise_id'             => $facture->entreprise_id,
                'facture_id'                => $facture->id,
                'date_paiement'             => now()->toDateString(),
                'montant'                   => $facture->total_ttc,
                'devise'                    => $facture->devise,
                'montant_devise_principale' => $facture->total_ttc,
                'type_paiement'             => 'autre',
                'libelle_paiement'          => 'Validé par comptable',
                'est_auto'                  => true,
                'saisie_par'                => auth()->id(),
            ]);

            // Valider une facture simple/acompte/solde = la marquer payée ET
            // l'envoyer au client en une seule action (PDF par mail, +
            // WhatsApp si un numéro est renseigné).
            $this->envoyerFactureAuClient($facture);
        } elseif ($facture->type_facture === 'recurrente') {
            $facture->update(['statut' => 'validee', 'valide_par' => auth()->id(), 'valide_le' => now()]);
        } elseif (in_array($facture->type_facture, $typesEnvoye)) {
            $facture->update(['statut' => 'envoye', 'envoye_le' => now(), 'valide_par' => auth()->id()]);
            $this->envoyerFactureAuClient($facture);
        }
    });
 
    return response()->json(['message' => 'Facture validée et envoyée au client.', 'facture' => $facture->fresh()]);
}
 
// ── apiRejeter ────────────────────────────────────────────────────
public function apiRejeter(Request $request, int $id)
{
    if (auth()->user()->role !== 'Comptable') {
        return response()->json(['message' => 'Accès refusé.'], 403);
    }
 
    $request->validate(['motif_rejet' => 'required|string|max:500']);
 
    $facture = Facture::where('entreprise_id', auth()->user()->entreprise_id)->findOrFail($id);
 
    $facture->update(['statut' => 'brouillon', 'motif_rejet' => $request->motif_rejet]);
 
    return response()->json(['message' => 'Facture rejetée.', 'facture' => $facture->fresh()]);
}
 
// ── apiEnvoyer ────────────────────────────────────────────────────
public function apiEnvoyer(Request $request, int $id)
{
    if (auth()->user()->role !== 'Comptable') {
        return response()->json(['message' => 'Accès refusé.'], 403);
    }
 
    $request->validate(['type_envoi' => 'required|in:email,whatsapp,both,print']);
 
    $facture = Facture::with('client')->where('entreprise_id', auth()->user()->entreprise_id)->findOrFail($id);
 
    if ($facture->statut !== 'validee') {
        return response()->json(['message' => 'La facture doit être validée avant envoi.'], 422);
    }
 
    \DB::transaction(function () use ($facture, $request) {
        $typeEnvoi = $request->type_envoi;

        // Génère le PDF si besoin puis dispatch email/WhatsApp
        // (logique centralisée, identique à apiValider() et à la version web).
        if (in_array($typeEnvoi, ['email', 'whatsapp', 'both'])) {
            $this->envoyerFactureAuClient($facture);
        }

        // Statut après envoi : "paye" uniquement pour simple/avoir/solde
        // (paiement automatique à l'envoi). Une facture récurrente passe en
        // "envoye" : le paiement reste manuel, à saisir plus tard par le
        // Comptable — elle ne doit pas apparaître comme déjà payée.
        $paiementAutoEnvoi = in_array($facture->type_facture, ['simple', 'avoir', 'solde']);

        $facture->update([
            'statut'           => $paiementAutoEnvoi ? 'paye' : 'envoye',
            'montant_paye'     => $paiementAutoEnvoi ? $facture->total_ttc : $facture->montant_paye,
            'montant_du'       => $paiementAutoEnvoi ? 0 : $facture->montant_du,
            'envoye_le'        => now(),
            'canal_envoi'      => $typeEnvoi,
            'envoye_client_le' => now(),
        ]);

        if ($paiementAutoEnvoi) {
            Paiement::create([
                'entreprise_id'             => $facture->entreprise_id,
                'facture_id'                => $facture->id,
                'date_paiement'             => now()->toDateString(),
                'montant'                   => $facture->total_ttc,
                'devise'                    => $facture->devise,
                'montant_devise_principale' => $facture->total_ttc,
                'type_paiement'             => 'autre',
                'libelle_paiement'          => "Paiement automatique à l'envoi de la facture",
                'est_auto'                  => true,
                'saisie_par'                => auth()->id(),
            ]);
        }
    });

    $estPayee = in_array($facture->type_facture, ['simple', 'avoir', 'solde']);
    $message  = $estPayee
        ? 'Facture envoyée et marquée comme payée.'
        : 'Facture envoyée. En attente de paiement manuel.';

    return response()->json(['message' => $message, 'facture' => $facture->fresh()]);
}
 
// ── apiDupliquer ──────────────────────────────────────────────────
public function apiDupliquer(int $id)
{
    $facture = Facture::with('lignes')->where('entreprise_id', auth()->user()->entreprise_id)->findOrFail($id);
 
    $nouveauId = null;
    \DB::transaction(function () use ($facture, &$nouveauId) {
        $entreprise = auth()->user()->entreprise;
        $nouveau = $facture->replicate(['numero_facture', 'statut', 'montant_paye', 'montant_du', 'envoye_le', 'snapshot_client', 'token_public']);
        $nouveau->numero_facture = $entreprise->prochainNumero($facture->type_facture);
        $nouveau->statut         = 'en_attente_validation';
        $nouveau->montant_paye   = 0;
        $nouveau->montant_du     = $facture->total_ttc;
        $nouveau->date_emission  = now()->toDateString();
        $nouveau->cree_par       = auth()->id();
        $nouveau->token_public   = \Illuminate\Support\Str::uuid();
        $nouveau->save();
 
        foreach ($facture->lignes as $ligne) {
            $nl = $ligne->replicate(['document_id']);
            $nl->document_id = $nouveau->id;
            $nl->save();
        }
        $nouveauId = $nouveau->id;
    });
 
    return response()->json(Facture::with('lignes')->find($nouveauId), 201);
}
 
// ── apiPdfUrl ─────────────────────────────────────────────────────
public function apiPdfUrl(int $id)
{
    $facture = Facture::where('entreprise_id', auth()->user()->entreprise_id)->findOrFail($id);
    $url = route('factures.pdf.public', $facture->token_public);
    return response()->json(['url' => $url, 'token' => $facture->token_public]);
}
 
// ── apiCalculerApercu ─────────────────────────────────────────────
public function apiCalculerApercu(Request $request)
{
    // Même logique que calculerApercu() existant
    $eid   = auth()->user()->entreprise_id;
    $lignes = $request->lignes ?? [];
    [, $totalRemises, $totalTaxes, $detailTaxes, $totalTTC, $lignesCalc]
        = $this->calculerTotaux($lignes, $eid, 'simple', $request);
    $totalHT = collect($lignesCalc)->sum('total_ht_ligne');
 
    return response()->json([
        'total_ht'      => round($totalHT, 2),
        'total_remises' => round($totalRemises, 2),
        'total_taxes'   => round($totalTaxes, 2),
        'detail_taxes'  => $detailTaxes,
        'total_ttc'     => round($totalTTC, 2),
        'lignes'        => $lignesCalc,
    ]);
}

// ── envoyerFactureAuClient ──────────────────────────────────────────
// Génère le PDF si besoin puis dispatch l'envoi email / WhatsApp au client.
// Centralise ce que faisait déjà apiEnvoyer(), pour pouvoir le réutiliser
// depuis apiValider() (validation simple/acompte/solde = envoi immédiat).
private function envoyerFactureAuClient(Facture $facture): void
{
    $facture->loadMissing('client');

    if (!$facture->client) {
        return;
    }

    // S'assurer que le PDF existe avant l'envoi (sinon le job part avec
    // url_pdf = null et l'email arrive sans pièce jointe).
    if (!$facture->url_pdf) {
        $chemin = \App\Services\PdfService::genererFacturePdf($facture);
        $facture->update(['url_pdf' => $chemin]);
    }

    if ($facture->client->email) {
        dispatch(new EnvoyerFacturePdfJob($facture, $facture->client->email));
    }
    if ($facture->client->telephone) {
        dispatch(new EnvoyerFactureWhatsAppJob($facture, $facture->client->telephone));
    }
}
}