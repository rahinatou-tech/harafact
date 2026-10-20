<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilisateurs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id');
            $table->foreign('entreprise_id')
                  ->references('id')->on('entreprises')
                  ->onDelete('cascade');
            $table->string('nom', 80);
            $table->string('prenom', 80);
            $table->string('email', 150)->unique();
            $table->string('mot_de_passe', 255);
            $table->enum('role', ['DG','Admin','Comptable','Employe'])->default('Comptable');
            $table->boolean('est_actif')->default(true);
            $table->boolean('double_auth_active')->default(false);
            $table->string('double_auth_secret', 100)->nullable();
            $table->string('token_reinit', 255)->nullable();
            $table->dateTime('token_reinit_expire_le')->nullable();
            $table->string('refresh_token_hash', 512)->nullable();
            $table->dateTime('refresh_token_expire_le')->nullable();
            $table->text('token_fcm')->nullable();
            $table->string('nom_appareil', 150)->nullable();
            $table->dateTime('refresh_token_revoque_le')->nullable();
            $table->dateTime('derniere_connexion_le')->nullable();
            $table->rememberToken();
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
        Schema::dropIfExists('utilisateurs');
    }
};
