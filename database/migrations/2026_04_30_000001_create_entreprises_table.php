<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entreprises', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 150);
            $table->string('forme_juridique', 50)->nullable();
            $table->string('numero_fiscal', 50)->nullable();
            $table->string('numero_tva', 50)->nullable();
            $table->string('adresse_ligne1', 200)->nullable();
            $table->string('adresse_ligne2', 200)->nullable();
            $table->string('ville', 100)->nullable();
            $table->char('pays', 2)->default('NE');
            $table->string('telephone', 30)->nullable();
            $table->string('email', 150)->unique();
            $table->string('site_web', 250)->nullable();
            $table->text('logo_url')->nullable();
            $table->char('devise_defaut', 3)->default('XOF');
            $table->smallInteger('delai_paiement_jours')->unsigned()->default(30);
            $table->json('coordonnees_bancaires')->nullable();
            $table->text('pied_page_facture')->nullable();
            $table->tinyInteger('debut_exercice_fiscal')->unsigned()->default(1);
            $table->string('fuseau_horaire', 50)->default('Africa/Niamey');
            $table->enum('type_activite', ['produits','services','les_deux'])->default('les_deux');
            $table->json('notif_jours_avant_echeance')->nullable();
            $table->json('notif_jours_apres_echeance')->nullable();
            $table->boolean('notif_envoi_auto_facture')->default(true);
            $table->boolean('notif_envoi_recu_paiement')->default(true);
            $table->boolean('notif_envoi_auto_devis')->default(false);
            $table->string('notif_email_expediteur', 150)->nullable();
            $table->string('notif_nom_expediteur', 100)->nullable();
            $table->json('sequences_numerotation')->nullable();
            $table->string('whatsapp_token', 255)->nullable();
            $table->string('smtp_host', 150)->nullable();
            $table->string('smtp_port', 10)->nullable();
            $table->string('smtp_user', 150)->nullable();
            $table->string('smtp_password', 255)->nullable();
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
            // Pas de softDeletes sur entreprises
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entreprises');
    }
};
