<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id');
            $table->foreign('entreprise_id')
                  ->references('id')->on('entreprises')
                  ->onDelete('cascade');
            $table->enum('type_client', ['particulier','entreprise'])->default('entreprise');
            $table->string('code_client', 30)->unique();
            $table->string('raison_sociale', 150)->nullable();
            $table->string('prenom', 80)->nullable();
            $table->string('nom', 80)->nullable();
            $table->string('numero_fiscal', 50)->nullable();
            $table->string('numero_tva', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('telephone', 30)->nullable();
            $table->string('adresse_ligne1', 200)->nullable();
            $table->string('adresse_ligne2', 200)->nullable();
            $table->string('ville', 100)->nullable();
            $table->char('pays', 2)->default('NE');
            $table->char('devise', 3)->nullable();
            $table->smallInteger('delai_paiement_jours')->unsigned()->nullable();
            $table->unsignedBigInteger('groupe_taxes_id')->nullable();
            $table->foreign('groupe_taxes_id')
                  ->references('id')->on('groupes_taxes')
                  ->onDelete('set null');
            $table->decimal('plafond_credit', 15, 2)->nullable();
            $table->tinyInteger('score_fiabilite')->unsigned()->nullable();
            $table->decimal('delai_paiement_moyen', 5, 1)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('est_actif')->default(true);
            $table->unsignedBigInteger('cree_par')->nullable();
            $table->foreign('cree_par')
                  ->references('id')->on('utilisateurs')
                  ->onDelete('set null');
            $table->timestamps();
            $table->softDeletes(); // ✅ deleted_at standard (au lieu de supprime_le)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
