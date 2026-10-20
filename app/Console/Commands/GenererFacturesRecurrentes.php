<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ConfigRecurrence;
use App\Models\Facture;
use App\Models\LigneDocument;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenererFacturesRecurrentes extends Command
{
    protected $signature   = 'factures:generer-recurrentes';
    protected $description = 'Génère automatiquement les factures récurrentes dues aujourd\'hui';

    public function handle(): void
    {
        $aujourd_hui = Carbon::today();

        // Récupérer toutes les configs actives dont la prochaine génération est aujourd'hui ou passée
        $configs = ConfigRecurrence::where('statut', 'actif')
            ->where('prochaine_generation_le', '<=', $aujourd_hui)
            ->where(function ($q) use ($aujourd_hui) {
                $q->whereNull('date_fin')->orWhere('date_fin', '>=', $aujourd_hui);
            })
            ->get();

        $this->info("🔄 {$configs->count()} facture(s) récurrente(s) à générer.");

        foreach ($configs as $config) {
            try {
                DB::transaction(function () use ($config) {
                    $this->genererFacture($config);
                });
            } catch (\Exception $e) {
                Log::error("Erreur génération récurrence ID {$config->id} : " . $e->getMessage());
                $this->error("❌ Erreur config #{$config->id} : " . $e->getMessage());
            }
        }

        $this->info('✅ Génération terminée.');
    }

    private function genererFacture(ConfigRecurrence $config): void
    {
        // Récupérer la dernière facture de cette récurrence comme modèle
        $factureModele = Facture::where('config_recurrence_id', $config->id)
            ->latest()
            ->first();

        if (!$factureModele) {
            $this->warn("⚠️ Aucune facture modèle trouvée pour config #{$config->id}, ignorée.");
            return;
        }

        $entreprise = $factureModele->entreprise;
        $numero     = $entreprise->prochainNumero('recurrente');

        $client         = Client::find($config->client_id);
        $snapshotClient = [
            'id'            => $client->id,
            'nom_affichage' => $client->nom_affichage,
            'email'         => $client->email,
            'telephone'     => $client->telephone,
            'adresse'       => $client->adresse_ligne1,
            'ville'         => $client->ville,
            'pays'          => $client->pays,
            'numero_fiscal' => $client->numero_fiscal,
            'numero_tva'    => $client->numero_tva,
        ];

        // Créer la nouvelle facture en copiant le modèle
        // ⚠️ Le statut reste 'en_attente_validation' quel que soit envoi_auto :
        // une facture récurrente générée par le cron doit être validée par le
        // Comptable exactement comme une facture créée manuellement. L'envoi
        // automatique (PDF/email) n'est qu'une notification informative au
        // client, ce n'est pas une validation comptable.
        $nouvelleFacture = Facture::create([
            'entreprise_id'      => $config->entreprise_id,
            'type_facture'       => 'recurrente',
            'numero_facture'     => $numero,
            'statut'             => 'en_attente_validation',
            'client_id'          => $config->client_id,
            'snapshot_client'    => $snapshotClient,
            'date_emission'      => now()->toDateString(),
            'date_echeance'      => now()->addDays(30)->toDateString(),
            'devise'             => $config->devise ?? 'XOF',
            'total_ht'           => $factureModele->total_ht,
            'total_remises_ht'   => $factureModele->total_remises_ht,
            'total_taxes'        => $factureModele->total_taxes,
            'detail_taxes'       => $factureModele->detail_taxes,
            'total_ttc'          => $factureModele->total_ttc,
            'montant_paye'       => 0,
            'montant_du'         => $factureModele->total_ttc,
            'config_recurrence_id' => $config->id,
            'token_public'         => \Illuminate\Support\Str::uuid()->toString(),
            'paiement_auto'      => false,
            'notes'              => $factureModele->notes,
            'notes_internes'     => $factureModele->notes_internes,
            'cree_par'           => $config->cree_par,
        ]);

        // Copier les lignes du modèle
        foreach ($factureModele->lignes as $ligne) {
            LigneDocument::create([
                'type_document'      => 'facture',
                'document_id'        => $nouvelleFacture->id,
                'ordre_affichage'    => $ligne->ordre_affichage,
                'produit_id'         => $ligne->produit_id,
                'designation'        => $ligne->designation,
                'quantite'           => $ligne->quantite,
                'unite'              => $ligne->unite,
                'prix_unitaire_ht'   => $ligne->prix_unitaire_ht,
                'groupe_taxes_id'    => $ligne->groupe_taxes_id,
                'snapshot_taxes'     => $ligne->snapshot_taxes,
                'type_remise'        => $ligne->type_remise,
                'valeur_remise'      => $ligne->valeur_remise,
                'total_ht_ligne'     => $ligne->total_ht_ligne,
                'detail_taxes_ligne' => $ligne->detail_taxes_ligne,
                'total_taxes_ligne'  => $ligne->total_taxes_ligne,
                'total_ttc_ligne'    => $ligne->total_ttc_ligne,
            ]);
        }

        // Générer le PDF de la nouvelle facture AVANT l'envoi automatique
        // (sans ça, EnvoyerFacturePdfJob reçoit url_pdf = null et plante : voir bug du failed_jobs)
        $cheminPdf = \App\Services\PdfService::genererFacturePdf($nouvelleFacture);
        $nouvelleFacture->update(['url_pdf' => $cheminPdf]);

        // Notification automatique si activée (canal_envoi informatif client).
        // ⚠️ IMPORTANT : ceci n'est PAS une validation. La facture reste
        // 'en_attente_validation' tant que le Comptable n'a pas cliqué sur
        // "Valider" — elle continue donc d'apparaître au dashboard et dans
        // sa liste de validation, même si le PDF a déjà été envoyé au client.
        if ($config->envoi_auto && $client->email) {
            dispatch(new \App\Jobs\EnvoyerFacturePdfJob($nouvelleFacture, $client->email));
        }

        // Mettre à jour la config : prochaine date + compteur
        $nouveauTotal = $config->total_genere + 1;
        $prochaine    = $config->calculerProchaineDate();

        $config->update([
            'total_genere'            => $nouveauTotal,
            'prochaine_generation_le' => $prochaine,
        ]);

        if ($config->estTerminee()) {
            $config->update(['statut' => 'termine']);
        }

        $this->info("✅ Facture {$numero} générée (en attente de validation) pour config #{$config->id}");
    }
}