<?php

namespace App\Http\Controllers;

use App\Models\Facture;

class FacturePublicController extends Controller
{
    public function show(string $token)
    {
        $facture = Facture::where('token_public', $token)
            ->with(['lignes', 'client', 'entreprise'])
            ->firstOrFail();

        return view('factures.public', compact('facture'));
    }
}