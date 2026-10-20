<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE journal_audit DROP CONSTRAINT journal_audit_action_check");
        DB::statement("ALTER TABLE journal_audit ADD CONSTRAINT journal_audit_action_check 
            CHECK (action IN (
                'creation','modification','suppression','envoi','paiement',
                'annulation','conversion','generation','export','connexion',
                'deconnexion','validation','rejet'
            ))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE journal_audit DROP CONSTRAINT journal_audit_action_check");
        DB::statement("ALTER TABLE journal_audit ADD CONSTRAINT journal_audit_action_check 
            CHECK (action IN (
                'creation','modification','suppression','envoi','paiement',
                'annulation','conversion','generation','export','connexion',
                'deconnexion'
            ))");
    }
};