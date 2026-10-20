<?php
namespace App\Http\Controllers;

use App\Models\Devis;
use App\Models\Facture;
use App\Models\LigneDocument;
use App\Models\JournalAudit;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DevisController extends Controller
{
    // ══════════════════════════════════════════════════════
    //  MÉTHODES WEB
    // ══════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $eid   = auth()->user()->entreprise_id;
        $query = Devis::with('client')->where('entreprise_id', $eid);

        if ($request->filled('statut')) $query->where('statut', $request->statut);
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn($sq) => $sq
                ->where('numero_devis', 'like', "%$q%")
                ->orWhereHas('client', fn($cq) => $cq
                    ->where('nom', 'like', "%$q%")
                    ->orWhere('raison_sociale', 'like', "%$q%")
                ));
        }

        $devis = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $stats = [
            'total'    => Devis::where('entreprise_id', $eid)->count(),
            'envoyes'  => Devis::where('entreprise_id', $eid)->where('statut', 'envoye')->count(),
            'acceptes' => Devis::where('entreprise_id', $eid)->where('statut', 'accepte')->count(),
            'convertis'=> Devis::where('entreprise_id', $eid)->where('statut', 'converti')->count(),
        ];

        return view('devis.index', compact('devis', 'stats'));
    }

    public function create()
    {
        $eid     = auth()->user()->entreprise_id;
        $clients = Client::where('entreprise_id', $eid)->where('est_actif', true)->orderBy('nom')->get();
        return view('devis.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $eid  = auth()->user()->entreprise_id;
        $data = $request->validate([
            'client_id'     => 'required|exists:clients,id',
            'date_devis'    => 'required|date',
            'date_validite' => 'nullable|date|after_or_equal:date_devis',
            'devise'        => 'required|string|size:3',
            'notes'         => 'nullable|string|max:1000',
            'lignes'                    => 'required|array|min:1',
            'lignes.*.designation'      => 'required|string|max:500',
            'lignes.*.quantite'         => 'required|numeric|min:0.001',
            'lignes.*.prix_unitaire_ht' => 'required|numeric|min:0',
        ]);

        $devisId = null;
        DB::transaction(function () use ($request, $eid, &$devisId) {
            $entreprise  = auth()->user()->entreprise;
            $numero      = 'DV-' . now()->format('Ym') . '-' . str_pad(
                Devis::where('entreprise_id', $eid)->count() + 1, 4, '0', STR_PAD_LEFT
            );

            $totalHt  = 0;
            $totalTtc = 0;
            foreach ($request->lignes as $l) {
                $ht = ($l['quantite'] ?? 1) * ($l['prix_unitaire_ht'] ?? 0);
                $totalHt  += $ht;
                $totalTtc += $ht; // taxes à intégrer selon votre logique
            }

            $devis = Devis::create([
                'entreprise_id' => $eid,
                'numero_devis'  => $numero,
                'client_id'     => $request->client_id,
                'statut'        => 'brouillon',
                'date_devis'    => $request->date_devis,
                'date_validite' => $request->date_validite,
                'devise'        => $request->devise ?? 'XOF',
                'total_ht'      => $totalHt,
                'total_ttc'     => $totalTtc,
                'notes'         => $request->notes,
                'cree_par'      => auth()->id(),
            ]);

            foreach ($request->lignes as $i => $ligne) {
                LigneDocument::create([
                    'type_document'   => 'devis',
                    'document_id'     => $devis->id,
                    'ordre_affichage' => $i + 1,
                    'designation'     => $ligne['designation'],
                    'quantite'        => $ligne['quantite'],
                    'prix_unitaire_ht'=> $ligne['prix_unitaire_ht'],
                    'unite'           => $ligne['unite'] ?? null,
                    'total_ht_ligne'  => ($ligne['quantite'] * $ligne['prix_unitaire_ht']),
                    'total_ttc_ligne' => ($ligne['quantite'] * $ligne['prix_unitaire_ht']),
                    'total_taxes_ligne'=> 0,
                ]);
            }

            $devisId = $devis->id;
        });

        return redirect()->route('devis.index')->with('success', 'Devis créé avec succès.');
    }

    public function show(Devis $devis)
    {
        $this->autoriser($devis);
        $devis->load(['client', 'lignes', 'createur']);
        return view('devis.show', compact('devis'));
    }

    public function edit(Devis $devis)
    {
        $this->autoriser($devis);
        return view('devis.edit', compact('devis'));
    }

    public function update(Request $request, Devis $devis)
    {
        $this->autoriser($devis);
        $data = $request->validate([
            'date_validite' => 'nullable|date',
            'notes'         => 'nullable|string|max:1000',
        ]);
        $devis->update($data);
        return redirect()->route('devis.show', $devis)->with('success', 'Devis mis à jour.');
    }

    public function destroy(Devis $devis)
    {
        $this->autoriser($devis);
        $devis->lignes()->delete();
        $devis->delete();
        return redirect()->route('devis.index')->with('success', 'Devis supprimé.');
    }

    private function autoriser(Devis $devis): void
    {
        if ($devis->entreprise_id !== auth()->user()->entreprise_id) abort(403);
    }

    // ══════════════════════════════════════════════════════
    //  MÉTHODES API MOBILE
    // ══════════════════════════════════════════════════════

    public function apiIndex(Request $request)
    {
        $eid   = auth()->user()->entreprise_id;
        $query = Devis::with('client')->where('entreprise_id', $eid);

        if ($request->filled('statut')) $query->where('statut', $request->statut);
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn($sq) => $sq
                ->where('numero_devis', 'like', "%$q%")
                ->orWhereHas('client', fn($cq) => $cq
                    ->where('nom', 'like', "%$q%")
                    ->orWhere('raison_sociale', 'like', "%$q%")
                ));
        }

        $devis = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return response()->json([
            'data' => $devis->items(),
            'meta' => [
                'current_page' => $devis->currentPage(),
                'last_page'    => $devis->lastPage(),
                'per_page'     => $devis->perPage(),
                'total'        => $devis->total(),
            ],
        ]);
    }

    public function apiStore(Request $request)
    {
        $eid = auth()->user()->entreprise_id;

        $request->validate([
            'client_id'     => 'required|exists:clients,id',
            'date_devis'    => 'required|date',
            'date_validite' => 'nullable|date|after_or_equal:date_devis',
            'devise'        => 'required|string|size:3',
            'notes'         => 'nullable|string|max:1000',
            'lignes'                    => 'required|array|min:1',
            'lignes.*.designation'      => 'required|string|max:500',
            'lignes.*.quantite'         => 'required|numeric|min:0.001',
            'lignes.*.prix_unitaire_ht' => 'required|numeric|min:0',
        ]);

        $devisId = null;
        DB::transaction(function () use ($request, $eid, &$devisId) {
            $numero = 'DV-' . now()->format('Ym') . '-' . str_pad(
                Devis::where('entreprise_id', $eid)->count() + 1, 4, '0', STR_PAD_LEFT
            );

            $totalHt = $totalTtc = 0;
            foreach ($request->lignes as $l) {
                $ht = ($l['quantite'] ?? 1) * ($l['prix_unitaire_ht'] ?? 0);
                $totalHt  += $ht;
                $totalTtc += $ht;
            }

            $devis = Devis::create([
                'entreprise_id' => $eid,
                'numero_devis'  => $numero,
                'client_id'     => $request->client_id,
                'statut'        => 'brouillon',
                'date_devis'    => $request->date_devis,
                'date_validite' => $request->date_validite,
                'devise'        => $request->devise ?? 'XOF',
                'total_ht'      => $totalHt,
                'total_ttc'     => $totalTtc,
                'notes'         => $request->notes,
                'cree_par'      => auth()->id(),
            ]);

            foreach ($request->lignes as $i => $ligne) {
                LigneDocument::create([
                    'type_document'    => 'devis',
                    'document_id'      => $devis->id,
                    'ordre_affichage'  => $i + 1,
                    'designation'      => $ligne['designation'],
                    'quantite'         => $ligne['quantite'],
                    'prix_unitaire_ht' => $ligne['prix_unitaire_ht'],
                    'unite'            => $ligne['unite'] ?? null,
                    'total_ht_ligne'   => $ligne['quantite'] * $ligne['prix_unitaire_ht'],
                    'total_ttc_ligne'  => $ligne['quantite'] * $ligne['prix_unitaire_ht'],
                    'total_taxes_ligne'=> 0,
                ]);
            }

            $devisId = $devis->id;
        });

        return response()->json(Devis::with(['client', 'lignes'])->find($devisId), 201);
    }

    public function apiShow(int $id)
    {
        $devis = Devis::with(['client', 'lignes', 'createur'])
            ->where('entreprise_id', auth()->user()->entreprise_id)
            ->findOrFail($id);

        return response()->json($devis);
    }

    public function apiUpdate(Request $request, int $id)
    {
        $devis = Devis::where('entreprise_id', auth()->user()->entreprise_id)->findOrFail($id);

        $data = $request->validate([
            'date_validite' => 'nullable|date',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $devis->update($data);
        return response()->json($devis->fresh());
    }

    public function apiDestroy(int $id)
    {
        $devis = Devis::where('entreprise_id', auth()->user()->entreprise_id)->findOrFail($id);
        $devis->lignes()->delete();
        $devis->delete();
        return response()->json(['message' => 'Devis supprimé.']);
    }

    public function apiEnvoyer(Request $request, int $id)
    {
        $devis = Devis::where('entreprise_id', auth()->user()->entreprise_id)->findOrFail($id);
        $devis->update(['statut' => 'envoye']);
        return response()->json(['message' => 'Devis marqué comme envoyé.', 'devis' => $devis->fresh()]);
    }

    public function apiConvertir(Request $request, int $id)
    {
        $request->validate(['type_facture' => 'required|in:simple,proforma,acompte']);

        $devis = Devis::with(['client', 'lignes'])
            ->where('entreprise_id', auth()->user()->entreprise_id)
            ->findOrFail($id);

        if (!in_array($devis->statut, ['envoye', 'accepte'])) {
            return response()->json(['message' => 'Ce devis ne peut pas être converti.'], 422);
        }

        $factureId = null;
        DB::transaction(function () use ($devis, $request, &$factureId) {
            $eid        = auth()->user()->entreprise_id;
            $entreprise = auth()->user()->entreprise;
            $type       = $request->type_facture;
            $numero     = $entreprise->prochainNumero($type);

            $facture = Facture::create([
                'entreprise_id'   => $eid,
                'type_facture'    => $type,
                'numero_facture'  => $numero,
                'statut'          => 'en_attente_validation',
                'client_id'       => $devis->client_id,
                'snapshot_client' => [
                    'id'           => $devis->client->id,
                    'nom_affichage'=> $devis->client->nom_affichage,
                    'email'        => $devis->client->email,
                    'telephone'    => $devis->client->telephone,
                ],
                'date_emission'   => now()->toDateString(),
                'devise'          => $devis->devise,
                'total_ht'        => $devis->total_ht,
                'total_remises_ht'=> 0,
                'total_taxes'     => $devis->total_ttc - $devis->total_ht,
                'total_ttc'       => $devis->total_ttc,
                'montant_paye'    => 0,
                'montant_du'      => $devis->total_ttc,
                'notes'           => $devis->notes,
                'cree_par'        => auth()->id(),
                'token_public'    => \Illuminate\Support\Str::uuid(),
            ]);

            foreach ($devis->lignes as $i => $ligne) {
                LigneDocument::create([
                    'type_document'    => 'facture',
                    'document_id'      => $facture->id,
                    'ordre_affichage'  => $i + 1,
                    'designation'      => $ligne->designation,
                    'quantite'         => $ligne->quantite,
                    'prix_unitaire_ht' => $ligne->prix_unitaire_ht,
                    'unite'            => $ligne->unite,
                    'total_ht_ligne'   => $ligne->total_ht_ligne,
                    'total_ttc_ligne'  => $ligne->total_ttc_ligne,
                    'total_taxes_ligne'=> $ligne->total_taxes_ligne,
                ]);
            }

            $devis->update(['statut' => 'converti', 'facture_convertie_id' => $facture->id]);
            $factureId = $facture->id;
        });

        return response()->json([
            'message'    => 'Devis converti en facture.',
            'facture_id' => $factureId,
        ], 201);
    }

    public function apiDupliquer(int $id)
    {
        $devis = Devis::with('lignes')
            ->where('entreprise_id', auth()->user()->entreprise_id)
            ->findOrFail($id);

        $newId = null;
        DB::transaction(function () use ($devis, &$newId) {
            $eid    = auth()->user()->entreprise_id;
            $numero = 'DV-' . now()->format('Ym') . '-' . str_pad(
                Devis::where('entreprise_id', $eid)->count() + 1, 4, '0', STR_PAD_LEFT
            );

            $nouveau = $devis->replicate(['numero_devis', 'statut', 'facture_convertie_id']);
            $nouveau->numero_devis = $numero;
            $nouveau->statut       = 'brouillon';
            $nouveau->date_devis   = now()->toDateString();
            $nouveau->cree_par     = auth()->id();
            $nouveau->save();

            foreach ($devis->lignes as $ligne) {
                $nl = $ligne->replicate(['document_id']);
                $nl->document_id = $nouveau->id;
                $nl->save();
            }

            $newId = $nouveau->id;
        });

        return response()->json(Devis::with(['client', 'lignes'])->find($newId), 201);
    }
}