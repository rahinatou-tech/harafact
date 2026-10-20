<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ⚡ FUSION paiements + moyens_paiement
        // Le moyen de paiement est stocké directement sur la ligne (ENUM + JSON)
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id');
            $table->foreign('entreprise_id')
                  ->references('id')->on('entreprises')
                  ->onDelete('cascade');
            $table->unsignedBigInteger('facture_id');
            $table->foreign('facture_id')
                  ->references('id')->on('factures')
                  ->onDelete('cascade');
            $table->date('date_paiement');
            $table->decimal('montant', 15, 2);
            $table->char('devise', 3)->default('XOF');
            $table->decimal('taux_change', 10, 6)->nullable();
            $table->decimal('montant_devise_principale', 15, 2)->nullable();
            // ⚡ Fusionné depuis moyens_paiement
            $table->enum('type_paiement', [
                'virement','cheque','carte','especes',
                'prelevement','mobile_money','autre'
            ])->default('especes');
            $table->string('libelle_paiement', 80)->nullable();
            $table->json('details_paiement')->nullable();
            $table->string('reference_externe', 100)->nullable();
            $table->text('notes')->nullable();
            // CRUCIAL : distingue auto (simple/proforma/avoir/solde) vs manuel (acompte/recurrente)
            $table->boolean('est_auto')->default(false);
            $table->unsignedBigInteger('saisie_par')->nullable();
            $table->foreign('saisie_par')
                  ->references('id')->on('utilisateurs')
                  ->onDelete('set null');
            $table->timestamps();
            $table->index(['facture_id','date_paiement'], 'idx_paiem_facture_date');
            $table->index(['entreprise_id','date_paiement'], 'idx_paiem_entreprise_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
