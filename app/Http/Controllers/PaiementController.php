<?php
namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Facture;
use App\Models\JournalAudit;
use Illuminate\Http\Request;

class PaiementController extends Controller
{
    public function index()
    {
        $eid = auth()->user()->entreprise_id;

        // Factures nécessitant un suivi actif
        $factures = Facture::with(['client', 'paiements'])
            ->where('entreprise_id', $eid)
            ->aSuivre()
            ->orderBy('date_echeance')
            ->paginate(12);

        // Statistiques
        $stats = [
            'total_encaisse_mois' => Paiement::where('entreprise_id', $eid)
                ->whereMonth('created_at', now()->month)
                ->where('est_auto', false)
                ->sum('montant'),

            'factures_a_suivre' => Facture::where('entreprise_id', $eid)
                ->aSuivre()
                ->count(),

            'total_restant_du' => Facture::where('entreprise_id', $eid)
                ->aSuivre()
                ->sum('montant_du'),

            'en_retard' => Facture::where('entreprise_id', $eid)
                ->where('statut', 'en_retard')
                ->count(),
        ];

        // Derniers paiements saisis manuellement
        $derniers_paiements = Paiement::with(['facture.client'])
            ->where('entreprise_id', $eid)
            ->where('est_auto', false)
            ->latest()
            ->take(8)
            ->get();

        return view('paiements.index', compact('factures', 'stats', 'derniers_paiements'));
    }

    public function store(Request $request)
    {
        $eid = auth()->user()->entreprise_id;

        $data = $request->validate([
            'facture_id'       => 'required|exists:factures,id',
            'montant'          => 'required|numeric|min:1',
            'date_paiement'    => 'required|date|before_or_equal:today',
            'type_paiement'    => 'required|in:virement,cheque,carte,especes,prelevement,mobile_money,autre',
            'libelle_paiement' => 'nullable|string|max:80',
            'reference_externe'=> 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:500',
        ], [
            'montant.min'              => 'Le montant doit être supérieur à 0.',
            'date_paiement.before_or_equal' => 'La date de paiement ne peut pas être dans le futur.',
        ]);

        $facture = Facture::where('entreprise_id', $eid)->findOrFail($data['facture_id']);

        // Vérification : facture doit être à suivi manuel
        if ($facture->paiement_auto) {
            return back()->with('error', 'Cette facture est à paiement automatique. Aucune saisie manuelle requise.');
        }

        // Vérification : pas de surpaiement
        $restant = $facture->montant_du;
        if ($data['montant'] > $restant + 0.01) {
            return back()
                ->withErrors(['montant' => "Le montant saisi (" . number_format($data['montant'], 0, ',', ' ') . " XOF) dépasse le restant dû (" . number_format($restant, 0, ',', ' ') . " XOF)."])
                ->withInput();
        }

        $data['entreprise_id']             = $eid;
        $data['devise']                    = $facture->devise;
        $data['montant_devise_principale'] = $data['montant'];
        $data['est_auto']                  = false;
        $data['saisie_par']                = auth()->id();

        $paiement = Paiement::create($data);
        // L'Observer dans Paiement::booted() appelle automatiquement $facture->recalculerMontants()

        JournalAudit::enregistrer('paiement', $paiement->id, 'paiement', [], [
            'facture'  => $facture->numero_facture,
            'montant'  => $data['montant'],
            'mode'     => $data['type_paiement'],
        ]);

        $message = "Paiement de " . number_format($data['montant'], 0, ',', ' ') . " XOF enregistré.";
        if ($facture->fresh()->statut === 'paye') {
            $message .= " ✅ Facture {$facture->numero_facture} soldée !";
        }

        return redirect()->route('paiements.index')->with('success', $message);
    }

    public function destroy(Paiement $paiement)
    {
        $eid = auth()->user()->entreprise_id;

        if ($paiement->entreprise_id !== $eid) abort(403);
        if ($paiement->est_auto) {
            return back()->with('error', 'Impossible de supprimer un paiement automatique.');
        }

        $montant = $paiement->montant;
        $paiement->delete();
        // L'Observer recalcule automatiquement la facture

        return redirect()->route('paiements.index')
            ->with('success', "Paiement de " . number_format($montant, 0, ',', ' ') . " XOF annulé.");
    }

    // ── API : liste paiements ─────────────────────────────
    public function apiIndex(Request $request)
    {
        $eid = auth()->user()->entreprise_id;
 
        $paiements = \App\Models\Paiement::with(['facture.client'])
            ->where('entreprise_id', $eid)
            ->latest()
            ->paginate(20);
 
        return response()->json([
            'data' => $paiements->items(),
            'meta' => [
                'current_page' => $paiements->currentPage(),
                'last_page'    => $paiements->lastPage(),
                'total'        => $paiements->total(),
            ],
        ]);
    }
 
    // ── API : enregistrer paiement ────────────────────────
    public function apiStore(Request $request)
    {
        $eid = auth()->user()->entreprise_id;
 
        $data = $request->validate([
            'facture_id'        => 'required|exists:factures,id',
            'montant'           => 'required|numeric|min:1',
            'date_paiement'     => 'required|date',
            'type_paiement'     => 'required|in:virement,cheque,carte,especes,prelevement,mobile_money,autre',
            'libelle_paiement'  => 'nullable|string|max:80',
            'reference_externe' => 'nullable|string|max:100',
        ]);
 
        $facture = \App\Models\Facture::where('entreprise_id', $eid)->findOrFail($data['facture_id']);
 
        if ($data['montant'] > $facture->montant_du + 0.01) {
            return response()->json([
                'message' => 'Le montant dépasse le restant dû (' . number_format($facture->montant_du, 0) . ' XOF).'
            ], 422);
        }
 
        $data['entreprise_id']             = $eid;
        $data['devise']                    = $facture->devise;
        $data['montant_devise_principale'] = $data['montant'];
        $data['est_auto']                  = false;
        $data['saisie_par']                = auth()->id();
 
        $paiement = \App\Models\Paiement::create($data);
 
        return response()->json($paiement->load('facture'), 201);
    }
 
    // ── API : supprimer paiement ──────────────────────────
    public function apiDestroy(int $id)
    {
        $paiement = \App\Models\Paiement::where('entreprise_id', auth()->user()->entreprise_id)
            ->findOrFail($id);
 
        if ($paiement->est_auto) {
            return response()->json(['message' => 'Impossible de supprimer un paiement automatique.'], 422);
        }
 
        $paiement->delete();
        return response()->json(['message' => 'Paiement annulé.']);
    }
 
    // ── API : factures à suivre ───────────────────────────
    public function apiFActuresASuivre(Request $request)
    {
        $eid = auth()->user()->entreprise_id;
 
        $factures = \App\Models\Facture::with(['client', 'paiements'])
            ->where('entreprise_id', $eid)
            ->aSuivre()
            ->orderBy('date_echeance')
            ->paginate(20);
 
        $stats = [
            'total_encaisse_mois' => \App\Models\Paiement::where('entreprise_id', $eid)
                ->whereMonth('created_at', now()->month)
                ->where('est_auto', false)
                ->sum('montant'),
            'factures_a_suivre'   => \App\Models\Facture::where('entreprise_id', $eid)->aSuivre()->count(),
            'total_restant_du'    => \App\Models\Facture::where('entreprise_id', $eid)->aSuivre()->sum('montant_du'),
            'en_retard'           => \App\Models\Facture::where('entreprise_id', $eid)->where('statut', 'en_retard')->count(),
        ];
 
        return response()->json([
            'data'  => $factures->items(),
            'meta'  => ['current_page' => $factures->currentPage(), 'last_page' => $factures->lastPage()],
            'stats' => $stats,
        ]);
    }
 
 
}


// ============================================================
// FICHIER : routes/web.php  —  VERSION FINALE COMPLÈTE
// ============================================================
/*
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\UtilisateurController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\DevisController;

// ─── AUTH ────────────────────────────────────────────────────
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout',[AuthController::class, 'logout'])->name('logout');

// ─── APP PROTÉGÉE ────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    Route::get('/',          [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Factures
    Route::resource('factures', FactureController::class);
    Route::post('factures/{facture}/dupliquer', [FactureController::class, 'dupliquer'])->name('factures.dupliquer');
    Route::post('factures/calcul-apercu',       [FactureController::class, 'calculerApercu'])->name('factures.calcul');

    // Paiements
    Route::get('paiements',            [PaiementController::class, 'index'])->name('paiements.index');
    Route::post('paiements',           [PaiementController::class, 'store'])->name('paiements.store');
    Route::delete('paiements/{paiement}', [PaiementController::class, 'destroy'])->name('paiements.destroy');

    // Clients
    Route::resource('clients', ClientController::class);

    // Produits & Services
    Route::resource('produits', ProduitController::class);
    Route::get('produits/catalogue', [ProduitController::class, 'catalogue'])->name('produits.catalogue');

    // Gestion (Admin+DG)
    Route::middleware(['role:DG,Admin'])->group(function () {
        Route::resource('utilisateurs', UtilisateurController::class);
    });

    // Config (DG seulement)
    Route::middleware(['role:DG'])->group(function () {
        Route::get('/config',  [ConfigController::class, 'index'])->name('config.index');
        Route::post('/config', [ConfigController::class, 'update'])->name('config.update');
    });
});
*/
