<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Facture;
use App\Models\Client;
use App\Models\Paiement;
use App\Models\Devis;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $eid = auth()->user()->entreprise_id;

        // ── KPIs ─────────────────────────────────────────────
        $stats = [
            'ca_mois' => Facture::where('entreprise_id', $eid)
                ->whereIn('statut', ['paye', 'partiellement_paye'])
                ->whereMonth('date_emission', now()->month)
                ->whereYear('date_emission',  now()->year)
                ->sum('montant_paye'),

            'factures_emises_mois' => Facture::where('entreprise_id', $eid)
                ->whereMonth('date_emission', now()->month)
                ->whereYear('date_emission',  now()->year)
                ->count(),

            'total_impayes' => Facture::where('entreprise_id', $eid)
                ->whereIn('statut', ['envoye', 'partiellement_paye', 'en_retard'])
                ->sum('montant_du'),

            'factures_retard' => Facture::where('entreprise_id', $eid)
                ->where('statut', 'en_retard')
                ->count(),

            'clients_actifs' => Client::where('entreprise_id', $eid)
                ->where('est_actif', true)
                ->count(),

            'devis_en_attente' => Devis::where('entreprise_id', $eid)
                ->whereIn('statut', ['envoye'])
                ->count(),

            'taux_recouvrement' => $this->calculerTauxRecouvrement($eid),

            'ca_mois_precedent' => Facture::where('entreprise_id', $eid)
                ->whereIn('statut', ['paye', 'partiellement_paye'])
                ->whereMonth('date_emission', now()->subMonth()->month)
                ->whereYear('date_emission',  now()->subMonth()->year)
                ->sum('montant_paye'),
        ];

        // ── Dernières factures ────────────────────────────────
        $dernieres_factures = Facture::with('client')
            ->where('entreprise_id', $eid)
            ->latest()
            ->take(8)
            ->get();

        // ── Factures à suivre (acompte + récurrente) ──────────
        $a_suivre = Facture::with('client')
            ->where('entreprise_id', $eid)
            ->aSuivre()
            ->orderBy('date_echeance')
            ->take(5)
            ->get();

        // ── Graphique CA 12 mois ──────────────────────────────
        $graphique = [];
        for ($i = 11; $i >= 0; $i--) {
            $mois = now()->subMonths($i);
            $graphique[] = [
                'label'   => $mois->translatedFormat('M Y'),
                'mois_n'  => $mois->month,
                'annee_n' => $mois->year,
                'ca'      => (float) Facture::where('entreprise_id', $eid)
                    ->whereIn('statut', ['paye', 'partiellement_paye'])
                    ->whereMonth('date_emission', $mois->month)
                    ->whereYear('date_emission',  $mois->year)
                    ->sum('montant_paye'),
            ];
        }

        // ── Répartition modes de paiement (mois courant) ─────
        $modes = Paiement::where('entreprise_id', $eid)
            ->whereMonth('created_at', now()->month)
            ->selectRaw('type_paiement, SUM(montant) as total')
            ->groupBy('type_paiement')
            ->orderByDesc('total')
            ->get();

        $totalModes  = $modes->sum('total');
        $modesFormat = $modes->map(fn($m) => [
            'label'   => $this->labelMode($m->type_paiement),
            'total'   => $m->total,
            'pct'     => $totalModes > 0 ? round(($m->total / $totalModes) * 100) : 0,
        ]);

        // ── Activité récente (journal) ────────────────────────
        $activite = \App\Models\JournalAudit::where('entreprise_id', $eid)
            ->with('utilisateur')
            ->latest('cree_le')
            ->take(8)
            ->get();

        return view('dashboard.index', compact(
            'stats', 'dernieres_factures', 'a_suivre',
            'graphique', 'modesFormat', 'activite'
        ));
    }

    private function calculerTauxRecouvrement(int $eid): float
    {
        $totalFacture = Facture::where('entreprise_id', $eid)
            ->whereMonth('date_emission', now()->month)
            ->sum('total_ttc');

        if ($totalFacture <= 0) return 0;

        $totalPaye = Facture::where('entreprise_id', $eid)
            ->whereMonth('date_emission', now()->month)
            ->sum('montant_paye');

        return round(($totalPaye / $totalFacture) * 100, 1);
    }

    private function labelMode(string $mode): string
    {
        return match($mode) {
            'mobile_money' => 'Mobile Money',
            'especes'      => 'Espèces',
            'virement'     => 'Virement',
            'cheque'       => 'Chèque',
            'carte'        => 'Carte',
            default        => ucfirst($mode),
        };
    }

  
//  FICHIER 1 : app/Http/Controllers/DashboardController.php
//  Ajouter la méthode apiIndex() à la fin du fichier
// ════════════════════════════════════════════════════════
// Copier-coller cette méthode AVANT la dernière accolade }
// du DashboardController existant
 
    public function apiIndex()
{
    try {
    $eid = auth()->user()->entreprise_id;
 $user = auth()->user();

    // ── KPIs ─────────────────────────────────────────────
    $caMois = (float) Facture::where('entreprise_id', $eid)
        ->whereIn('statut', ['paye', 'partiellement_paye'])
        ->whereMonth('date_emission', now()->month)
        ->whereYear('date_emission',  now()->year)
        ->sum('montant_paye');
 
    $caMoisPrecedent = (float) Facture::where('entreprise_id', $eid)
        ->whereIn('statut', ['paye', 'partiellement_paye'])
        ->whereMonth('date_emission', now()->subMonth()->month)
        ->whereYear('date_emission',  now()->subMonth()->year)
        ->sum('montant_paye');
 
    $stats = [
        'ca_mois'              => $caMois,
        'ca_mois_precedent'    => $caMoisPrecedent,
        'evolution_ca'         => $caMoisPrecedent > 0
            ? round(($caMois - $caMoisPrecedent) / $caMoisPrecedent * 100, 1)
            : 0,
        'factures_emises_mois' => Facture::where('entreprise_id', $eid)
            ->whereMonth('date_emission', now()->month)
            ->whereYear('date_emission',  now()->year)
            ->count(),
        'total_impayes'        => (float) Facture::where('entreprise_id', $eid)
            ->whereIn('statut', ['envoye', 'partiellement_paye', 'en_retard'])
            ->sum('montant_du'),
        'factures_retard'      => Facture::where('entreprise_id', $eid)
            ->where('statut', 'en_retard')->count(),
        'en_attente_validation'=> Facture::where('entreprise_id', $eid)
            ->where('statut', 'en_attente_validation')->count(),
        'validees_a_envoyer'   => Facture::where('entreprise_id', $eid)
            ->where('statut', 'validee')->count(),
        'clients_actifs'       => Client::where('entreprise_id', $eid)
            ->where('est_actif', true)->count(),
        'devis_en_attente'     => Devis::where('entreprise_id', $eid)
            ->whereIn('statut', ['envoye'])->count(),
        'taux_recouvrement'    => $this->calculerTauxRecouvrement($eid),
    ];
 
    // ── Graphique CA 12 mois ──────────────────────────────
    $graphique = [];
    for ($i = 11; $i >= 0; $i--) {
        $mois = now()->subMonths($i);
        $graphique[] = [
            'label' => $mois->format('M y'),   // "Jan 25"
            'mois'  => $mois->month,
            'annee' => $mois->year,
            'ca'    => (float) Facture::where('entreprise_id', $eid)
                ->whereIn('statut', ['paye', 'partiellement_paye'])
                ->whereMonth('date_emission', $mois->month)
                ->whereYear('date_emission',  $mois->year)
                ->sum('montant_paye'),
        ];
    }
 
    // ── Répartition modes de paiement (mois courant) ─────
    $modes = Paiement::where('entreprise_id', $eid)
        ->whereMonth('created_at', now()->month)
        ->selectRaw('type_paiement, SUM(montant) as total')
        ->groupBy('type_paiement')
        ->orderByDesc('total')
        ->get();
 
    $totalModes  = (float) $modes->sum('total');
    $modesFormat = $modes->map(fn($m) => [
        'label' => $this->labelMode($m->type_paiement),
        'type'  => $m->type_paiement,
        'total' => (float) $m->total,
        'pct'   => $totalModes > 0 ? round(($m->total / $totalModes) * 100) : 0,
    ])->values();
 
    // ── Dernières factures (8) ────────────────────────────
    $dernieres = Facture::with('client')
        ->where('entreprise_id', $eid)
        ->latest()
        ->take(8)
        ->get()
        ->map(fn($f) => [
            'id'             => $f->id,
            'numero_facture' => $f->numero_facture,
            'type_facture'   => $f->type_facture,
            'statut'         => $f->statut,
            'total_ttc'      => (float) $f->total_ttc,
            'montant_du'     => (float) $f->montant_du,
            'montant_paye'   => (float) $f->montant_paye,
            'date_emission'  => $f->date_emission?->toDateString(),
            'date_echeance'  => $f->date_echeance?->toDateString(),
            'client_id'      => $f->client_id,
            'client'         => $f->client ? [
                'id'            => $f->client->id,
                'nom_affichage' => $f->client->nom_affichage,
            ] : null,
        ]);
 
    // ── Factures à suivre ─────────────────────────────────
    // ✅ N'appeler les paiements que si Comptable
    $aSuivre = [];
    if ($user->isComptable()) {
        $aSuivre = Facture::with('client')
            ->where('entreprise_id', $eid)
            ->aSuivre()
            ->take(5)
            ->get();
    }

   return response()->json([
            'stats'              => $stats,
            'dernieres_factures' => $dernieres ?? [],
            'graphique'          => $graphique,
            'a_suivre'           => $aSuivre ?? [],
        ]);
    } catch (\Throwable $e) {
        \Log::error('Dashboard API Error: ' . $e->getMessage());
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}   
//  FIN DashboardController
// ════════════════════════════════════════════════════════
 
}
