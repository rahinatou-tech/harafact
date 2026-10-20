<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Génération automatique des factures récurrentes — chaque jour à 6h
Schedule::command('factures:generer-recurrentes')->dailyAt('15:00');