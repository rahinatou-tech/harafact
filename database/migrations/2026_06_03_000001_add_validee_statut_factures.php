<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE factures DROP CONSTRAINT factures_statut_check");
        DB::statement("ALTER TABLE factures ADD CONSTRAINT factures_statut_check CHECK (statut::text = ANY (ARRAY['brouillon','en_attente_validation','paye','partiellement_paye','en_retard','annule','converti','envoye','validee']::text[]))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE factures DROP CONSTRAINT factures_statut_check");
        DB::statement("ALTER TABLE factures ADD CONSTRAINT factures_statut_check CHECK (statut::text = ANY (ARRAY['brouillon','en_attente_validation','paye','partiellement_paye','en_retard','annule','converti','envoye']::text[]))");
    }
};