<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id');
            $table->foreign('entreprise_id')
                  ->references('id')->on('entreprises')
                  ->onDelete('cascade');
            $table->enum('type_facture', [
                'simple','proforma','avoir','acompte','solde','recurrente'
            ]);
            $table->string('numero_facture', 40)->unique()->nullable();
            $table->enum('statut', [
                'brouillon','envoye','partiellement_paye',
                'paye','en_retard','annule','converti'
            ])->default('brouillon');
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')
                  ->references('id')->on('clients')
                  ->onDelete('restrict');
            $table->json('snapshot_client')->nullable();
            $table->date('date_emission')->nullable();
            $table->date('date_echeance')->nullable();
            $table->dateTime('envoye_le')->nullable();
            $table->char('devise', 3)->default('XOF');
            $table->decimal('taux_change', 10, 6)->nullable();
            $table->decimal('total_ht', 15, 2)->default(0);
            $table->decimal('total_remises_ht', 15, 2)->default(0);
            $table->decimal('total_taxes', 15, 2)->default(0);
            $table->json('detail_taxes')->nullable();
            $table->decimal('total_ttc', 15, 2)->default(0);
            $table->decimal('montant_paye', 15, 2)->default(0);
            $table->decimal('montant_du', 15, 2)->default(0);
            $table->unsignedBigInteger('facture_parente_id')->nullable();
            $table->foreign('facture_parente_id')
                  ->references('id')->on('factures')
                  ->onDelete('set null');
            $table->unsignedBigInteger('proforma_origine_id')->nullable();
            $table->foreign('proforma_origine_id')
                  ->references('id')->on('factures')
                  ->onDelete('set null');
            $table->unsignedBigInteger('devis_origine_id')->nullable();
            $table->unsignedBigInteger('config_recurrence_id')->nullable();
            $table->enum('type_acompte', ['pourcentage','montant_fixe'])->nullable();
            $table->decimal('valeur_acompte', 15, 4)->nullable();
            $table->text('motif_avoir')->nullable();
            $table->boolean('paiement_auto')->default(true);
            $table->text('notes')->nullable();
            $table->text('notes_internes')->nullable();
            $table->text('url_pdf')->nullable();
            $table->unsignedBigInteger('cree_par');
            $table->foreign('cree_par')
                  ->references('id')->on('utilisateurs')
                  ->onDelete('restrict');
            $table->unsignedBigInteger('modifie_par')->nullable();
            $table->foreign('modifie_par')
                  ->references('id')->on('utilisateurs')
                  ->onDelete('set null');
            $table->timestamps();
            $table->softDeletes(); // ✅ deleted_at standard (au lieu de supprime_le)
            $table->index(['entreprise_id','statut','date_echeance'], 'idx_factures_statut_ech');
            $table->index(['client_id','date_emission'], 'idx_factures_client_date');
            $table->index(['type_facture','facture_parente_id'], 'idx_factures_type_parente');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
