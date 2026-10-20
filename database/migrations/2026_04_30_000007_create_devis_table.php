<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id');
            $table->foreign('entreprise_id')
                  ->references('id')->on('entreprises')
                  ->onDelete('cascade');
            $table->string('numero_devis', 40)->unique()->nullable();
            $table->enum('statut', [
                'brouillon','envoye','accepte','refuse','expire','converti'
            ])->default('brouillon');
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')
                  ->references('id')->on('clients')
                  ->onDelete('restrict');
            $table->json('snapshot_client')->nullable();
            $table->date('date_emission')->nullable();
            $table->date('date_validite')->nullable();
            $table->char('devise', 3)->default('XOF');
            $table->decimal('taux_change', 10, 6)->nullable();
            $table->decimal('total_ht', 15, 2)->default(0);
            $table->decimal('total_remises_ht', 15, 2)->default(0);
            $table->decimal('total_taxes', 15, 2)->default(0);
            $table->json('detail_taxes')->nullable();
            $table->decimal('total_ttc', 15, 2)->default(0);
            $table->enum('converti_en', ['simple','proforma','acompte'])->nullable();
            $table->unsignedBigInteger('facture_convertie_id')->nullable();
            $table->foreign('facture_convertie_id')
                  ->references('id')->on('factures')
                  ->onDelete('set null');
            $table->text('notes')->nullable();
            $table->text('notes_internes')->nullable();
            $table->text('url_pdf')->nullable();
            $table->unsignedBigInteger('cree_par');
            $table->foreign('cree_par')
                  ->references('id')->on('utilisateurs')
                  ->onDelete('restrict');
            $table->dateTime('envoye_le')->nullable();
            $table->dateTime('repondu_le')->nullable();
            $table->timestamps();
            $table->softDeletes(); // ✅ ajout deleted_at standard
            $table->index(['entreprise_id','statut','date_validite'], 'idx_devis_statut_valid');
        });

        Schema::table('factures', function (Blueprint $table) {
            $table->foreign('devis_origine_id')
                  ->references('id')->on('devis')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->dropForeign(['devis_origine_id']);
        });
        Schema::dropIfExists('devis');
    }
};
