<?php

namespace App\Jobs;

use App\Models\Facture;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnvoyerFactureWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Facture $facture,
        public string $telephone
    ) {}

    public function handle(): void
    {
        // Intégration WhatsApp à connecter plus tard (Twilio, Meta API, etc.)
        Log::info("WhatsApp facture {$this->facture->numero_facture} → {$this->telephone}");
    }
}