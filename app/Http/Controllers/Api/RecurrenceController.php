<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConfigRecurrence;
use App\Models\Facture;
use App\Models\LigneDocument;
use App\Models\Client;
use App\Models\GroupeTaxe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecurrenceController extends Controller
{
    public function index(Request $request)
    {
        $eid = auth()->user()->entreprise_id;

        $query = ConfigRecurrence::with('client')
            ->where('entreprise_id', $eid);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $recurrences = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(20);

        return response()->json([
            'data' => $recurrences->items(),
            'meta' => [
                'current_page' => $recurrences->currentPage(),
                'last_page'    => $recurrences->lastPage(),
                'total'        => $recurrences->total(),
            ],
            'stats' => [
                'actives'   => ConfigRecurrence::where('entreprise_id', $eid)->where('statut', 'actif')->count(),
                'pausees'   => ConfigRecurrence::where('entreprise_id', $eid)->where('statut', 'pause')->count(),
                'terminees' => ConfigRecurrence::where('entreprise_id', $eid)->where('statut', 'termine')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $eid = auth()->user()->entreprise_id;

        $data = $request->validate([
            'client_id'        => 'required|exists:clients,id',
            'nom'              => 'required|string|max:200',
            'frequence'        => 'required|in:quotidienne,hebdomadaire,mensuelle,trimestrielle,semestrielle,annuelle',
            'intervalle'       => 'integer|min:1|max:12',
            'date_debut'       => 'required|date',
            'date_fin'         => 'nullable|date|after:date_debut',
            'max_occurrences'  => 'nullable|integer|min:1',
            'jour_du_mois'     => 'nullable|integer|min:1|max:28',
            'envoi_auto'       => 'boolean',
            'devise'           => 'required|string|size:3',
            // Lignes modèles : nécessaires pour générer la 1ère facture
            // immédiatement (sinon le cron GenererFacturesRecurrentes
            // ne trouve aucune facture-modèle à dupliquer et ignore la
            // config silencieusement, voir genererFacture()).
            'lignes_modele'                     => 'required|array|min:1',
            'lignes_modele.*.designation'       => 'required|string|max:500',
            'lignes_modele.*.quantite'          => 'required|numeric|min:0.001',
            'lignes_modele.*.prix_unitaire_ht'  => 'required|numeric|min:0',
            'lignes_modele.*.groupe_taxes_id'   => 'nullable|exists:groupes_taxes,id',
        ]);

        $config = DB::transaction(function () use ($data, $eid) {

            $config = ConfigRecurrence::create([
                'entreprise_id'          => $eid,
                'client_id'              => $data['client_id'],
                'nom'                    => $data['nom'],
                'frequence'              => $data['frequence'],
                'intervalle'             => $data['intervalle'] ?? 1,
                'date_debut'             => $data['date_debut'],
                'date_fin'               => $data['date_fin'] ?? null,
                'max_occurrences'        => $data['max_occurrences'] ?? null,
                'jour_du_mois'           => $data['jour_du_mois'] ?? null,
                'envoi_auto'             => $data['envoi_auto'] ?? false,
                'devise'                 => $data['devise'],
                'statut'                 => 'actif',
                'prochaine_generation_le'=> $data['date_debut'],
                'total_genere'           => 1,
                'cree_par'               => auth()->id(),
            ]);

            // Créer la 1ère facture (modèle) immédiatement, exactement comme
            // le fait FactureController::store() côté web pour le type
            // 'recurrente'. Sans ça, le cron ne trouve jamais de modèle.
            [$totalHT, $totalRemises, $totalTaxes, $detailTaxes, $totalTTC, $lignesData]
                = $this->calculerTotaux($data['lignes_modele'], $eid);

            $entreprise = auth()->user()->entreprise;
            $numero     = $entreprise->prochainNumero('recurrente');
            $client     = Client::find($data['client_id']);

            $facture = Facture::create([
                'entreprise_id'        => $eid,
                'type_facture'         => 'recurrente',
                'numero_facture'       => $numero,
                'statut'               => 'en_attente_validation',
                'client_id'            => $data['client_id'],
                'snapshot_client'      => [
                    'id'             => $client->id,
                    'nom_affichage'  => $client->nom_affichage,
                    'email'          => $client->email,
                    'telephone'      => $client->telephone,
                    'adresse'        => $client->adresse_ligne1,
                    'ville'          => $client->ville,
                    'pays'           => $client->pays,
                    'numero_fiscal'  => $client->numero_fiscal,
                    'numero_tva'     => $client->numero_tva,
                ],
                'date_emission'        => $data['date_debut'],
                'date_echeance'        => \Carbon\Carbon::parse($data['date_debut'])->addDays(30)->toDateString(),
                'devise'               => $data['devise'],
                'total_ht'             => $totalHT,
                'total_remises_ht'     => $totalRemises,
                'total_taxes'          => $totalTaxes,
                'detail_taxes'         => $detailTaxes,
                'total_ttc'            => $totalTTC,
                'montant_paye'         => 0,
                'montant_du'           => $totalTTC,
                'config_recurrence_id' => $config->id,
                'paiement_auto'        => false,
                'cree_par'             => auth()->id(),
                'token_public'         => \Illuminate\Support\Str::uuid(),
            ]);

            foreach ($lignesData as $i => $ligne) {
                LigneDocument::create([
                    'type_document'      => 'facture',
                    'document_id'        => $facture->id,
                    'ordre_affichage'    => $i + 1,
                    'produit_id'         => $ligne['produit_id'] ?? null,
                    'designation'        => $ligne['designation'],
                    'quantite'           => $ligne['quantite'],
                    'unite'              => $ligne['unite'] ?? null,
                    'prix_unitaire_ht'   => $ligne['prix_unitaire_ht'],
                    'groupe_taxes_id'    => $ligne['groupe_taxes_id'] ?? null,
                    'snapshot_taxes'     => $ligne['snapshot_taxes'] ?? null,
                    'type_remise'        => $ligne['type_remise'] ?? null,
                    'valeur_remise'      => $ligne['valeur_remise'] ?? 0,
                    'total_ht_ligne'     => $ligne['total_ht_ligne'],
                    'detail_taxes_ligne' => $ligne['detail_taxes_ligne'] ?? [],
                    'total_taxes_ligne'  => $ligne['total_taxes_ligne'],
                    'total_ttc_ligne'    => $ligne['total_ttc_ligne'],
                ]);
            }

            // La prochaine génération (cron) doit viser l'échéance suivante,
            // pas date_debut à nouveau (sinon doublon le jour même).
            $config->update([
                'prochaine_generation_le' => $config->calculerProchaineDate(),
            ]);

            return $config;
        });

        return response()->json($config->load('client'), 201);
    }

    // ── Reprend la même logique de calcul que FactureController, pour
    //    générer les totaux/taxes de la facture modèle créée à l'enregistrement
    //    de la récurrence (lignes_modele envoyées par le mobile).
    private function calculerTotaux(array $lignesRequest, int $eid): array
    {
        $lignesData   = [];
        $totalHT      = 0;
        $totalRemises = 0;
        $totalTaxes   = 0;
        $detailTaxes  = [];

        foreach ($lignesRequest as $ligne) {
            $qte  = (float) ($ligne['quantite'] ?? 1);
            $pu   = (float) ($ligne['prix_unitaire_ht'] ?? 0);
            $remV = (float) ($ligne['valeur_remise'] ?? 0);
            $remT = $ligne['type_remise'] ?? null;

            $remMontant   = $remT === 'pourcentage' ? ($qte * $pu * $remV / 100) : ($remT === 'montant_fixe' ? $remV : 0);
            $htLigne      = round(($qte * $pu) - $remMontant, 2);
            $totalHT     += $qte * $pu;
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

                    foreach ($calcul['detail'] as $d) {
                        $key = $d['code'];
                        if (!isset($detailTaxes[$key])) {
                            $detailTaxes[$key] = ['nom' => $d['nom'], 'code' => $d['code'], 'taux' => $d['taux'], 'montant' => 0, 'code_comptable' => $d['code_comptable'] ?? ''];
                        }
                        $detailTaxes[$key]['montant'] += $d['montant'];
                    }
                }
            }

            $lignesData[] = [
                'produit_id'         => $ligne['produit_id'] ?? null,
                'designation'        => $ligne['designation'],
                'quantite'           => $qte,
                'unite'              => $ligne['unite'] ?? null,
                'prix_unitaire_ht'   => $pu,
                'groupe_taxes_id'    => $ligne['groupe_taxes_id'] ?? null,
                'snapshot_taxes'     => $snapshotTaxes,
                'type_remise'        => $remT,
                'valeur_remise'      => $remV,
                'total_ht_ligne'     => $htLigne,
                'detail_taxes_ligne' => $detailTaxeLigne,
                'total_taxes_ligne'  => $taxesLigne,
                'total_ttc_ligne'    => round($htLigne + $taxesLigne, 2),
            ];
        }

        $htNet       = round($totalHT - $totalRemises, 2);
        $totalTTC    = round($htNet + $totalTaxes, 2);
        $detailArray = array_values($detailTaxes);

        return [$htNet, round($totalRemises, 2), round($totalTaxes, 2), $detailArray, $totalTTC, $lignesData];
    }

    public function show(int $id)
    {
        $config = ConfigRecurrence::with('client')
            ->where('entreprise_id', auth()->user()->entreprise_id)
            ->findOrFail($id);

        return response()->json($config);
    }

    public function update(Request $request, int $id)
    {
        $config = ConfigRecurrence::where('entreprise_id', auth()->user()->entreprise_id)->findOrFail($id);

        $data = $request->validate([
            'nom'             => 'string|max:200',
            'date_fin'        => 'nullable|date',
            'max_occurrences' => 'nullable|integer|min:1',
            'envoi_auto'      => 'boolean',
            'jour_du_mois'    => 'nullable|integer|min:1|max:28',
        ]);

        $config->update($data);
        return response()->json($config->fresh()->load('client'));
    }

    public function destroy(int $id)
    {
        $config = ConfigRecurrence::where('entreprise_id', auth()->user()->entreprise_id)->findOrFail($id);

        if ($config->factures()->count() > 0) {
            return response()->json(['message' => 'Impossible de supprimer une récurrence ayant des factures générées.'], 422);
        }

        $config->delete();
        return response()->json(['message' => 'Récurrence supprimée.']);
    }

    public function pause(int $id)
    {
        $config = ConfigRecurrence::where('entreprise_id', auth()->user()->entreprise_id)->findOrFail($id);
        $config->update(['statut' => 'pause']);
        return response()->json(['message' => 'Récurrence mise en pause.', 'config' => $config->fresh()]);
    }

    public function reprendre(int $id)
    {
        $config = ConfigRecurrence::where('entreprise_id', auth()->user()->entreprise_id)->findOrFail($id);
        $config->update(['statut' => 'actif']);
        return response()->json(['message' => 'Récurrence relancée.', 'config' => $config->fresh()]);
    }

    public function factures(int $id)
    {
        $config = ConfigRecurrence::where('entreprise_id', auth()->user()->entreprise_id)->findOrFail($id);

        $factures = Facture::with('client')
            ->where('config_recurrence_id', $config->id)
            ->orderByDesc('date_emission')
            ->orderByDesc('id')
            ->get();

        return response()->json($factures);
    }
}