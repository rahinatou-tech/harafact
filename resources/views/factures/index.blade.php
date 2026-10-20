@extends('layouts.app')
@section('title','Documents')
@section('page-title','Documents & Factures')
@section('page-sub', $stats['total'].' documents · CA mois '.number_format($stats['ca_mois']/1000,0,',',' ').'K XOF')

@section('topbar-actions')
<a href="{{ route('factures.create') }}" class="tb-btn">＋ Nouveau document</a>
@endsection

@section('content')

<div class="stats-grid">
  <div class="stat-card"><div class="stat-label">Total documents</div><div class="stat-value" style="color:var(--blue)">{{ $stats['total'] }}</div></div>
  <div class="stat-card"><div class="stat-label">CA encaissé</div><div class="stat-value" style="color:var(--green)">{{ number_format($stats['ca_mois']/1000,0,',',' ') }}K</div><div class="stat-foot">XOF ce mois</div></div>
  <div class="stat-card"><div class="stat-label">Payées</div><div class="stat-value" style="color:var(--green)">{{ $stats['payees'] }}</div></div>
  <div class="stat-card"><div class="stat-label">Impayés total</div><div class="stat-value" style="color:var(--danger)">{{ number_format($stats['impayes']/1000,0,',',' ') }}K</div><div class="stat-foot"><span class="down">{{ $stats['en_retard'] }} en retard</span></div></div>
</div>

{{-- Filtres onglets --}}
<div style="display:flex;gap:6px;margin-bottom:14px;flex-wrap:wrap">
  @php $types=[''=>['label'=>'Tous','emoji'=>'📋'],'simple'=>['label'=>'Facture','emoji'=>'📄'],'proforma'=>['label'=>'Proforma','emoji'=>'📝'],'avoir'=>['label'=>'Avoir','emoji'=>'↩️'],'acompte'=>['label'=>'Acompte','emoji'=>'💰'],'solde'=>['label'=>'Solde','emoji'=>'🔒'],'recurrente'=>['label'=>'Récurrente','emoji'=>'🔄']]; @endphp
  @foreach($types as $val => $info)
  <a href="{{ route('factures.index',array_merge(request()->except('type'),['type'=>$val?$val:''])) }}"
     style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:20px;font-size:12px;font-weight:600;text-decoration:none;border:1.5px solid var(--border);transition:all 0.15s;
            {{ request('type')===$val ? 'background:linear-gradient(135deg,#1A3C6E,#2E5FA3);color:#fff;border-color:transparent' : 'background:#fff;color:var(--muted)' }}">
    {{ $info['emoji'] }} {{ $info['label'] }}
  </a>
  @endforeach
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title">Liste des documents</div>
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
      <input type="hidden" name="type" value="{{ request('type') }}">
      <div class="search-box">
        <span class="search-ico">🔍</span>
        <input type="text" name="q" placeholder="N°, client..." value="{{ request('q') }}" onchange="this.form.submit()">
      </div>
      <select name="statut" onchange="this.form.submit()" style="padding:9px 12px;border:1.5px solid var(--border);border-radius:10px;font-family:'Poppins',sans-serif;font-size:13px;color:var(--text);background:#F8FAFF;outline:none">
        <option value="">Tous statuts</option>
        <option value="en_attente_validation" {{ request('statut')==='en_attente_validation'?'selected':'' }}>⏳ En attente de validation</option>
        <option value="validee"   {{ request('statut')==='validee'?'selected':'' }}>Validée</option>
        <option value="brouillon" {{ request('statut')==='brouillon'?'selected':'' }}>Brouillon</option>
        <option value="envoye"    {{ request('statut')==='envoye'?'selected':'' }}>Envoyé</option>
        <option value="paye"      {{ request('statut')==='paye'?'selected':'' }}>Payé</option>
        <option value="en_retard" {{ request('statut')==='en_retard'?'selected':'' }}>En retard</option>
      </select>
    </form>
  </div>

  <div style="overflow-x:auto">
    <table>
      <thead>
        <tr><th>N° Document</th><th>Type</th><th>Client</th><th>Date</th><th>Échéance</th><th>Montant TTC</th><th>Paiement</th><th>Statut</th><th>Actions</th></tr>
      </thead>
      <tbody>
        @forelse($factures as $f)
        @php
          $typeBadges=['simple'=>['#2E5FA3','#DBEAFE','Facture'],'proforma'=>['#0369A1','#E0F2FE','Proforma'],'avoir'=>['#7C3AED','#EDE9FE','Avoir'],'acompte'=>['#B45309','#FEF3C7','Acompte'],'solde'=>['#134E4A','#CCFBF1','Solde'],'recurrente'=>['#5B21B6','#EDE9FE','Récurrente']];
          $tb=$typeBadges[$f->type_facture]??['#5B7299','#F1F5F9','—'];
          $statusCss=['paye'=>'t-paye','partiellement_paye'=>'t-partiel','envoye'=>'t-envoye','brouillon'=>'t-brouillon','en_retard'=>'t-retard','annule'=>'t-annule','en_attente_validation'=>'t-attente','validee'=>'t-partiel'];
          $statusLabel=['paye'=>'✓ Payé','partiellement_paye'=>'½ Partiel','envoye'=>'↗ Envoyé','brouillon'=>'◌ Brouillon','en_retard'=>'⚠ Retard','annule'=>'— Annulé','en_attente_validation'=>'⏳ À valider','validee'=>'✓ Validée'];
        @endphp
        <tr onclick="window.location='{{ route('factures.show',$f) }}'">
          <td>
            <div style="font-weight:800;font-size:12.5px;font-family:monospace;color:var(--text)">{{ $f->numero_facture ?? '(brouillon)' }}</div>
            <div style="font-size:10.5px;color:var(--muted);margin-top:1px">{{ $f->date_emission?->format('d/m/Y') ?? '—' }}</div>
          </td>
          <td>
            <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:800;background:{{ $tb[1] }};color:{{ $tb[0] }}">{{ $tb[2] }}</span>
          </td>
          <td>
            <div style="font-weight:600;font-size:13px">{{ $f->client->nom_affichage ?? '—' }}</div>
          </td>
          <td style="font-size:12.5px;color:var(--muted)">{{ $f->date_emission?->format('d/m/Y') ?? '—' }}</td>
          <td style="font-size:12.5px;color:{{ $f->statut==='en_retard'?'var(--danger)':'var(--muted)' }};font-weight:{{ $f->statut==='en_retard'?'700':'400' }}">
            {{ $f->date_echeance?->format('d/m/Y') ?? '—' }}
          </td>
          <td>
            <div style="font-size:14px;font-weight:900;color:var(--blue)">{{ number_format($f->total_ttc,0,',',' ') }}</div>
            <div style="font-size:10px;color:var(--muted)">XOF TTC</div>
          </td>
          <td>
            @if($f->aUnSuivi())
            <div class="progress" style="width:80px">
              <div class="progress-fill" style="width:{{ $f->getPourcentagePaye() }}%;background:{{ $f->statut==='en_retard'?'#EF4444':'#2E5FA3' }}"></div>
            </div>
            <div style="font-size:10px;color:var(--muted);margin-top:2px">{{ $f->getPourcentagePaye() }}%</div>
            @else
            <span style="font-size:11px;color:var(--green);font-weight:600">⚡ Auto</span>
            @endif
          </td>
          <td><span class="tag {{ $statusCss[$f->statut]??'t-brouillon' }}">{{ $statusLabel[$f->statut]??$f->statut }}</span></td>
          <td onclick="event.stopPropagation()">
            <div style="display:flex;gap:4px">
              <a href="{{ route('factures.show',$f) }}" class="btn-icon" title="Voir">👁️</a>
              @if(auth()->user()->peutCreer() && $f->statut !== 'paye')
              <a href="{{ route('factures.edit',$f) }}" class="btn-icon" title="Modifier">✏️</a>
              @endif
              <form method="POST" action="{{ route('factures.dupliquer',$f) }}" onsubmit="event.stopPropagation()">@csrf
                <button type="submit" class="btn-icon" title="Dupliquer">📋</button>
              </form>
              @if(auth()->user()->peutCreer() && $f->statut !== 'paye')
              <form method="POST" action="{{ route('factures.destroy',$f) }}" onsubmit="return confirm('Supprimer cette facture ?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn-icon del" title="Supprimer">🗑️</button>
              </form>
              @endif
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--muted)">
          <div style="font-size:32px;margin-bottom:10px">📋</div>
          Aucun document trouvé<br>
          <a href="{{ route('factures.create') }}" class="btn btn-primary btn-sm" style="margin-top:14px">Créer le premier</a>
        </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($factures->hasPages())
  <div class="pgn">
    <div class="pgn-info">{{ $factures->firstItem() }}–{{ $factures->lastItem() }} sur {{ $factures->total() }}</div>
    <div>{{ $factures->links() }}</div>
  </div>
  @endif
</div>

@endsection