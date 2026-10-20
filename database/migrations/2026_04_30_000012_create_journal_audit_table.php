<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ★ APPEND-ONLY — JAMAIS de UPDATE ni DELETE sur cette table
        Schema::create('journal_audit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id');
            $table->foreign('entreprise_id')
                  ->references('id')->on('entreprises')
                  ->onDelete('cascade');
            // NULL = action système / Laravel Scheduler / CRON
            $table->unsignedBigInteger('utilisateur_id')->nullable();
            $table->foreign('utilisateur_id')
                  ->references('id')->on('utilisateurs')
                  ->onDelete('set null');
            $table->string('type_entite', 60);
            $table->unsignedBigInteger('entite_id');
            $table->enum('action', [
                'creation','modification','suppression',
                'envoi','paiement','annulation','conversion',
                'generation','export','connexion','deconnexion'
            ]);
            $table->enum('source', ['web','mobile','cron','api'])->default('web');
            $table->json('anciennes_valeurs')->nullable();
            $table->json('nouvelles_valeurs')->nullable();
            $table->string('adresse_ip', 45)->nullable();
            $table->text('agent_utilisateur')->nullable();
            // Append-only : on utilise cree_le uniquement (pas de updated_at)
            $table->timestamp('cree_le')->useCurrent();
            // Index
            $table->index(['entreprise_id','type_entite','entite_id'], 'idx_audit_entite');
            $table->index(['entreprise_id','utilisateur_id','cree_le'], 'idx_audit_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_audit');
    }
};
