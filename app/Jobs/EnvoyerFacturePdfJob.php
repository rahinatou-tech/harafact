<?php

namespace App\Jobs;

use App\Models\Facture;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class EnvoyerFacturePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Facture $facture,
        public string $email
    ) {}

public function handle(): void
    {
        $lienPublic = route('facture.public', $this->facture->token_public);

        // Chemin absolu du PDF déjà généré (storage/app/public/pdfs/xxx.pdf)
        $cheminPdf = $this->facture->url_pdf
            ? storage_path('app/public/' . $this->facture->url_pdf)
            : null;

        Mail::send('emails.facture-lien', [
            'facture'    => $this->facture,
            'lienPublic' => $lienPublic,
        ], function ($m) use ($cheminPdf) {
            $m->to($this->email)
              ->subject('Votre facture ' . $this->facture->numero_facture . ' — HaRaFact');

            // Joindre le PDF directement : le client peut l'ouvrir sans accéder au serveur
            // is_file() (et non file_exists()) car file_exists() renvoie true même pour un dossier
            if ($cheminPdf && is_file($cheminPdf)) {
                $m->attach($cheminPdf, [
                    'as'   => $this->facture->numero_facture . '.pdf',
                    'mime' => 'application/pdf',
                ]);
            }
        });
    }
}