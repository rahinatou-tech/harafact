@extends('layouts.app')
@section('title','Tableau de bord')
@section('page-title','Tableau de bord')
@section('page-sub', now()->translatedFormat('l d F Y'))

@section('topbar-actions')
<a href="{{ route('factures.create') }}" class="tb-btn">＋ Nouvelle facture</a>
@endsection

@section('content')

{{-- ── KPIs ── --}}
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label">CA du mois</div>
    <div class="stat-value" style="color:var(--blue)">
      {{ number_format($stats['ca_mois']/1000, 0, ',', ' ') }}K
    </div>
    <div class="stat-foot">
      @if($stats['ca_mois_precedent'] > 0)
        @php $diff = $stats['ca_mois'] - $stats['ca_mois_precedent']; $pct = round(abs($diff)/$stats['ca_mois_precedent']*100); @endphp
        <span class="{{ $diff >= 0 ? 'up' : 'down' }}">{{ $diff >= 0 ? '↑' : '↓' }} {{ $pct }}%</span> vs mois précédent
      @else
        XOF encaissé ce mois
      @endif
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-label">Factures émises</div>
    <div class="stat-value" style="color:var(--blue-mid)">{{ $stats['factures_emises_mois'] }}</div>
    <div class="stat-foot">Ce mois · {{ $stats['devis_en_attente'] }} devis en attente</div>
  </div>

  <div class="stat-card">
    <div class="stat-label">Taux recouvrement</div>
    <div class="stat-value" style="color:{{ $stats['taux_recouvrement'] >= 80 ? 'var(--green)' : ($stats['taux_recouvrement'] >= 50 ? 'var(--warn)' : 'var(--danger)') }}">
      {{ $stats['taux_recouvrement'] }}%
    </div>
    <div class="stat-foot">{{ $stats['clients_actifs'] }} clients actifs</div>
  </div>

  <div class="stat-card">
    <div class="stat-label">Impayés</div>
    <div class="stat-value" style="color:{{ $stats['total_impayes'] > 0 ? 'var(--danger)' : 'var(--green)' }}">
      {{ number_format($stats['total_impayes']/1000, 0, ',', ' ') }}K
    </div>
    <div class="stat-foot">
      @if($stats['factures_retard'] > 0)
        <span class="down">↓ {{ $stats['factures_retard'] }} en retard</span>
      @else
        <span class="up">✓ Aucune facture en retard</span>
      @endif
    </div>
  </div>
</div>

{{-- ── GRILLE PRINCIPALE ── --}}
<div style="display:grid;grid-template-columns:1.8fr 1fr;gap:16px;margin-bottom:16px">

  {{-- Graphique CA --}}
  <div class="card">
    <div class="card-header">
      <div class="card-title">Évolution du chiffre d'affaires</div>
      <div style="display:flex;gap:6px">
        <button class="btn btn-ghost btn-sm" onclick="setGraph('mois')" id="btn-mois" style="background:linear-gradient(135deg,#1A3C6E,#2E5FA3);color:#fff;border:none">Mois</button>
        <button class="btn btn-ghost btn-sm" onclick="setGraph('trimestre')" id="btn-trimestre">Trim.</button>
        <button class="btn btn-ghost btn-sm" onclick="setGraph('annee')" id="btn-annee">Année</button>
      </div>
    </div>
    <div class="card-body">
      <div style="height:220px"><canvas id="chartCA"></canvas></div>
    </div>
  </div>

  {{-- Factures récentes --}}
  <div class="card">
    <div class="card-header">
      <div class="card-title">Récentes</div>
      <a href="{{ route('factures.index') }}" style="font-size:12px;color:var(--blue-mid);text-decoration:none;font-weight:600">Tout voir →</a>
    </div>
    <div style="padding:0 6px">
      @forelse($dernieres_factures->take(6) as $f)
      <a href="{{ route('factures.show', $f) }}" style="display:flex;align-items:center;justify-content:space-between;padding:11px 14px;border-bottom:1px solid #EEF3FC;text-decoration:none;transition:background 0.12s;border-radius:8px" onmouseover="this.style.background='#F8FBFF'" onmouseout="this.style.background=''">
        <div>
          <div style="font-size:12.5px;font-weight:700;color:var(--text);font-family:monospace">{{ $f->numero_facture }}</div>
          <div style="font-size:11px;color:var(--muted);margin-top:1px">{{ $f->client->nom_affichage ?? '—' }}</div>
        </div>
        <div style="text-align:right">
          <div style="font-size:13px;font-weight:800;color:var(--blue)">{{ number_format($f->total_ttc/1000,0,',',' ') }}K</div>
          @php
            $tags=['paye'=>'t-paye','partiellement_paye'=>'t-partiel','envoye'=>'t-envoye','brouillon'=>'t-brouillon','en_retard'=>'t-retard','annule'=>'t-annule'];
            $labels=['paye'=>'Payé','partiellement_paye'=>'Partiel','envoye'=>'Envoyé','brouillon'=>'Brouillon','en_retard'=>'Retard','annule'=>'Annulé'];
          @endphp
          <span class="tag {{ $tags[$f->statut] ?? 't-brouillon' }}" style="font-size:10px">{{ $labels[$f->statut] ?? $f->statut }}</span>
        </div>
      </a>
      @empty
      <div style="text-align:center;padding:30px;color:var(--muted)">
        <div style="font-size:28px;margin-bottom:8px">📋</div>
        <div style="font-size:13px">Aucune facture pour l'instant</div>
        <a href="{{ route('factures.create') }}" class="btn btn-primary btn-sm" style="margin-top:12px">Créer la première</a>
      </div>
      @endforelse
    </div>
  </div>
</div>

{{-- ── LIGNE INFÉRIEURE ── --}}
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">

  {{-- Factures à suivre --}}
  <div class="card">
    <div class="card-header">
      <div class="card-title">⚡ Suivi requis</div>
      <a href="{{ route('paiements.index') }}" style="font-size:12px;color:var(--blue-mid);text-decoration:none;font-weight:600">Voir tout →</a>
    </div>
    <div style="padding:0 6px">
      @forelse($a_suivre as $f)
      <a href="{{ route('factures.show',$f) }}" style="display:block;padding:12px 14px;border-bottom:1px solid #EEF3FC;text-decoration:none">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px">
          <div style="font-size:12px;font-weight:700;color:var(--text)">{{ $f->numero_facture }}</div>
          @if($f->statut === 'en_retard')
          <span class="tag t-retard" style="font-size:10px">Retard</span>
          @else
          <span class="tag t-partiel" style="font-size:10px">{{ number_format($f->getPourcentagePaye(),0) }}%</span>
          @endif
        </div>
        <div class="progress">
          <div class="progress-fill" style="width:{{ $f->getPourcentagePaye() }}%;background:{{ $f->statut==='en_retard' ? '#EF4444' : '#2E5FA3' }}"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:10.5px;color:var(--muted);margin-top:4px">
          <span>{{ $f->client->nom_affichage ?? '—' }}</span>
          <span>{{ number_format($f->montant_du,0,',',' ') }} XOF dû</span>
        </div>
      </a>
      @empty
      <div style="text-align:center;padding:24px;color:var(--muted);font-size:13px">
        <div style="font-size:24px;margin-bottom:6px">✅</div>
        Aucun paiement en attente
      </div>
      @endforelse
    </div>
  </div>

  {{-- Modes de paiement --}}
  <div class="card">
    <div class="card-header">
      <div class="card-title">Modes de paiement</div>
      <span style="font-size:11px;color:var(--muted)">Ce mois</span>
    </div>
    <div class="card-body">
      @forelse($modesFormat as $m)
      <div style="margin-bottom:14px">
        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:5px">
          <span style="font-weight:600;color:var(--text)">{{ $m['label'] }}</span>
          <span style="color:var(--muted)">{{ $m['pct'] }}% · {{ number_format($m['total']/1000,0,',',' ') }}K</span>
        </div>
        <div class="progress">
          <div class="progress-fill" style="width:{{ $m['pct'] }}%;background:linear-gradient(90deg,#2E5FA3,#4A90D9)"></div>
        </div>
      </div>
      @empty
      <div style="text-align:center;padding:20px;color:var(--muted);font-size:13px">
        Aucun paiement ce mois
      </div>
      @endforelse
    </div>
  </div>

  {{-- Activité récente --}}
  <div class="card">
    <div class="card-header">
      <div class="card-title">Activité récente</div>
    </div>
    <div style="padding:0 6px">
      @forelse($activite as $a)
      @php
        $icones=['creation'=>'➕','modification'=>'✏️','envoi'=>'📤','paiement'=>'💰','annulation'=>'❌','connexion'=>'🔑','generation'=>'🔄'];
        $couleurs=['creation'=>'#2E5FA3','modification'=>'#F59E0B','envoi'=>'#7C3AED','paiement'=>'#00875A','annulation'=>'#EF4444','connexion'=>'#5B7299'];
      @endphp
      <div style="display:flex;align-items:flex-start;gap:10px;padding:10px 14px;border-bottom:1px solid #EEF3FC">
        <div style="width:28px;height:28px;border-radius:50%;background:{{ $couleurs[$a->action] ?? '#EEF3FC' }}22;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0">{{ $icones[$a->action] ?? '•' }}</div>
        <div style="flex:1;min-width:0">
          <div style="font-size:12px;font-weight:600;color:var(--text)">
            {{ ucfirst($a->action) }} · {{ $a->type_entite }}
          </div>
          <div style="font-size:11px;color:var(--muted);margin-top:1px">
            {{ $a->utilisateur?->prenom ?? 'Système' }} · {{ $a->cree_le->diffForHumans() }}
          </div>
        </div>
      </div>
      @empty
      <div style="text-align:center;padding:24px;color:var(--muted);font-size:13px">Aucune activité enregistrée</div>
      @endforelse
    </div>
  </div>

</div>

@endsection

@push('scripts')
<script>
const graphData = {
  mois: {
    labels: @json(collect($graphique)->pluck('label')),
    data:   @json(collect($graphique)->pluck('ca'))
  }
};

const ctx = document.getElementById('chartCA').getContext('2d');
const grad = ctx.createLinearGradient(0,0,0,220);
grad.addColorStop(0,'rgba(46,95,163,0.2)');
grad.addColorStop(1,'rgba(46,95,163,0)');

const chart = new Chart(ctx, {
  type:'line',
  data:{
    labels: graphData.mois.labels,
    datasets:[{
      data: graphData.mois.data,
      borderColor:'#2E5FA3',backgroundColor:grad,
      borderWidth:2.5,tension:0.4,fill:true,
      pointBackgroundColor:'#fff',pointBorderColor:'#2E5FA3',
      pointBorderWidth:2,pointRadius:4,pointHoverRadius:7
    }]
  },
  options:{
    responsive:true,maintainAspectRatio:false,
    plugins:{
      legend:{display:false},
      tooltip:{
        backgroundColor:'#1A3C6E',titleColor:'rgba(255,255,255,0.6)',
        bodyColor:'#fff',bodyFont:{family:'Poppins',size:13,weight:'700'},
        padding:11,cornerRadius:9,
        callbacks:{label:c=>'  '+Math.round(c.parsed.y).toLocaleString('fr-FR')+' XOF'}
      }
    },
    scales:{
      x:{grid:{display:false},ticks:{font:{family:'Poppins',size:10},color:'#9BB3CC'},border:{display:false}},
      y:{grid:{color:'rgba(200,220,255,0.3)'},ticks:{font:{family:'Poppins',size:10},color:'#9BB3CC',callback:v=>(v/1000).toFixed(0)+'K'},border:{display:false}}
    }
  }
});

function setGraph(p) {
  document.querySelectorAll('.btn-sm').forEach(b=>{
    b.style.background='';b.style.color='';b.style.border='1.5px solid var(--border)';
  });
  document.getElementById('btn-'+p).style.background='linear-gradient(135deg,#1A3C6E,#2E5FA3)';
  document.getElementById('btn-'+p).style.color='#fff';
  document.getElementById('btn-'+p).style.border='none';
  chart.update();
}
</script>
@endpush
