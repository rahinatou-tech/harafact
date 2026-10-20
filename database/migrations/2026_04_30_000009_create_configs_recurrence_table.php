<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configs_recurrence', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id');
            $table->foreign('entreprise_id')
                  ->references('id')->on('entreprises')
                  ->onDelete('cascade');
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')
                  ->references('id')->on('clients')
                  ->onDelete('restrict');
            $table->string('nom', 150);
            $table->enum('frequence', [
                'quotidienne','hebdomadaire','mensuelle',
                'trimestrielle','semestrielle','annuelle'
            ])->default('mensuelle');
            $table->tinyInteger('intervalle')->unsigned()->default(1);
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->integer('max_occurrences')->unsigned()->nullable();
            $table->integer('total_genere')->unsigned()->default(0);
            $table->date('prochaine_generation_le')->nullable();
            $table->tinyInteger('jour_du_mois')->unsigned()->nullable();
            $table->boolean('envoi_auto')->default(true);
            $table->char('devise', 3)->default('XOF');
            $table->enum('statut', ['actif','pause','termine','annule'])->default('actif');
            $table->unsignedBigInteger('cree_par');
            $table->foreign('cree_par')
                  ->references('id')->on('utilisateurs')
                  ->onDelete('restrict');
            $table->timestamps();
            $table->index(['entreprise_id','statut','prochaine_generation_le'], 'idx_configs_rec_statut');
        });

        // Maintenant qu'on a configs_recurrence, on ajoute la FK factures → configs_recurrence
        Schema::table('factures', function (Blueprint $table) {
            $table->foreign('config_recurrence_id')
                  ->references('id')->on('configs_recurrence')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->dropForeign(['config_recurrence_id']);
        });
        Schema::dropIfExists('configs_recurrence');
    }
};
