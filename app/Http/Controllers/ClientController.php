<?php
namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\GroupeTaxe;
use App\Models\JournalAudit;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $eid   = auth()->user()->entreprise_id;
        $query = Client::with('groupeTaxe')
            ->where('entreprise_id', $eid);

        // Recherche
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('nom',            'like', "%$q%")
                   ->orWhere('prenom',        'like', "%$q%")
                   ->orWhere('raison_sociale','like', "%$q%")
                   ->orWhere('code_client',   'like', "%$q%")
                   ->orWhere('email',         'like', "%$q%")
                   ->orWhere('telephone',     'like', "%$q%");
            });
        }

        // Filtre statut
        if ($request->filled('statut')) {
            $query->where('est_actif', $request->statut === 'actif');
        }

        // Filtre type
        if ($request->filled('type')) {
            $query->where('type_client', $request->type);
        }

        $clients = $query->orderBy('nom')->paginate(15)->withQueryString();

        $stats = [
            'total'       => Client::where('entreprise_id', $eid)->count(),
            'actifs'      => Client::where('entreprise_id', $eid)->where('est_actif', true)->count(),
            'entreprises' => Client::where('entreprise_id', $eid)->where('type_client', 'entreprise')->count(),
            'particuliers'=> Client::where('entreprise_id', $eid)->where('type_client', 'particulier')->count(),
        ];

        $groupesTaxes = GroupeTaxe::where('entreprise_id', $eid)->where('est_actif', true)->get();

        // ✅ JSON préparé côté contrôleur pour éviter le bug Blade avec fn()=>
        $clientsJson = $clients->getCollection()->map(fn($c) => [
            'id'        => $c->id,
            'nom'       => $c->nom_affichage,
            'email'     => $c->email,
            'telephone' => $c->telephone,
            'adresse'   => $c->adresse_ligne1,
            'ville'     => $c->ville,
            'pays'      => $c->pays,
            'notes'     => $c->notes,
            'est_actif' => $c->est_actif,
            'type'      => $c->type_client,
        ]);

        return view('clients.index', compact('clients', 'stats', 'groupesTaxes', 'clientsJson'));
    }

    public function store(Request $request)
    {
        $eid = auth()->user()->entreprise_id;

        $data = $request->validate([
            'type_client'          => 'required|in:particulier,entreprise',
            'raison_sociale'       => 'required_if:type_client,entreprise|nullable|string|max:150',
            'prenom'               => 'nullable|string|max:80',
            'nom'                  => 'required_if:type_client,particulier|nullable|string|max:80',
            'numero_fiscal'        => 'nullable|string|max:50',
            'numero_tva'           => 'nullable|string|max:50',
            'email'                => 'nullable|email|max:150',
            'telephone'            => 'nullable|string|max:30',
            'adresse_ligne1'       => 'nullable|string|max:200',
            'adresse_ligne2'       => 'nullable|string|max:200',
            'ville'                => 'nullable|string|max:100',
            'pays'                 => 'required|string|size:2',
            'devise'               => 'nullable|string|size:3',
            'delai_paiement_jours' => 'nullable|integer|min:0|max:365',
            'groupe_taxes_id'      => 'nullable|exists:groupes_taxes,id',
            'plafond_credit'       => 'nullable|numeric|min:0',
            'notes'                => 'nullable|string|max:1000',
        ], [
            'raison_sociale.required_if' => 'La raison sociale est obligatoire pour une entreprise.',
            'nom.required_if'            => 'Le nom est obligatoire pour un particulier.',
        ]);

        $data['entreprise_id'] = $eid;
        $data['code_client']   = Client::genererCode($eid);
        $data['est_actif']     = true;
        $data['cree_par']      = auth()->id();

        $client = Client::create($data);

        JournalAudit::enregistrer('client', $client->id, 'creation', [], $data);

        return redirect()
            ->route('clients.index')
            ->with('success', "Client {$client->nom_affichage} ({$client->code_client}) créé avec succès.");
    }

    public function show(Client $client)
    {
        $this->autoriser($client);
        $client->load(['groupeTaxe', 'createur']);

        $factures = $client->factures()
            ->latest('date_emission')
            ->take(10)
            ->get();

        $devis = $client->devis()
            ->latest()
            ->take(5)
            ->get();

        $stats = [
            'total_factures' => $client->factures()->count(),
            'total_ca'       => $client->factures()->whereIn('statut', ['paye', 'partiellement_paye'])->sum('montant_paye'),
            'total_du'       => $client->factures()->whereIn('statut', ['envoye', 'partiellement_paye', 'en_retard'])->sum('montant_du'),
            'nb_retard'      => $client->factures()->where('statut', 'en_retard')->count(),
        ];

        return view('clients.show', compact('client', 'factures', 'devis', 'stats'));
    }

    public function update(Request $request, Client $client)
    {
        $this->autoriser($client);

        $anciennes = $client->toArray();

        $data = $request->validate([
            'type_client'          => 'required|in:particulier,entreprise',
            'raison_sociale'       => 'nullable|string|max:150',
            'prenom'               => 'nullable|string|max:80',
            'nom'                  => 'nullable|string|max:80',
            'numero_fiscal'        => 'nullable|string|max:50',
            'numero_tva'           => 'nullable|string|max:50',
            'email'                => 'nullable|email|max:150',
            'telephone'            => 'nullable|string|max:30',
            'adresse_ligne1'       => 'nullable|string|max:200',
            'ville'                => 'nullable|string|max:100',
            'pays'                 => 'required|string|size:2',
            'devise'               => 'nullable|string|size:3',
            'delai_paiement_jours' => 'nullable|integer|min:0|max:365',
            'groupe_taxes_id'      => 'nullable|exists:groupes_taxes,id',
            'plafond_credit'       => 'nullable|numeric|min:0',
            'notes'                => 'nullable|string|max:1000',
            'est_actif'            => 'boolean',
        ]);

        $client->update($data);

        JournalAudit::enregistrer('client', $client->id, 'modification', $anciennes, $data);

        return redirect()
            ->route('clients.index')
            ->with('success', "Client {$client->nom_affichage} modifié.");
    }

    public function destroy(Client $client)
    {
        $this->autoriser($client);

        // Vérifier aucune facture impayée
        if ($client->factures()->whereIn('statut', ['envoye', 'partiellement_paye', 'en_retard'])->exists()) {
            return back()->with('error', "Impossible d'archiver ce client : il a des factures en attente de paiement.");
        }

        $nom = $client->nom_affichage;
        $client->delete(); // Soft delete

        JournalAudit::enregistrer('client', $client->id, 'suppression');

        return redirect()
            ->route('clients.index')
            ->with('success', "Client $nom archivé.");
    }

    private function autoriser(Client $client): void
    {
        if ($client->entreprise_id !== auth()->user()->entreprise_id) {
            abort(403);
        }
    }

    
// ════════════════════════════════════════════════════════
//  MÉTHODES API À AJOUTER dans ClientController.php
//  Coller ces méthodes AVANT la dernière accolade } du fichier
// ════════════════════════════════════════════════════════

    // ── API : liste clients ───────────────────────────────
    public function apiIndex(Request $request)
    {
        $eid   = auth()->user()->entreprise_id;
        $query = \App\Models\Client::where('entreprise_id', $eid)
            ->where('est_actif', true);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn($sq) => $sq
                ->where('nom',            'like', "%$q%")
                ->orWhere('raison_sociale','like', "%$q%")
                ->orWhere('email',         'like', "%$q%")
                ->orWhere('telephone',     'like', "%$q%")
            );
        }

        $perPage = $request->get('per_page', 50);
        $clients = $query->orderBy('nom')->paginate($perPage);

        return response()->json([
            'data' => $clients->items(),
            'meta' => [
                'current_page' => $clients->currentPage(),
                'last_page'    => $clients->lastPage(),
                'total'        => $clients->total(),
            ],
        ]);
    }

    // ── API : créer client ────────────────────────────────
    public function apiStore(Request $request)
    {
        $eid = auth()->user()->entreprise_id;

        $data = $request->validate([
            'nom'           => 'required|string|max:150',
            'raison_sociale'=> 'nullable|string|max:150',
            'email'         => 'nullable|email|max:150',
            'telephone'     => 'nullable|string|max:30',
            'adresse_ligne1'=> 'nullable|string|max:200',
            'ville'         => 'nullable|string|max:100',
            'pays'          => 'nullable|string|size:2',
        ]);

        // Le mobile envoie toujours la clé 'raison_sociale', même vide
        // ('' plutôt que null). On normalise ici pour que les ?? et empty()
        // utilisés ailleurs (nom_affichage, type_client...) se comportent
        // de façon prévisible.
        if (isset($data['raison_sociale']) && trim($data['raison_sociale']) === '') {
            $data['raison_sociale'] = null;
        }

        $data['entreprise_id'] = $eid;
        $data['code_client']   = \App\Models\Client::genererCode($eid);
        $data['est_actif']     = true;
        $data['cree_par']      = auth()->id();
        $data['type_client']   = !empty($data['raison_sociale']) ? 'entreprise' : 'particulier';
        $data['pays']          = $data['pays'] ?? 'NE';

        $client = \App\Models\Client::create($data);
        return response()->json($client, 201);
    }

    // ── API : voir client ─────────────────────────────────
    public function apiShow(int $id)
    {
        $client = \App\Models\Client::where('entreprise_id', auth()->user()->entreprise_id)
            ->findOrFail($id);

        $stats = [
            'total_factures' => $client->factures()->count(),
            'total_ca'       => $client->factures()->whereIn('statut', ['paye', 'partiellement_paye'])->sum('montant_paye'),
            'total_du'       => $client->factures()->whereIn('statut', ['envoye', 'partiellement_paye', 'en_retard'])->sum('montant_du'),
        ];

        return response()->json(['client' => $client, 'stats' => $stats]);
    }

    // ── API : modifier client ─────────────────────────────
    public function apiUpdate(Request $request, int $id)
    {
        $client = \App\Models\Client::where('entreprise_id', auth()->user()->entreprise_id)
            ->findOrFail($id);

        $data = $request->validate([
            'nom'           => 'string|max:150',
            'raison_sociale'=> 'nullable|string|max:150',
            'email'         => 'nullable|email|max:150',
            'telephone'     => 'nullable|string|max:30',
            'adresse_ligne1'=> 'nullable|string|max:200',
            'ville'         => 'nullable|string|max:100',
            'pays'          => 'nullable|string|size:2',
            'est_actif'     => 'boolean',
        ]);

        // Même normalisation que apiStore : '' envoyé par le mobile -> null.
        if (isset($data['raison_sociale']) && trim($data['raison_sociale']) === '') {
            $data['raison_sociale'] = null;
        }

        $client->update($data);
        return response()->json($client->fresh());
    }

    // ── API : supprimer client ────────────────────────────
    public function apiDestroy(int $id)
    {
        $client = \App\Models\Client::where('entreprise_id', auth()->user()->entreprise_id)
            ->findOrFail($id);

        if ($client->factures()->whereIn('statut', ['envoye', 'partiellement_paye', 'en_retard'])->exists()) {
            return response()->json(['message' => 'Impossible : ce client a des factures impayées.'], 422);
        }

        $client->delete();
        return response()->json(['message' => 'Client archivé.']);
    }
}