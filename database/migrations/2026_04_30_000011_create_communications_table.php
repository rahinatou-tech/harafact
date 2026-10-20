<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ⚡ FUSION : journal_emails + relances_paiement + notifications_push
        // La colonne type_comm distingue les 3 types
        Schema::create('communications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id');
            $table->foreign('entreprise_id')
                  ->references('id')->on('entreprises')
                  ->onDelete('cascade');
            // ⚡ Distingue email / relance / push
            $table->enum('type_comm', ['email','relance','push'])->default('email');
            $table->unsignedBigInteger('facture_id')->nullable();
            $table->foreign('facture_id')
                  ->references('id')->on('factures')
                  ->onDelete('cascade');
            $table->unsignedBigInteger('devis_id')->nullable();
            $table->foreign('devis_id')
                  ->references('id')->on('devis')
                  ->onDelete('cascade');
            $table->unsignedBigInteger('utilisateur_id')->nullable();
            $table->foreign('utilisateur_id')
                  ->references('id')->on('utilisateurs')
                  ->onDelete('set null');
            // Statut unifié
            $table->enum('statut', [
                'planifie','en_attente','envoye','delivre',
                'echoue','rebondi','ouvert','annule'
            ])->default('en_attente');
            $table->dateTime('planifie_le')->nullable();
            $table->dateTime('envoye_le')->nullable();
            // ── Champs EMAIL ──────────────────────────────────
            $table->string('email_destinataire', 150)->nullable();
            $table->string('sujet', 255)->nullable();
            $table->string('template', 80)->nullable();
            $table->string('id_message_fournisseur', 150)->nullable();
            $table->dateTime('ouvert_le')->nullable();
            // ── Champs RELANCE ────────────────────────────────
            $table->enum('type_relance', [
                'avant_echeance','jour_echeance','apres_echeance'
            ])->nullable();
            $table->smallInteger('decalage_jours')->nullable();
            $table->enum('canal_relance', ['email','sms','push','email_et_push'])->nullable();
            // ── Champs PUSH (Flutter) ─────────────────────────
            $table->string('titre_push', 150)->nullable();
            $table->text('corps_push')->nullable();
            $table->enum('type_push', [
                'facture_en_retard','paiement_recu','facture_envoyee',
                'devis_accepte','devis_refuse','relance'
            ])->nullable();
            $table->string('id_message_fcm', 150)->nullable();
            // Erreur
            $table->text('message_erreur')->nullable();
            $table->timestamps();
            // Index
            $table->index(['type_comm','facture_id','statut'], 'idx_comm_type_facture');
            $table->index(['entreprise_id','statut','planifie_le'], 'idx_comm_statut_planif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communications');
    }
};
