<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Facture {{ $facture->numero_facture }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Poppins', sans-serif; background: #F1F5F9; color: #1E293B; padding: 40px 16px; }

    .container { max-width: 720px; margin: 0 auto; }

    .header { background: linear-gradient(135deg, #0F172A, #1E3A8A); border-radius: 20px; padding: 32px; color: #fff; margin-bottom: 20px; }
    .header-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
    .company-name { font-size: 22px; font-weight: 800; }
    .company-info { font-size: 12px; color: rgba(255,255,255,0.5); margin-top: 4px; }
    .facture-numero { font-size: 28px; font-weight: 900; font-family: monospace; }
    .facture-date { font-size: 12px; color: rgba(255,255,255,0.5); margin-top: 4px; }

    .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    .stat { background: rgba(255,255,255,0.08); border-radius: 12px; padding: 14px 16px; }
    .stat-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.4); margin-bottom: 6px; }
    .stat-value { font-size: 16px; font-weight: 800; color: #fff; }
    .stat-value.green { color: #4ADE80; }
    .stat-value.red   { color: #FCA5A5; }

    .card { background: #fff; border-radius: 16px; padding: 28px; margin-bottom: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }

    .parties { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 2px solid #F1F5F9; }
    .party-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #94A3B8; margin-bottom: 6px; font-weight: 700; }
    .party-name  { font-size: 15px; font-weight: 800; color: #0F172A; margin-bottom: 4px; }
    .party-info  { font-size: 12px; color: #64748B; line-height: 1.6; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    thead tr { background: #0F172A; }
    thead th { padding: 10px 14px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px; color: rgba(255,255,255,0.7); font-weight: 700; text-align: left; }
    thead th:not(:first-child) { text-align: right; }
    tbody tr { border-bottom: 1px solid #F1F5F9; }
    tbody td { padding: 12px 14px; font-size: 13px; color: #334155; }
    tbody td:not(:first-child) { text-align: right; font-weight: 600; }

    .totaux { margin-left: auto; width: 280px; }
    .totaux-line { display: flex; justify-content: space-between; padding: 5px 0; font-size: 13px; color: #64748B; }
    .totaux-line.final { border-top: 2px solid #CBD5E1; margin-top: 8px; padding-top: 12px; }
    .totaux-line.final span:first-child { font-size: 14px; font-weight: 800; color: #0F172A; }
    .totaux-line.final span:last-child  { font-size: 20px; font-weight: 900; color: #1A3C6E; }

    .footer { text-align: center; font-size: 12px; color: #94A3B8; margin-top: 24px; }

    .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; }
    .badge-paye    { background: #DCFCE7; color: #16A34A; }
    .badge-attente { background: #FEF3C7; color: #B45309; }
    .badge-retard  { background: #FEE2E2; color: #DC2626; }

    @media (max-width: 600px) {
      .stats   { grid-template-columns: 1fr 1fr; }
      .parties { grid-template-columns: 1fr; }
      .totaux  { width: 100%; }
      .header-top { flex-direction: column; gap: 12px; }
    }
  </style>
</head>
<body>
<div class="container">

  {{-- HEADER --}}
  <div class="header">
    <div class="header-top">
      <div>
        <div class="company-name">{{ $facture->entreprise->nom }}</div>
        <div class="company-info">{{ $facture->entreprise->email }}</div>
      </div>
      <div style="text-align:right">
        <div class="facture-numero">{{ $facture->numero_facture }}</div>
        <div class="facture-date">Émis le {{ $facture->date_emission?->format('d/m/Y') }}</div>
        @if($facture->date_echeance)
        <div class="facture-date">Échéance {{ $facture->date_echeance->format('d/m/Y') }}</div>
        @endif
      </div>
    </div>

    <div class="stats">
      <div class="stat">
        <div class="stat-label">Total TTC</div>
        <div class="stat-value">{{ number_format($facture->total_ttc, 0, ',', ' ') }} {{ $facture->devise }}</div>
      </div>
      <div class="stat">
        <div class="stat-label">Statut</div>
        <div class="stat-value {{ $facture->statut === 'paye' ? 'green' : 'red' }}">
          {{ $facture->statut === 'paye' ? '✓ Payé' : ($facture->statut === 'en_retard' ? '⚠ Retard' : '⏳ En attente') }}
        </div>
      </div>
      <div class="stat">
        <div class="stat-label">Restant dû</div>
        <div class="stat-value {{ $facture->montant_du > 0 ? 'red' : 'green' }}">
          {{ number_format($facture->montant_du, 0, ',', ' ') }} {{ $facture->devise }}
        </div>
      </div>
    </div>
  </div>

  {{-- CORPS --}}
  <div class="card">

    <div class="parties">
      <div>
        <div class="party-label">De</div>
        <div class="party-name">{{ $facture->entreprise->nom }}</div>
        <div class="party-info">
          {{ $facture->entreprise->adresse_ligne1 }}<br>
          {{ $facture->entreprise->ville ?? '' }}<br>
          {{ $facture->entreprise->email }}
        </div>
      </div>
      <div>
        <div class="party-label">À</div>
        <div class="party-name">{{ $facture->client->nom_affichage }}</div>
        <div class="party-info">
          @if($facture->client->email){{ $facture->client->email }}<br>@endif
          @if($facture->client->telephone){{ $facture->client->telephone }}<br>@endif
          @if($facture->client->adresse_ligne1){{ $facture->client->adresse_ligne1 }}@endif
        </div>
      </div>
    </div>

    {{-- Lignes --}}
    @if($facture->lignes->count() > 0)
    <table>
      <thead>
        <tr>
          <th style="border-radius:8px 0 0 8px">Désignation</th>
          <th>Qté</th>
          <th>Prix unit. HT</th>
          <th style="border-radius:0 8px 8px 0">Total HT</th>
        </tr>
      </thead>
      <tbody>
        @foreach($facture->lignes as $l)
        <tr>
          <td>
            <div style="font-weight:600">{{ $l->designation }}</div>
            @if($l->unite)<div style="font-size:11px;color:#94A3B8">{{ $l->unite }}</div>@endif
          </td>
          <td>{{ $l->quantite }}</td>
          <td>{{ number_format($l->prix_unitaire_ht, 0, ',', ' ') }}</td>
          <td style="color:#1A3C6E">{{ number_format($l->total_ht_ligne, 0, ',', ' ') }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @endif

    {{-- Totaux --}}
    <div style="display:flex;justify-content:flex-end">
      <div class="totaux">
        <div class="totaux-line"><span>Sous-total HT</span><span>{{ number_format($facture->total_ht, 0, ',', ' ') }} {{ $facture->devise }}</span></div>
        @if($facture->total_remises_ht > 0)
        <div class="totaux-line"><span>Remises</span><span style="color:#EF4444">− {{ number_format($facture->total_remises_ht, 0, ',', ' ') }} {{ $facture->devise }}</span></div>
        @endif
        <div class="totaux-line"><span>Taxes</span><span>+ {{ number_format($facture->total_taxes, 0, ',', ' ') }} {{ $facture->devise }}</span></div>
        <div class="totaux-line final">
          <span>Total TTC</span>
          <span>{{ number_format($facture->total_ttc, 0, ',', ' ') }} {{ $facture->devise }}</span>
        </div>
      </div>
    </div>

    @if($facture->notes)
    <div style="margin-top:20px;padding:14px 16px;background:#F8FAFC;border-radius:10px;border-left:3px solid #CBD5E1;font-size:12px;color:#64748B">
      <div style="font-weight:700;color:#334155;margin-bottom:4px">Notes</div>
      {{ $facture->notes }}
    </div>
    @endif

  </div>

  <div class="footer">
    🔒 Ce lien est sécurisé et personnel — {{ $facture->entreprise->nom }}
  </div>

</div>
</body>
</html>