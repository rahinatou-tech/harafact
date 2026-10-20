<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ⚡ FUSION lignes_devis + lignes_factures en une seule table
        // La colonne type_document distingue les deux
        Schema::create('lignes_documents', function (Blueprint $table) {
            $table->id();
            $table->enum('type_document', ['devis','facture']);
            $table->unsignedBigInteger('document_id');
            // Pas de FK directe car pointe vers 2 tables différentes
            // L'intégrité est gérée par le code applicatif
            $table->tinyInteger('ordre_affichage')->unsigned()->default(1);
            $table->unsignedBigInteger('produit_id')->nullable();
            $table->foreign('produit_id')
                  ->references('id')->on('produits_services')
                  ->onDelete('set null');
            $table->text('designation');
            $table->decimal('quantite', 12, 4)->default(1);
            $table->string('unite', 30)->nullable();
            $table->decimal('prix_unitaire_ht', 15, 4);
            $table->unsignedBigInteger('groupe_taxes_id')->nullable();
            $table->foreign('groupe_taxes_id')
                  ->references('id')->on('groupes_taxes')
                  ->onDelete('set null');
            // Snapshot figé des taxes à la création (immutable comptablement)
            $table->json('snapshot_taxes')->nullable();
            // Remise
            $table->enum('type_remise', ['pourcentage','montant_fixe'])->nullable();
            $table->decimal('valeur_remise', 15, 4)->default(0);
            // Totaux calculés
            $table->decimal('total_ht_ligne', 15, 2)->default(0);
            $table->json('detail_taxes_ligne')->nullable();
            $table->decimal('total_taxes_ligne', 15, 2)->default(0);
            $table->decimal('total_ttc_ligne', 15, 2)->default(0);
            $table->timestamps();
            // Index composite pour accès rapide
            $table->index(['type_document','document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_documents');
    }
};
