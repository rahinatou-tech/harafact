<?php

namespace App\Services;

use App\Models\Facture;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    public static function genererFacturePdf(Facture $facture): string
    {
        $facture->load(['lignes', 'client', 'entreprise']);

        $entreprise      = $facture->entreprise;
        $client_snapshot = $facture->snapshot_client ?? [];

        $pdf      = Pdf::loadView('factures.pdf', compact('facture', 'entreprise', 'client_snapshot'));
        $filename = $facture->numero_facture . '.pdf';
        $chemin   = 'pdfs/' . $filename;

        // Créer le dossier si nécessaire
        if (!file_exists(storage_path('app/public/pdfs'))) {
            mkdir(storage_path('app/public/pdfs'), 0755, true);
        }

        $pdf->save(storage_path('app/public/' . $chemin));

        return $chemin;
    }
}