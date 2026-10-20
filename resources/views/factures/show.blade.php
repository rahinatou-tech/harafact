@extends('layouts.app')
@section('title', $facture->numero_facture)
@section('page-title', $facture->numero_facture ?? 'Facture')
@section('page-sub', $facture->type_label.' · '.$facture->client->nom_affichage)

@section('topbar-actions')
<a href="{{ route('factures.index') }}" class="tb-btn ghost">← Retour</a>

@if(in_array($facture->statut, ['paye', 'envoye', 'partiellement_paye']))
    <a href="{{ route('factures.pdf', $facture) }}" class="tb-btn ghost" target="_blank">🖨️ Imprimer PDF</a>
@endif

@if(auth()->user()->isEmploye())
    @if($facture->statut === 'en_attente_validation')
        <a href="{{ route('factures.edit', $facture) }}" class="tb-btn ghost">✏️ Modifier</a>
    @endif
    <form method="POST" action="{{ route('factures.dupliquer', $facture) }}" style="display:inline">
        @csrf
        <button type="submit" class="tb-btn ghost">📋 Dupliquer</button>
    </form>
@endif

@if(auth()->user()->isComptable())
    @if($facture->statut === 'en_attente_validation')
        <button type="button" class="tb-btn" style="background:#22c55e;color:white" onclick="openValidateModal()">✅ Valider</button>
        <button type="button" class="tb-btn" style="background:#ef4444;color:white" onclick="openRejectModal()">❌ Rejeter</button>
    @endif
    {{-- Le bouton "Envoyer" ne sert plus que pour les récurrentes validées :
         pour simple/acompte/solde/proforma/avoir, la validation (ci-dessus)
         envoie déjà automatiquement le PDF par email/WhatsApp au client. --}}
    @if($facture->type_facture === 'recurrente' && $facture->statut === 'validee')
    <button type="button" class="tb-btn" style="background:#3b82f6;color:white" onclick="openSendModal()">📤 Envoyer</button>
@endif
@endif

@if(auth()->user()->isDG())
    <span style="color:var(--muted);font-size:12px">👁️ Lecture seule</span>
@endif
@endsection

@push('styles')
<style>
.fiche-wrap { display:grid; grid-template-columns:1fr 320px; gap:20px; align-items:start; }

.hero-card { border-radius:20px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,0.18); margin-bottom:16px; }
.hero-dark { padding:28px 32px 24px; position:relative; overflow:hidden; }
.hero-dark::before { content:''; position:absolute; width:300px; height:300px; border-radius:50%; background:rgba(255,255,255,0.04); top:-80px; right:-60px; }
.hero-dark::after  { content:''; position:absolute; width:180px; height:180px; border-radius:50%; background:rgba(255,255,255,0.03); bottom:-40px; left:40px; }

.hero-type-badge { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.2); border-radius:20px; padding:4px 12px; font-size:11px; font-weight:700; color:rgba(255,255,255,0.8); letter-spacing:0.8px; text-transform:uppercase; margin-bottom:14px; }
.hero-numero { font-size:32px; font-weight:900; color:#fff; font-family:monospace; letter-spacing:-1px; line-height:1; margin-bottom:6px; position:relative; z-index:1; }
.hero-date   { font-size:12px; color:rgba(255,255,255,0.5); margin-bottom:24px; position:relative; z-index:1; }

.hero-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; position:relative; z-index:1; }
.hero-stat  { background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.1); border-radius:14px; padding:14px 16px; backdrop-filter:blur(10px); }
.hero-stat-label { font-size:9px; text-transform:uppercase; letter-spacing:1px; color:rgba(255,255,255,0.4); margin-bottom:6px; font-weight:600; }
.hero-stat-value { font-size:17px; font-weight:900; color:#fff; line-height:1.1; }
.hero-stat-value.green  { color:#4ADE80; }
.hero-stat-value.red    { color:#FCA5A5; }
.hero-stat-value.yellow { color:#FDE68A; }
.hero-stat-value.blue   { color:#93C5FD; }
.hero-stat-sub { font-size:10px; color:rgba(255,255,255,0.4); margin-top:3px; }

.doc-body { background:#fff; padding:28px 32px; }
.doc-parties { display:grid; grid-template-columns:1fr 1fr; gap:24px; padding-bottom:20px; margin-bottom:20px; border-bottom:2px solid #F1F5F9; }
.doc-party-label { font-size:9px; text-transform:uppercase; letter-spacing:1.2px; color:#94A3B8; font-weight:700; margin-bottom:8px; }
.doc-party-name  { font-size:15px; font-weight:800; color:#0F172A; margin-bottom:3px; }
.doc-party-info  { font-size:12px; color:#64748B; line-height:1.6; }

.type-bloc { border-radius:12px; padding:14px 18px; margin-bottom:18px; border-left:4px solid; }
.type-bloc.avoir    { background:#F5F3FF; border-color:#7C3AED; color:#4C1D95; }
.type-bloc.acompte  { background:#FFFBEB; border-color:#D97706; color:#78350F; }
.type-bloc.solde    { background:#F0FDF4; border-color:#16A34A; color:#14532D; }
.type-bloc.proforma { background:#EFF6FF; border-color:#2563EB; color:#1E3A8A; }
.type-bloc.recurrente { background:#F5F3FF; border-color:#5B21B6; color:#2E1065; }
.type-bloc-title { font-weight:800; font-size:13px; margin-bottom:4px; }
.type-bloc-sub   { font-size:12px; opacity:0.8; line-height:1.5; }
.type-bloc a     { font-weight:700; text-decoration:underline; }

.lignes-table { width:100%; border-collapse:collapse; margin-bottom:20px; }
.lignes-table thead tr { background:#0F172A; }
.lignes-table thead th { padding:10px 14px; font-size:10px; text-transform:uppercase; letter-spacing:0.8px; color:rgba(255,255,255,0.7); font-weight:700; text-align:left; }
.lignes-table thead th:not(:first-child) { text-align:right; }
.lignes-table tbody tr { border-bottom:1px solid #F1F5F9; transition:background 0.1s; }
.lignes-table tbody tr:hover { background:#F8FAFC; }
.lignes-table tbody td { padding:12px 14px; font-size:13px; color:#334155; }
.lignes-table tbody td:not(:first-child) { text-align:right; font-weight:600; }

.totaux-wrap { background:#F8FAFC; border:1px solid #E2E8F0; border-radius:14px; padding:18px 20px; margin-left:auto; width:320px; }
.totaux-line { display:flex; justify-content:space-between; align-items:center; padding:5px 0; font-size:13px; color:#64748B; }
.totaux-line.final { border-top:2px solid #CBD5E1; margin-top:10px; padding-top:12px; }
.totaux-line.final span:first-child { font-size:14px; font-weight:800; color:#0F172A; }
.totaux-line.final span:last-child  { font-size:20px; font-weight:900; color:#1A3C6E; }

.side-card { background:#fff; border:1px solid var(--border); border-radius:16px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.05); margin-bottom:14px; }
.side-card-head { padding:14px 18px; font-size:12px; font-weight:800; color:#0F172A; border-bottom:1px solid #F1F5F9; background:#F8FAFC; letter-spacing:0.3px; }
.side-row { display:flex; justify-content:space-between; align-items:center; padding:10px 18px; border-bottom:1px solid #F8FAFC; font-size:12.5px; }
.side-row:last-child { border-bottom:none; }
.side-row-lbl { color:#64748B; }
.side-row-val { font-weight:600; color:#0F172A; text-align:right; max-width:55%; }

.linked-table { width:100%; border-collapse:collapse; }
.linked-table th { padding:9px 16px; font-size:10px; text-transform:uppercase; letter-spacing:0.6px; color:var(--muted); background:#F8FAFC; border-bottom:1px solid var(--border); }
.linked-table td { padding:10px 16px; font-size:12.5px; border-bottom:1px solid #F8FAFC; }

.bg-simple     { background:linear-gradient(135deg,#0F172A 0%,#1E3A8A 50%,#1A3C6E 100%); }
.bg-proforma   { background:linear-gradient(135deg,#0F172A 0%,#0C4A6E 50%,#0369A1 100%); }
.bg-avoir      { background:linear-gradient(135deg,#1A0533 0%,#3B0764 50%,#6D28D9 100%); }
.bg-acompte    { background:linear-gradient(135deg,#1C0A00 0%,#78350F 50%,#B45309 100%); }
.bg-solde      { background:linear-gradient(135deg,#001A0F 0%,#064E3B 50%,#065F46 100%); }
.bg-recurrente { background:linear-gradient(135deg,#0D0020 0%,#2E1065 50%,#4C1D95 100%); }
</style>
@endpush

@section('content')
@php
$sc = [
    'paye'                  => ['t-paye',     '✓ Payé'],
    'partiellement_paye'    => ['t-partiel',  '½ Partiel'],
    'envoye'                => ['t-envoye',   '↗ Envoyé'],
    'brouillon'             => ['t-brouillon','◌ Brouillon'],
    'en_retard'             => ['t-retard',   '⚠ Retard'],
    'annule'                => ['t-annule',   '— Annulé'],
    'en_attente_validation' => ['t-brouillon','⏳ En attente'],
];

$typeEmoji = [
    'simple'     => '📄',
    'proforma'   => '📝',
    'avoir'      => '↩️',
    'acompte'    => '💰',
    'solde'      => '🔒',
    'recurrente' => '🔄',
];
$emoji = $typeEmoji[$facture->type_facture] ?? '📄';

// Stats adaptées par type
$cfg = $facture->configRecurrence ?? null;

switch($facture->type_facture) {
    case 'avoir':
        $stats = [
            ['label' => 'Montant remboursé', 'value' => number_format($facture->total_ttc,0,',',' ').' XOF', 'class' => 'yellow'],
            ['label' => 'Remboursement', 'value' => $facture->statut === 'paye' ? '✓ Effectué' : '⏳ En attente', 'class' => $facture->statut === 'paye' ? 'green' : ''],
            ['label' => 'Facture originale', 'value' => $facture->factureParente?->numero_facture ?? '—', 'class' => 'blue'],
        ];
        break;

    case 'acompte':
        $totalParente = $facture->factureParente?->total_ttc ?? 0;
        $pourcentage  = $totalParente > 0 ? round(($facture->total_ttc / $totalParente) * 100, 1) : 0;
        $resteApayer  = max(0, $totalParente - $facture->total_ttc);
        $stats = [
            ['label' => 'Montant acompte',  'value' => number_format($facture->total_ttc,0,',',' ').' XOF', 'class' => 'yellow'],
            ['label' => '% du total',        'value' => $pourcentage.'%', 'class' => 'blue'],
            ['label' => 'Reste à payer',     'value' => number_format($resteApayer,0,',',' ').' XOF', 'class' => $resteApayer > 0 ? 'red' : 'green'],
        ];
        break;

    case 'solde':
        $totalParente    = $facture->factureParente?->total_ttc ?? 0;
        $acomptesDeduits = $totalParente - $facture->total_ttc;
        $stats = [
            ['label' => 'Montant soldé',     'value' => number_format($facture->total_ttc,0,',',' ').' XOF', 'class' => 'green'],
            ['label' => 'Total original',    'value' => number_format($totalParente,0,',',' ').' XOF', 'class' => ''],
            ['label' => 'Acomptes déduits',  'value' => number_format($acomptesDeduits,0,',',' ').' XOF', 'class' => 'yellow'],
        ];
        break;

    case 'proforma':
        $stats = [
            ['label' => 'Montant estimé',  'value' => number_format($facture->total_ttc,0,',',' ').' XOF', 'class' => 'blue'],
            ['label' => 'Valeur comptable', 'value' => '✗ Aucune', 'class' => 'red'],
            ['label' => 'Valide jusqu\'au', 'value' => $facture->date_echeance?->format('d/m/Y') ?? '—', 'class' => ''],
        ];
        break;

    case 'recurrente':
        $stats = [
            ['label' => 'Montant/occurrence', 'value' => number_format($facture->total_ttc,0,',',' ').' XOF', 'class' => ''],
            ['label' => 'Fréquence',           'value' => ucfirst($cfg?->frequence ?? '—'), 'class' => 'blue'],
            ['label' => 'Prochain envoi',      'value' => $cfg?->prochaine_generation_le?->format('d/m/Y') ?? '—', 'class' => 'yellow'],
        ];
        break;

    default: // simple
        $stats = [
            ['label' => 'Total TTC',   'value' => number_format($facture->total_ttc,0,',',' ').' XOF', 'class' => ''],
            ['label' => 'Encaissé',    'value' => number_format($facture->montant_paye,0,',',' ').' XOF', 'class' => 'green'],
            ['label' => 'Restant dû',  'value' => number_format($facture->montant_du,0,',',' ').' XOF', 'class' => $facture->montant_du > 0 ? 'red' : 'green'],
        ];
        break;
}
@endphp

<div class="fiche-wrap">

  {{-- ══ COLONNE GAUCHE ══ --}}
  <div>
    <div class="hero-card">
      <div class="hero-dark bg-{{ $facture->type_facture }}">
        <div style="display:flex;justify-content:space-between;align-items:flex-start">
          <div>
            <div class="hero-type-badge">{{ $emoji }} {{ $facture->type_label }}</div>
            <div class="hero-numero">{{ $facture->numero_facture }}</div>
            <div class="hero-date">
              Émis le {{ $facture->date_emission?->format('d/m/Y') }}
              @if($facture->date_echeance) · Échéance {{ $facture->date_echeance->format('d/m/Y') }} @endif
            </div>
          </div>
          <span class="tag {{ ($sc[$facture->statut]??['t-brouillon','—'])[0] }}" style="font-size:11px;padding:5px 14px">
            {{ ($sc[$facture->statut]??['','—'])[1] }}
          </span>
        </div>

        {{-- Stats adaptées par type --}}
        <div class="hero-stats">
          @foreach($stats as $stat)
          <div class="hero-stat">
            <div class="hero-stat-label">{{ $stat['label'] }}</div>
            <div class="hero-stat-value {{ $stat['class'] }}">{{ $stat['value'] }}</div>
          </div>
          @endforeach
        </div>
      </div>

      {{-- DOCUMENT BODY --}}
      <div class="doc-body">

        {{-- Parties --}}
        <div class="doc-parties">
          <div>
            <div class="doc-party-label">De</div>
            <div class="doc-party-name">{{ auth()->user()->entreprise->nom }}</div>
            <div class="doc-party-info">
              {{ auth()->user()->entreprise->adresse_ligne1 }}<br>
              {{ auth()->user()->entreprise->ville ?? '' }}
              @if(auth()->user()->entreprise->email)<br>{{ auth()->user()->entreprise->email }}@endif
            </div>
          </div>
          <div>
            <div class="doc-party-label">À</div>
            <div class="doc-party-name">{{ $facture->client->nom_affichage }}</div>
            <div class="doc-party-info">
              @if($facture->client->email){{ $facture->client->email }}<br>@endif
              @if($facture->client->telephone){{ $facture->client->telephone }}<br>@endif
              @if($facture->client->adresse_ligne1){{ $facture->client->adresse_ligne1 }}@endif
            </div>
          </div>
        </div>

        {{-- Blocs spéciaux --}}
        @if($facture->type_facture === 'avoir' && $facture->factureParente)
        <div class="type-bloc avoir">
          <div class="type-bloc-title">↩️ Avoir sur {{ $facture->factureParente->numero_facture }}</div>
          @if($facture->motif_avoir)
          <div class="type-bloc-sub">Motif : {{ $facture->motif_avoir }}</div>
          @endif
          <div style="margin-top:6px">
            <a href="{{ route('factures.show', $facture->factureParente) }}" class="type-bloc-sub">→ Voir la facture originale</a>
          </div>
        </div>
        @endif

        @if($facture->type_facture === 'acompte' && $facture->factureParente)
        <div class="type-bloc acompte">
          <div class="type-bloc-title">💰 Acompte sur {{ $facture->factureParente->numero_facture }}</div>
          <div class="type-bloc-sub">
            Facture principale : {{ number_format($facture->factureParente->total_ttc,0,',',' ') }} XOF
            @if($facture->type_acompte === 'pourcentage') · {{ $facture->valeur_acompte }}% @endif
            · Acompte : <strong>{{ number_format($facture->total_ttc,0,',',' ') }} XOF</strong>
          </div>
          <div style="margin-top:6px">
            <a href="{{ route('factures.show', $facture->factureParente) }}" class="type-bloc-sub">→ Voir la facture principale</a>
          </div>
        </div>
        @endif

        @if($facture->type_facture === 'solde' && $facture->factureParente)
        <div class="type-bloc solde">
          <div class="type-bloc-title">🔒 Solde de {{ $facture->factureParente->numero_facture }}</div>
          <div class="type-bloc-sub">
            Total original : {{ number_format($facture->factureParente->total_ttc,0,',',' ') }} XOF
            · Solde : <strong>{{ number_format($facture->total_ttc,0,',',' ') }} XOF</strong>
          </div>
          <div style="margin-top:6px">
            <a href="{{ route('factures.show', $facture->factureParente) }}" class="type-bloc-sub">→ Voir la facture principale</a>
          </div>
        </div>
        @endif

        @if($facture->type_facture === 'proforma')
        <div class="type-bloc proforma">
          <div class="type-bloc-title">📝 Document pro forma</div>
          <div class="type-bloc-sub">Non comptable — ne peut pas servir de justificatif de paiement.</div>
        </div>
        @endif

        @if($facture->type_facture === 'recurrente' && $cfg)
        <div class="type-bloc recurrente">
          <div class="type-bloc-title">🔄 Facture récurrente — {{ ucfirst($cfg->frequence) }}</div>
          <div class="type-bloc-sub">
            Canal : @if($cfg->canal_envoi==='email') 📧 Email @elseif($cfg->canal_envoi==='whatsapp') 💬 WhatsApp @else 📧💬 Email + WhatsApp @endif
            · Prochain envoi : <strong>{{ $cfg->prochaine_generation_le?->format('d/m/Y') ?? '—' }}</strong>
            · {{ $cfg->total_genere }} envoi(s) effectué(s)
          </div>
        </div>
        @endif

        {{-- Lignes --}}
        @if($facture->lignes->count() > 0)
        <table class="lignes-table">
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
                <div style="font-weight:600;color:#1E293B">{{ $l->designation }}</div>
                @if($l->unite)<div style="font-size:11px;color:#94A3B8">{{ $l->unite }}</div>@endif
              </td>
              <td>{{ $l->quantite }}</td>
              <td>{{ number_format($l->prix_unitaire_ht,0,',',' ') }}</td>
              <td style="color:#1A3C6E">{{ number_format($l->total_ht_ligne,0,',',' ') }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
        @endif

        {{-- Totaux --}}
        <div style="display:flex;justify-content:flex-end">
          <div class="totaux-wrap">
            <div class="totaux-line"><span>Sous-total HT</span><span>{{ number_format($facture->total_ht,0,',',' ') }} XOF</span></div>
            @if($facture->total_remises_ht > 0)
            <div class="totaux-line"><span>Remises</span><span style="color:#EF4444">− {{ number_format($facture->total_remises_ht,0,',',' ') }} XOF</span></div>
            @endif
            <div class="totaux-line"><span>Taxes</span><span>+ {{ number_format($facture->total_taxes,0,',',' ') }} XOF</span></div>
            <div class="totaux-line final">
              <span>{{ $facture->type_facture === 'avoir' ? 'Total remboursé' : 'Total TTC' }}</span>
              <span>{{ number_format($facture->total_ttc,0,',',' ') }} XOF</span>
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
    </div>

    {{-- Documents liés --}}
    @if($facture->facturesFilles && $facture->facturesFilles->count() > 0)
    <div class="side-card" style="margin-bottom:16px">
      <div class="side-card-head">📎 Documents liés ({{ $facture->facturesFilles->count() }})</div>
      <div class="side-card-body">
        <table class="linked-table">
          <thead><tr><th>Numéro</th><th>Type</th><th>Statut</th><th style="text-align:right">Montant</th></tr></thead>
          <tbody>
            @foreach($facture->facturesFilles as $fille)
            <tr>
              <td><a href="{{ route('factures.show', $fille) }}" style="color:var(--blue);font-weight:700">{{ $fille->numero_facture }}</a></td>
              <td style="font-size:11px;color:var(--muted)">{{ $fille->type_label }}</td>
              <td><span class="tag {{ ($sc[$fille->statut]??['t-brouillon','—'])[0] }}" style="font-size:10px">{{ ($sc[$fille->statut]??['','—'])[1] }}</span></td>
              <td style="text-align:right;font-weight:700">{{ number_format($fille->total_ttc,0,',',' ') }} XOF</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endif
  </div>

  {{-- ══ COLONNE DROITE ══ --}}
  <div>

    <div class="side-card">
      <div class="side-card-head">Informations</div>
      <div class="side-card-body">
        <div class="side-row"><span class="side-row-lbl">Type</span><span class="side-row-val">{{ $emoji }} {{ $facture->type_label }}</span></div>
        <div class="side-row">
          <span class="side-row-lbl">Statut</span>
          <span class="tag {{ ($sc[$facture->statut]??['t-brouillon','—'])[0] }}" style="font-size:11px">{{ ($sc[$facture->statut]??['','—'])[1] }}</span>
        </div>
        <div class="side-row"><span class="side-row-lbl">Émission</span><span class="side-row-val">{{ $facture->date_emission?->format('d/m/Y') ?? '—' }}</span></div>
        @if($facture->date_echeance)
        <div class="side-row">
          <span class="side-row-lbl">Échéance</span>
          <span class="side-row-val" style="color:{{ $facture->statut==='en_retard'?'var(--danger)':'inherit' }}">{{ $facture->date_echeance->format('d/m/Y') }}</span>
        </div>
        @endif
        <div class="side-row"><span class="side-row-lbl">Devise</span><span class="side-row-val">{{ $facture->devise }}</span></div>
        <div class="side-row">
          <span class="side-row-lbl">Paiement</span>
          <span class="side-row-val" style="color:{{ $facture->paiement_auto?'var(--green)':'var(--blue)' }}">{{ $facture->paiement_auto ? '⚡ Auto' : '✋ Manuel' }}</span>
        </div>
        <div class="side-row"><span class="side-row-lbl">Créé par</span><span class="side-row-val">{{ $facture->createur?->prenom ?? 'Système' }}</span></div>
        @if($facture->motif_rejet)
        <div style="padding:12px 18px">
          <div class="alert err" style="margin:0"><strong>Rejet :</strong> {{ $facture->motif_rejet }}</div>
        </div>
        @endif
      </div>
    </div>

    {{-- Récurrente --}}
    @if($facture->type_facture === 'recurrente' && $cfg)
    <div class="side-card">
      <div class="side-card-head" style="background:linear-gradient(135deg,#EDE9FE,#DDD6FE);color:#4C1D95">🔄 Récurrence</div>
      <div class="side-card-body">
        <div class="side-row"><span class="side-row-lbl">Nom</span><span class="side-row-val">{{ $cfg->nom }}</span></div>
        <div class="side-row"><span class="side-row-lbl">Fréquence</span><span class="side-row-val" style="text-transform:capitalize">{{ $cfg->frequence }}</span></div>
        <div class="side-row">
          <span class="side-row-lbl">Canal</span>
          <span class="side-row-val">
            @if($cfg->canal_envoi==='email') 📧 Email
            @elseif($cfg->canal_envoi==='whatsapp') 💬 WhatsApp
            @else 📧💬 Les deux @endif
          </span>
        </div>
        <div class="side-row"><span class="side-row-lbl">Début</span><span class="side-row-val">{{ $cfg->date_debut?->format('d/m/Y') }}</span></div>
        @if($cfg->prochaine_generation_le)
        <div class="side-row">
          <span class="side-row-lbl">Prochain envoi</span>
          <span class="side-row-val" style="color:#5B21B6;font-weight:800">{{ $cfg->prochaine_generation_le->format('d/m/Y') }}</span>
        </div>
        @endif
        <div class="side-row"><span class="side-row-lbl">Envois effectués</span><span class="side-row-val">{{ $cfg->total_genere }}</span></div>
        @if($cfg->max_occurrences)<div class="side-row"><span class="side-row-lbl">Max</span><span class="side-row-val">{{ $cfg->max_occurrences }}</span></div>@endif
        @if($cfg->date_fin)<div class="side-row"><span class="side-row-lbl">Fin</span><span class="side-row-val">{{ $cfg->date_fin->format('d/m/Y') }}</span></div>@endif
        <div class="side-row">
          <span class="side-row-lbl">Statut</span>
          <span class="tag {{ $cfg->statut==='actif'?'t-paye':'t-brouillon' }}" style="font-size:10px">{{ ucfirst($cfg->statut) }}</span>
        </div>
      </div>
    </div>
    @endif

    {{-- Acompte détail --}}
    @if($facture->type_facture === 'acompte' && $facture->factureParente)
    <div class="side-card">
      <div class="side-card-head" style="background:linear-gradient(135deg,#FEF3C7,#FDE68A);color:#78350F">💰 Détail acompte</div>
      <div class="side-card-body">
        <div class="side-row">
          <span class="side-row-lbl">Facture principale</span>
          <a href="{{ route('factures.show', $facture->factureParente) }}" style="color:var(--blue);font-weight:700;font-size:12px">{{ $facture->factureParente->numero_facture }}</a>
        </div>
        <div class="side-row"><span class="side-row-lbl">Total principal</span><span class="side-row-val">{{ number_format($facture->factureParente->total_ttc,0,',',' ') }} XOF</span></div>
        @if($facture->type_acompte === 'pourcentage')
        <div class="side-row"><span class="side-row-lbl">Pourcentage</span><span class="side-row-val">{{ $facture->valeur_acompte }}%</span></div>
        @endif
        <div class="side-row">
          <span class="side-row-lbl" style="font-weight:700;color:#0F172A">Montant acompte</span>
          <span class="side-row-val" style="font-size:15px;font-weight:900;color:#1A3C6E">{{ number_format($facture->total_ttc,0,',',' ') }} XOF</span>
        </div>
      </div>
    </div>
    @endif

    {{-- Solde détail --}}
    @if($facture->type_facture === 'solde' && $facture->factureParente)
    <div class="side-card">
      <div class="side-card-head" style="background:linear-gradient(135deg,#CCFBF1,#99F6E4);color:#134E4A">🔒 Détail solde</div>
      <div class="side-card-body">
        <div class="side-row">
          <span class="side-row-lbl">Facture principale</span>
          <a href="{{ route('factures.show', $facture->factureParente) }}" style="color:var(--blue);font-weight:700;font-size:12px">{{ $facture->factureParente->numero_facture }}</a>
        </div>
        <div class="side-row"><span class="side-row-lbl">Total original</span><span class="side-row-val">{{ number_format($facture->factureParente->total_ttc,0,',',' ') }} XOF</span></div>
        <div class="side-row"><span class="side-row-lbl">Acomptes déduits</span><span class="side-row-val" style="color:#D97706">{{ number_format($facture->factureParente->total_ttc - $facture->total_ttc,0,',',' ') }} XOF</span></div>
        <div class="side-row">
          <span class="side-row-lbl" style="font-weight:700;color:#0F172A">Montant soldé</span>
          <span class="side-row-val" style="font-size:15px;font-weight:900;color:#065F46">{{ number_format($facture->total_ttc,0,',',' ') }} XOF</span>
        </div>
      </div>
    </div>
    @endif

    {{-- Avoir détail --}}
    @if($facture->type_facture === 'avoir' && $facture->factureParente)
    <div class="side-card">
      <div class="side-card-head" style="background:linear-gradient(135deg,#EDE9FE,#DDD6FE);color:#4C1D95">↩️ Détail avoir</div>
      <div class="side-card-body">
        <div class="side-row">
          <span class="side-row-lbl">Facture originale</span>
          <a href="{{ route('factures.show', $facture->factureParente) }}" style="color:var(--blue);font-weight:700;font-size:12px">{{ $facture->factureParente->numero_facture }}</a>
        </div>
        <div class="side-row"><span class="side-row-lbl">Montant original</span><span class="side-row-val">{{ number_format($facture->factureParente->total_ttc,0,',',' ') }} XOF</span></div>
        @if($facture->motif_avoir)
        <div class="side-row"><span class="side-row-lbl">Motif</span><span class="side-row-val">{{ $facture->motif_avoir }}</span></div>
        @endif
        <div class="side-row">
          <span class="side-row-lbl" style="font-weight:700;color:#0F172A">Montant remboursé</span>
          <span class="side-row-val" style="font-size:15px;font-weight:900;color:#6D28D9">{{ number_format($facture->total_ttc,0,',',' ') }} XOF</span>
        </div>
      </div>
    </div>
    @endif

  </div>
</div>

@include('factures.validate-modal')
@endsection