@extends('layouts.app')
@section('title', $client->nom_affichage)
@section('page-title', $client->nom_affichage)
@section('page-sub', $client->code_client.' · '.$client->ville.' '.$client->pays)
@section('topbar-actions')
<a href="{{ route('clients.index') }}" class="tb-btn ghost">← Retour</a>
<a href="{{ route('factures.create',['client_id'=>$client->id]) }}" class="tb-btn">＋ Nouvelle facture</a>
@endsection
@section('content')
<div style="display:grid;grid-template-columns:1fr 2fr;gap:16px">
  <div>
    <div class="card" style="margin-bottom:14px">
      <div class="card-body">
        <div style="text-align:center;padding:10px 0 16px">
          <div style="width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#1A3C6E,#2E5FA3);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:900;color:#fff;margin:0 auto 12px">{{ strtoupper(substr($client->nom_affichage,0,2)) }}</div>
          <div style="font-size:16px;font-weight:800;color:var(--text)">{{ $client->nom_affichage }}</div>
          <div style="font-size:12px;color:var(--muted);margin-top:3px">{{ $client->code_client }} · {{ $client->type_client==='entreprise'?'🏢 Entreprise':'👤 Particulier' }}</div>
          <span class="tag {{ $client->est_actif?'t-paye':'t-annule' }}" style="margin-top:8px">{{ $client->est_actif?'Actif':'Archivé' }}</span>
        </div>
        <hr style="border:none;border-top:1px solid var(--border);margin:12px 0">
        @if($client->email)<div style="display:flex;gap:8px;padding:6px 0;font-size:13px"><span>📧</span><span>{{ $client->email }}</span></div>@endif
        @if($client->telephone)<div style="display:flex;gap:8px;padding:6px 0;font-size:13px"><span>📞</span><span>{{ $client->telephone }}</span></div>@endif
        @if($client->adresse_ligne1)<div style="display:flex;gap:8px;padding:6px 0;font-size:13px"><span>📍</span><span>{{ $client->adresse_ligne1 }}, {{ $client->ville }}</span></div>@endif
        @if($client->numero_fiscal)<div style="display:flex;gap:8px;padding:6px 0;font-size:13px"><span>🪪</span><span>NIF: {{ $client->numero_fiscal }}</span></div>@endif
        @if($client->groupeTaxe)<div style="display:flex;gap:8px;padding:6px 0;font-size:13px"><span>💰</span><span>{{ $client->groupeTaxe->nom }}</span></div>@endif
      </div>
    </div>
    <div class="stats-grid" style="grid-template-columns:1fr 1fr">
      <div class="stat-card"><div class="stat-label">Factures</div><div class="stat-value" style="color:var(--blue)">{{ $stats['total_factures'] }}</div></div>
      <div class="stat-card"><div class="stat-label">CA total</div><div class="stat-value" style="font-size:16px;color:var(--green)">{{ number_format($stats['total_ca']/1000,0,',',' ') }}K</div></div>
      <div class="stat-card"><div class="stat-label">Solde dû</div><div class="stat-value" style="font-size:16px;color:{{ $stats['total_du']>0?'var(--danger)':'var(--green)' }}">{{ number_format($stats['total_du']/1000,0,',',' ') }}K</div></div>
      <div class="stat-card"><div class="stat-label">Retard</div><div class="stat-value" style="color:var(--danger)">{{ $stats['nb_retard'] }}</div></div>
    </div>
  </div>
  <div>
    <div class="card" style="margin-bottom:14px">
      <div class="card-header"><div class="card-title">Dernières factures</div><a href="{{ route('factures.index',['q'=>$client->nom_affichage]) }}" style="font-size:12px;color:var(--blue-mid);text-decoration:none;font-weight:600">Tout voir →</a></div>
      <div style="overflow-x:auto">
        <table>
          <thead><tr><th>N°</th><th>Type</th><th>Date</th><th>Montant</th><th>Statut</th><th></th></tr></thead>
          <tbody>
            @forelse($factures as $f)
            @php $sc=['paye'=>'t-paye','partiellement_paye'=>'t-partiel','envoye'=>'t-envoye','brouillon'=>'t-brouillon','en_retard'=>'t-retard'];@endphp
            <tr>
              <td style="font-family:monospace;font-weight:700;font-size:12.5px">{{ $f->numero_facture ?? '—' }}</td>
              <td><span style="font-size:11px;color:var(--muted)">{{ ucfirst($f->type_facture) }}</span></td>
              <td style="font-size:12.5px;color:var(--muted)">{{ $f->date_emission?->format('d/m/Y') }}</td>
              <td style="font-weight:800;color:var(--blue)">{{ number_format($f->total_ttc,0,',',' ') }}</td>
              <td><span class="tag {{ $sc[$f->statut]??'t-brouillon' }}" style="font-size:10px">{{ ucfirst($f->statut) }}</span></td>
              <td><a href="{{ route('factures.show',$f) }}" class="btn-icon" style="font-size:12px">👁️</a></td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;padding:20px;color:var(--muted)">Aucune facture</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
