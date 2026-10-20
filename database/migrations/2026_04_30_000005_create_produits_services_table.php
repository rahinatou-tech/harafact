<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produits_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id');
            $table->foreign('entreprise_id')
                  ->references('id')->on('entreprises')
                  ->onDelete('cascade');
            $table->string('reference', 60)->nullable();
            $table->string('nom', 200);
            $table->text('description')->nullable();
            $table->enum('type', ['produit','service'])->default('service');
            $table->string('unite', 30)->nullable();
            $table->decimal('prix_unitaire_ht', 15, 4);
            $table->unsignedBigInteger('groupe_taxes_id')->nullable();
            $table->foreign('groupe_taxes_id')
                  ->references('id')->on('groupes_taxes')
                  ->onDelete('set null');
            $table->string('code_comptable', 20)->nullable();
            $table->integer('stock')->nullable();
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
        Schema::dropIfExists('produits_services');
    }
};
