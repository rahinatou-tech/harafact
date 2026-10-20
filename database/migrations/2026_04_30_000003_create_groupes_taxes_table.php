<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groupes_taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id');
            $table->foreign('entreprise_id')
                  ->references('id')->on('entreprises')
                  ->onDelete('cascade');
            $table->string('nom', 100);
            $table->text('description')->nullable();
            $table->json('composantes')->nullable();
            $table->decimal('taux_total', 8, 4)->default(0);
            $table->boolean('est_defaut')->default(false);
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
            // Pas de softDeletes sur groupes_taxes
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groupes_taxes');
    }
};
