<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // ← ajouter cette ligne

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    DB::statement("ALTER TABLE paiements DROP CONSTRAINT paiements_type_paiement_check");
    DB::statement("ALTER TABLE paiements ADD CONSTRAINT paiements_type_paiement_check 
        CHECK (type_paiement IN (
            'virement','cheque','carte','especes','prelevement',
            'mobile_money','autre','validation_comptable','envoi_automatique'
        ))");
}

public function down(): void
{
    DB::statement("ALTER TABLE paiements DROP CONSTRAINT paiements_type_paiement_check");
    DB::statement("ALTER TABLE paiements ADD CONSTRAINT paiements_type_paiement_check 
        CHECK (type_paiement IN (
            'virement','cheque','carte','especes','prelevement','mobile_money','autre'
        ))");
}
};
