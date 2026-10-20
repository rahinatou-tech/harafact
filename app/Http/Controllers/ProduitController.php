<?php
namespace App\Http\Controllers;

use App\Models\ProduitService;
use App\Models\GroupeTaxe;
use App\Models\JournalAudit;
use Illuminate\Http\Request;

class ProduitController extends Controller
{
    public function index(Request $request)
    {
        $eid   = auth()->user()->entreprise_id;
        $entreprise = auth()->user()->entreprise;
        $query = ProduitService::with('groupeTaxe')
            ->where('entreprise_id', $eid);

        // Filtre type selon activité entreprise
        if ($entreprise->type_activite === 'produits') {
            $query->where('type', 'produit');
        } elseif ($entreprise->type_activite === 'services') {
            $query->where('type', 'service');
        }
        // 'les_deux' = pas de filtre

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn($sq) => $sq
                ->where('nom',       'like', "%$q%")
                ->orWhere('reference','like', "%$q%")
                ->orWhere('description','like', "%$q%")
            );
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('statut')) {
            $query->where('est_actif', $request->statut === 'actif');
        }

        $produits = $query->orderBy('nom')->paginate(15)->withQueryString();

        $stats = [
            'total'    => ProduitService::where('entreprise_id', $eid)->count(),
            'actifs'   => ProduitService::where('entreprise_id', $eid)->where('est_actif', true)->count(),
            'produits' => ProduitService::where('entreprise_id', $eid)->where('type', 'produit')->count(),
            'services' => ProduitService::where('entreprise_id', $eid)->where('type', 'service')->count(),
        ];

        $groupesTaxes = GroupeTaxe::where('entreprise_id', $eid)->where('est_actif', true)->get();

        // ✅ JSON préparé côté contrôleur pour éviter le bug Blade avec fn()=>
        $produitsJson = $produits->getCollection()->map(fn($p) => [
            'id'     => $p->id,
            'nom'    => $p->nom,
            'prix'   => $p->prix_unitaire_ht,
            'unite'  => $p->unite,
            'statut' => $p->est_actif,
            'type'   => $p->type,
        ]);

        return view('produits.index', compact('produits', 'stats', 'groupesTaxes', 'entreprise', 'produitsJson'));
    }

    public function store(Request $request)
    {
        $eid = auth()->user()->entreprise_id;

        $data = $request->validate([
            'nom'              => 'required|string|max:200',
            'reference'        => 'nullable|string|max:60',
            'description'      => 'nullable|string',
            'type'             => 'required|in:produit,service',
            'unite'            => 'nullable|string|max:30',
            'prix_unitaire_ht' => 'required|numeric|min:0',
            'groupe_taxes_id'  => 'nullable|exists:groupes_taxes,id',
            'code_comptable'   => 'nullable|string|max:20',
            'stock'            => 'nullable|integer|min:0',
        ]);

        // Vérifier unicité référence par entreprise
        if (!empty($data['reference'])) {
            $existe = ProduitService::where('entreprise_id', $eid)
                ->where('reference', $data['reference'])
                ->exists();
            if ($existe) {
                return back()
                    ->withErrors(['reference' => 'Cette référence existe déjà dans votre catalogue.'])
                    ->withInput();
            }
        }

        $data['entreprise_id'] = $eid;
        $data['est_actif']     = true;
        $data['cree_par']      = auth()->id();

        $produit = ProduitService::create($data);

        JournalAudit::enregistrer('produit_service', $produit->id, 'creation', [], $data);

        return redirect()
            ->route('produits.index')
            ->with('success', "Produit/service « {$produit->nom} » ajouté.");
    }

    public function update(Request $request, ProduitService $produit)
    {
        $this->autoriser($produit);
        $anciennes = $produit->toArray();

        $data = $request->validate([
            'nom'              => 'required|string|max:200',
            'description'      => 'nullable|string',
            'type'             => 'required|in:produit,service',
            'unite'            => 'nullable|string|max:30',
            'prix_unitaire_ht' => 'required|numeric|min:0',
            'groupe_taxes_id'  => 'nullable|exists:groupes_taxes,id',
            'code_comptable'   => 'nullable|string|max:20',
            'stock'            => 'nullable|integer|min:0',
            'est_actif'        => 'boolean',
        ]);

        $produit->update($data);
        JournalAudit::enregistrer('produit_service', $produit->id, 'modification', $anciennes, $data);

        return redirect()
            ->route('produits.index')
            ->with('success', "Produit/service « {$produit->nom} » modifié.");
    }

    public function destroy(ProduitService $produit)
    {
        $this->autoriser($produit);

        if ($produit->lignes()->exists()) {
            return back()->with('error', 'Impossible de supprimer : ce produit est utilisé dans des factures ou devis.');
        }

        $nom = $produit->nom;
        $produit->delete();
        JournalAudit::enregistrer('produit_service', $produit->id, 'suppression');

        return redirect()->route('produits.index')->with('success', "Produit « $nom » supprimé.");
    }

    // API JSON — pour l'autocomplétion dans les formulaires de facture
    public function catalogue(Request $request)
    {
        $eid       = auth()->user()->entreprise_id;
        $entreprise = auth()->user()->entreprise;

        $query = ProduitService::with('groupeTaxe')
            ->where('entreprise_id', $eid)
            ->where('est_actif', true);

        if ($entreprise->type_activite === 'produits') $query->where('type', 'produit');
        if ($entreprise->type_activite === 'services') $query->where('type', 'service');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn($sq) => $sq
                ->where('nom',        'like', "%$q%")
                ->orWhere('reference', 'like', "%$q%")
            );
        }

        $produits = $query->select('id','reference','nom','type','unite','prix_unitaire_ht','groupe_taxes_id')
            ->with('groupeTaxe:id,nom,taux_total,composantes')
            ->limit(20)
            ->get();

        return response()->json($produits);
    }

    private function autoriser(ProduitService $produit): void
    {
        if ($produit->entreprise_id !== auth()->user()->entreprise_id) abort(403);
    }


     
// ════════════════════════════════════════════════════════
//  MÉTHODES API À AJOUTER dans ProduitController.php
//  Coller AVANT la dernière accolade }
// ════════════════════════════════════════════════════════
 
    // ── API : liste produits ──────────────────────────────
    public function apiIndex(Request $request)
    {
        $eid   = auth()->user()->entreprise_id;
        $query = \App\Models\ProduitService::with('groupeTaxe')
            ->where('entreprise_id', $eid)
            ->where('est_actif', true);
 
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn($sq) => $sq
                ->where('nom',       'like', "%$q%")
                ->orWhere('reference','like', "%$q%")
            );
        }
 
        $perPage  = $request->get('per_page', 100);
        $produits = $query->orderBy('nom')->paginate($perPage);
 
        return response()->json([
            'data' => $produits->items(),
            'meta' => ['current_page' => $produits->currentPage(), 'last_page' => $produits->lastPage(), 'total' => $produits->total()],
        ]);
    }
 
    // ── API : créer produit ───────────────────────────────
    public function apiStore(Request $request)
    {
        $eid = auth()->user()->entreprise_id;
 
        $data = $request->validate([
            'nom'              => 'required|string|max:200',
            'reference'        => 'nullable|string|max:60',
            'type'             => 'nullable|in:produit,service',
            'unite'            => 'nullable|string|max:30',
            'prix_unitaire_ht' => 'required|numeric|min:0',
            'groupe_taxes_id'  => 'nullable|exists:groupes_taxes,id',
        ]);
 
        $data['entreprise_id'] = $eid;
        $data['est_actif']     = true;
        $data['type']          = $data['type'] ?? 'service';
        $data['cree_par']      = auth()->id();
 
        $produit = \App\Models\ProduitService::create($data);
        return response()->json($produit->load('groupeTaxe'), 201);
    }
 
    // ── API : modifier produit ────────────────────────────
    public function apiUpdate(Request $request, int $id)
    {
        $produit = \App\Models\ProduitService::where('entreprise_id', auth()->user()->entreprise_id)
            ->findOrFail($id);
 
        $data = $request->validate([
            'nom'              => 'string|max:200',
            'unite'            => 'nullable|string|max:30',
            'prix_unitaire_ht' => 'numeric|min:0',
            'groupe_taxes_id'  => 'nullable|exists:groupes_taxes,id',
            'est_actif'        => 'boolean',
        ]);
 
        $produit->update($data);
        return response()->json($produit->fresh());
    }
 
    // ── API : supprimer produit ───────────────────────────
    public function apiDestroy(int $id)
    {
        $produit = \App\Models\ProduitService::where('entreprise_id', auth()->user()->entreprise_id)
            ->findOrFail($id);
 
        if ($produit->lignes()->exists()) {
            return response()->json(['message' => 'Impossible : ce produit est utilisé dans des documents.'], 422);
        }
 
        $produit->delete();
        return response()->json(['message' => 'Produit supprimé.']);
    }
 
    // ── API : show produit ────────────────────────────────
    public function apiShow(int $id)
    {
        $produit = \App\Models\ProduitService::with('groupeTaxe')
            ->where('entreprise_id', auth()->user()->entreprise_id)
            ->findOrFail($id);
 
        return response()->json($produit);
    }
}
