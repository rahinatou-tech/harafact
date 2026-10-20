<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->unsignedBigInteger('valide_par')->nullable()->after('cree_par');
            $table->timestamp('valide_le')->nullable()->after('valide_par');
            $table->string('canal_envoi')->nullable()->after('valide_le');
            $table->timestamp('envoye_client_le')->nullable()->after('canal_envoi');
        });
    }

    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->dropColumn(['valide_par', 'valide_le', 'canal_envoi', 'envoye_client_le']);
        });
    }
};