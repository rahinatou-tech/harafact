<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::table('configs_recurrence', function (Blueprint $table) {
        $table->enum('canal_envoi', ['email','whatsapp','both'])
              ->default('email')
              ->after('envoi_auto');
    });
}

public function down(): void
{
    Schema::table('configs_recurrence', function (Blueprint $table) {
        $table->dropColumn('canal_envoi');
    });
}
};
