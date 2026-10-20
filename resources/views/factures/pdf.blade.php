<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $facture->numero_facture }}</title>
    <style>
        * { margin: 0; padding: 0; }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
        }

        .logo-section {
            flex: 1;
        }

        .logo-section h1 {
            color: #007bff;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .logo-section p {
            font-size: 10px;
            color: #666;
        }

        .type-facture {
            flex: 1;
            text-align: right;
        }

        .type-facture .label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            font-weight: bold;
        }

        .type-facture .value {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
            text-transform: uppercase;
        }

        /* Infos entreprise */
        .entreprise-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 40px;
        }

        .entreprise-info .section {
            flex: 1;
        }

        .entreprise-info h3 {
            font-size: 11px;
            text-transform: uppercase;
            color: #333;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .entreprise-info p {
            font-size: 10px;
            line-height: 1.6;
            color: #666;
        }

        /* Données facture */
        .facture-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 40px;
        }

        .facture-details .section {
            flex: 1;
        }

        .facture-details h3 {
            font-size: 11px;
            text-transform: uppercase;
            color: #333;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .facture-details table {
            width: 100%;
            font-size: 10px;
            border-collapse: collapse;
        }

        .facture-details table tr {
            border-bottom: 1px solid #eee;
        }

        .facture-details table td {
            padding: 4px 0;
            line-height: 1.4;
        }

        .facture-details table .label {
            font-weight: bold;
            color: #333;
            width: 40%;
        }

        .facture-details table .value {
            text-align: right;
            color: #666;
        }

        /* Tableau lignes */
        .lignes-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .lignes-table thead {
            background-color: #007bff;
            color: white;
        }

        .lignes-table th {
            padding: 8px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
        }

        .lignes-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
            font-size: 10px;
        }

        .lignes-table .qte {
            text-align: center;
            width: 60px;
        }

        .lignes-table .prix {
            text-align: right;
            width: 80px;
        }

        .lignes-table .montant {
            text-align: right;
            width: 90px;
            font-weight: bold;
        }

        .lignes-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Totaux */
        .totaux {
            width: 100%;
            margin-bottom: 20px;
        }

        .totaux table {
            width: 100%;
            border-collapse: collapse;
            margin-left: auto;
            width: 350px;
        }

        .totaux td {
            padding: 6px 10px;
            font-size: 10px;
            border-bottom: 1px solid #eee;
        }

        .totaux .label {
            text-align: right;
            font-weight: bold;
            width: 60%;
        }

        .totaux .value {
            text-align: right;
            width: 40%;
        }

        .totaux .total-ht {
            background-color: #f9f9f9;
        }

        .totaux .total-ttc {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            font-size: 12px;
        }

        /* Notes */
        .notes {
            margin-top: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border-left: 3px solid #007bff;
            font-size: 9px;
            line-height: 1.6;
        }

        .notes h4 {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        /* Pied de page */
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 2px solid #eee;
            text-align: center;
            font-size: 9px;
            color: #999;
        }

        .footer p {
            margin: 3px 0;
        }

        /* Page break */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        
        <!-- Header -->
        <div class="header">
            <div class="logo-section">
                <h1>{{ $entreprise->nom }}</h1>
                <p>{{ $entreprise->adresse_ligne1 }}, {{ $entreprise->ville }}, {{ $entreprise->pays }}</p>
            </div>
            <div class="type-facture">
                <div class="label">Type</div>
                <div class="value">{{ ucfirst(str_replace('_', ' ', $facture->type_facture)) }}</div>
                <div style="margin-top: 10px; font-size: 14px; font-weight: bold;">
                    {{ $facture->numero_facture }}
                </div>
            </div>
        </div>

        <!-- Infos entreprise et client -->
        <div class="entreprise-info">
            <div class="section">
                <h3>Entreprise</h3>
                <p>
                    <strong>{{ $entreprise->nom }}</strong><br>
                    @if($entreprise->numero_fiscal)
                        N° Fiscal: {{ $entreprise->numero_fiscal }}<br>
                    @endif
                    @if($entreprise->numero_tva)
                        N° TVA: {{ $entreprise->numero_tva }}<br>
                    @endif
                    {{ $entreprise->adresse_ligne1 }}<br>
                    @if($entreprise->adresse_ligne2)
                        {{ $entreprise->adresse_ligne2 }}<br>
                    @endif
                    {{ $entreprise->code_postal }} {{ $entreprise->ville }}<br>
                    {{ $entreprise->pays }}
                </p>
            </div>

            <div class="section">
                <h3>Client</h3>
                <p>
                    <strong>{{ $client_snapshot['nom_affichage'] ?? $facture->client->nom_affichage }}</strong><br>
                    @if($client_snapshot['numero_tva'] ?? $facture->client->numero_tva)
                        N° TVA: {{ $client_snapshot['numero_tva'] ?? $facture->client->numero_tva }}<br>
                    @endif
                    {{ $client_snapshot['adresse'] ?? $facture->client->adresse_ligne1 }}<br>
                    {{ $client_snapshot['ville'] ?? $facture->client->ville }}<br>
                    {{ $client_snapshot['pays'] ?? $facture->client->pays }}<br>
                    <br>
                    @if($client_snapshot['email'] ?? $facture->client->email)
                        Email: {{ $client_snapshot['email'] ?? $facture->client->email }}<br>
                    @endif
                    @if($client_snapshot['telephone'] ?? $facture->client->telephone)
                        Tél: {{ $client_snapshot['telephone'] ?? $facture->client->telephone }}<br>
                    @endif
                </p>
            </div>
        </div>

        <!-- Dates et infos -->
        <div class="facture-details">
            <div class="section">
                <h3>Dates</h3>
                <table>
                    <tr>
                        <td class="label">Émise le</td>
                        <td class="value">{{ $facture->date_emission->format('d/m/Y') }}</td>
                    </tr>
                    @if($facture->date_echeance)
                        <tr>
                            <td class="label">Échéance</td>
                            <td class="value">{{ $facture->date_echeance->format('d/m/Y') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label">Créée le</td>
                        <td class="value">{{ $facture->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>
            </div>

            <div class="section">
                <h3>Informations</h3>
                <table>
                    <tr>
                        <td class="label">Type de document</td>
                        <td class="value">{{ ucfirst(str_replace('_', ' ', $facture->type_facture)) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Devise</td>
                        <td class="value">{{ $facture->devise }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Tableau des lignes -->
        <table class="lignes-table">
            <thead>
                <tr>
                    <th style="flex: 1;">Désignation</th>
                    <th class="qte">Qté</th>
                    <th class="prix">Prix HT</th>
                    <th class="prix">Taxes</th>
                    <th class="montant">Total TTC</th>
                </tr>
            </thead>
            <tbody>
                @foreach($facture->lignes as $ligne)
                    <tr>
                        <td>
                            {{ $ligne->designation }}
                            @if($ligne->unite)
                                <br><small style="color: #999;">{{ $ligne->unite }}</small>
                            @endif
                        </td>
                        <td class="qte">{{ number_format($ligne->quantite, 2) }}</td>
                        <td class="prix">{{ number_format($ligne->prix_unitaire_ht, 2) }} {{ $facture->devise }}</td>
                        <td class="prix">{{ number_format($ligne->total_taxes_ligne, 2) }} {{ $facture->devise }}</td>
                        <td class="montant">{{ number_format($ligne->total_ttc_ligne, 2) }} {{ $facture->devise }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totaux -->
        <div class="totaux">
            <table>
                <tr class="total-ht">
                    <td class="label">Total HT</td>
                    <td class="value">{{ number_format($facture->total_ht, 2) }} {{ $facture->devise }}</td>
                </tr>
                @if($facture->total_remises_ht > 0)
                    <tr>
                        <td class="label">Remises</td>
                        <td class="value">-{{ number_format($facture->total_remises_ht, 2) }} {{ $facture->devise }}</td>
                    </tr>
                @endif
                @if($facture->detail_taxes && count($facture->detail_taxes) > 0)
                    @foreach($facture->detail_taxes as $taxe)
                        <tr>
                            <td class="label">{{ $taxe['nom'] ?? $taxe['code'] }} ({{ $taxe['taux'] ?? 0 }}%)</td>
                            <td class="value">{{ number_format($taxe['montant'] ?? 0, 2) }} {{ $facture->devise }}</td>
                        </tr>
                    @endforeach
                @endif
                <tr class="total-ttc">
                    <td class="label">TOTAL TTC</td>
                    <td class="value">{{ number_format($facture->total_ttc, 2) }} {{ $facture->devise }}</td>
                </tr>
                @if($facture->montant_paye > 0)
                    <tr>
                        <td class="label">Montant payé</td>
                        <td class="value">{{ number_format($facture->montant_paye, 2) }} {{ $facture->devise }}</td>
                    </tr>
                    <tr class="total-ht">
                        <td class="label">Solde dû</td>
                        <td class="value">{{ number_format($facture->montant_du, 2) }} {{ $facture->devise }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <!-- Notes -->
        @if($facture->notes || $facture->notes_internes)
            <div class="notes">
                @if($facture->notes)
                    <h4>Notes publiques</h4>
                    <p>{{ $facture->notes }}</p>
                @endif
                @if($facture->notes_internes)
                    <h4 style="margin-top: 10px;">Notes internes</h4>
                    <p>{{ $facture->notes_internes }}</p>
                @endif
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p><strong>{{ $entreprise->nom }}</strong> | Facture {{ $facture->numero_facture }}</p>
            <p>Généré le {{ now()->format('d/m/Y à H:i') }}</p>
            @if($entreprise->telephone)
                <p>Téléphone: {{ $entreprise->telephone }}</p>
            @endif
            @if($entreprise->email)
                <p>Email: {{ $entreprise->email }}</p>
            @endif
        </div>

    </div>
</body>
</html>