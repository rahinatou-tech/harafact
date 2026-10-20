<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter colonnes aux factures pour le workflow
        Schema::table('factures', function (Blueprint $table) {
            if (!Schema::hasColumn('factures', 'statut_validation')) {
                $table->enum('statut_validation', ['brouillon', 'en_attente_validation', 'validee', 'rejetee'])
                      ->default('brouillon')
                      ->after('statut');
            }
            if (!Schema::hasColumn('factures', 'soumise_le')) {
                $table->dateTime('soumise_le')->nullable()->after('envoye_le');
            }
            if (!Schema::hasColumn('factures', 'soumise_par')) {
                $table->unsignedBigInteger('soumise_par')->nullable()->after('soumise_le');
                $table->foreign('soumise_par')
                      ->references('id')->on('utilisateurs')
                      ->onDelete('set null');
            }
            if (!Schema::hasColumn('factures', 'rejet_raison')) {
                $table->text('rejet_raison')->nullable()->after('soumise_par');
            }
        });

        // Mettre à jour les rôles disponibles dans utilisateurs
        // (On ne modifie pas la table, juste le commentaire pour la doc)
        // Les rôles seront : DG, Employé, Comptable, Admin
    }

    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->dropColumn(['statut_validation', 'soumise_le', 'rejet_raison']);
            $table->dropForeign(['soumise_par']);
            $table->dropColumn('soumise_par');
        });
    }
};