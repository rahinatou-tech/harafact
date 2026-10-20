@extends('layouts.app')
@section('title','Paiements')
@section('page-title','Suivi des paiements')
@section('page-sub','Acomptes et factures récurrentes uniquement')

@section('content')

<div class="stats-grid">
  <div class="stat-card"><div class="stat-label">Encaissé ce mois</div><div class="stat-value" style="color:var(--green)">{{ number_format($stats['total_encaisse_mois']/1000,0,',',' ') }}K</div><div class="stat-foot">XOF saisis manuellement</div></div>
  <div class="stat-card"><div class="stat-label">Factures à suivre</div><div class="stat-value" style="color:var(--blue)">{{ $stats['factures_a_suivre'] }}</div></div>
  <div class="stat-card"><div class="stat-label">Total restant dû</div><div class="stat-value" style="color:var(--warn)">{{ number_format($stats['total_restant_du']/1000,0,',',' ') }}K</div><div class="stat-foot">XOF à encaisser</div></div>
  <div class="stat-card"><div class="stat-label">En retard</div><div class="stat-value" style="color:{{ $stats['en_retard']>0?'var(--danger)':'var(--green)' }}">{{ $stats['en_retard'] }}</div></div>
</div>

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:16px">

  {{-- Factures à suivre --}}
  <div>
    <div class="card">
      <div class="card-header"><div class="card-title">⚡ Factures nécessitant un suivi</div></div>

      @forelse($factures as $f)
      @php
        $isRetard = $f->statut === 'en_retard';
        $pct = $f->getPourcentagePaye();
      @endphp
      <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px">
          <div>
            <div style="display:flex;align-items:center;gap:8px">
              <span style="font-size:13px;font-weight:800;font-family:monospace;color:var(--text)">{{ $f->numero_facture }}</span>
              <span class="tag {{ $f->type_facture==='acompte'?'t-partiel':'t-envoye' }}" style="font-size:10px">{{ ucfirst($f->type_facture) }}</span>
              @if($isRetard)<span class="tag t-retard" style="font-size:10px">⚠ Retard</span>@endif
            </div>
            <div style="font-size:12px;color:var(--muted);margin-top:3px">
              {{ $f->client->nom_affichage ?? '—' }}
              @if($f->date_echeance)
                · Échéance : <strong style="color:{{ $isRetard?'var(--danger)':'var(--text)' }}">{{ $f->date_echeance->format('d/m/Y') }}</strong>
              @endif
            </div>
          </div>
          <div style="text-align:right">
            <div style="font-size:15px;font-weight:900;color:var(--blue)">{{ number_format($f->total_ttc,0,',',' ') }} XOF</div>
            <div style="font-size:11px;color:var(--muted);margin-top:1px">Total TTC</div>
          </div>
        </div>

        <div class="progress" style="margin-bottom:6px">
          <div class="progress-fill" style="width:{{ $pct }}%;background:{{ $pct>=100?'#00875A':($isRetard?'#EF4444':'#2E5FA3') }}"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:11.5px;margin-bottom:12px">
          <span style="color:var(--muted)">{{ $pct }}% encaissé · <strong style="color:var(--green)">{{ number_format($f->montant_paye,0,',',' ') }} XOF</strong></span>
          <span style="color:var(--muted)">Restant dû : <strong style="color:{{ $f->montant_du>0?'var(--danger)':'var(--green)' }}">{{ number_format($f->montant_du,0,',',' ') }} XOF</strong></span>
        </div>

        @if($f->montant_du > 0 && auth()->user()->peutPayer())
        <button class="btn btn-primary btn-sm" onclick="openPaiement({{ $f->id }},'{{ $f->numero_facture }}',{{ $f->montant_du }})">
          ＋ Saisir un versement
        </button>
        @elseif($f->montant_du <= 0)
        <span style="font-size:12px;color:var(--green);font-weight:600">✅ Entièrement soldée</span>
        @endif
      </div>
      @empty
      <div style="text-align:center;padding:40px;color:var(--muted)">
        <div style="font-size:32px;margin-bottom:10px">✅</div>
        <div style="font-size:14px;font-weight:600">Aucune facture en attente de paiement</div>
        <div style="font-size:12px;margin-top:4px">Toutes les factures sont à jour</div>
      </div>
      @endforelse

      @if($factures->hasPages())
      <div class="pgn">
        <div class="pgn-info">{{ $factures->firstItem() }}–{{ $factures->lastItem() }} sur {{ $factures->total() }}</div>
        <div>{{ $factures->links() }}</div>
      </div>
      @endif
    </div>
  </div>

  {{-- Derniers paiements --}}
  <div>
    <div class="card">
      <div class="card-header"><div class="card-title">Derniers versements saisis</div></div>
      @forelse($derniers_paiements as $p)
      <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 20px;border-bottom:1px solid var(--border)">
        <div>
          <div style="font-size:13px;font-weight:700;color:var(--green)">+ {{ number_format($p->montant,0,',',' ') }} XOF</div>
          <div style="font-size:11px;color:var(--muted);margin-top:2px">
            {{ $p->facture->numero_facture ?? '—' }} ·
            {{ match($p->type_paiement){'virement'=>'Virement','mobile_money'=>'Mobile Money','especes'=>'Espèces','cheque'=>'Chèque',default=>ucfirst($p->type_paiement)} }}
          </div>
          @if($p->libelle_paiement)<div style="font-size:11px;color:var(--muted)">{{ $p->libelle_paiement }}</div>@endif
        </div>
        <div style="text-align:right">
          <div style="font-size:11.5px;font-weight:600;color:var(--muted)">{{ $p->date_paiement->format('d/m/Y') }}</div>
          <div style="font-size:10.5px;color:var(--muted)">par {{ $p->saiseur?->prenom ?? 'Système' }}</div>
          @if(auth()->user()->isAdmin()||auth()->user()->isDG())
          <form method="POST" action="{{ route('paiements.destroy',$p) }}" onsubmit="return confirm('Annuler ce paiement ?')" style="margin-top:4px">
            @csrf @method('DELETE')
            <button type="submit" style="font-size:10px;color:var(--danger);background:none;border:none;cursor:pointer;padding:2px">✕ Annuler</button>
          </form>
          @endif
        </div>
      </div>
      @empty
      <div style="text-align:center;padding:30px;color:var(--muted);font-size:13px">
        Aucun versement saisi pour l'instant
      </div>
      @endforelse
    </div>
  </div>
</div>

{{-- ── MODAL SAISIE PAIEMENT ── --}}
<div class="overlay" id="mPaiement">
  <div class="modal modal-md">
    <div class="mhead green">
      <button class="mhead-x" onclick="closeAll()">✕</button>
      <div class="mhead-title">💰 Saisir un versement</div>
      <div class="mhead-sub" id="m-facture-num">—</div>
    </div>
    <form method="POST" action="{{ route('paiements.store') }}">@csrf
      <input type="hidden" name="facture_id" id="m-fid">
      <div class="mbody">
        <div class="form-grid">
          <div class="fg">
            <label>Montant (XOF) <span>*</span></label>
            <input type="number" name="montant" id="m-montant" min="1" step="1" required>
            <div style="font-size:11px;color:var(--muted);margin-top:4px">Restant dû : <strong id="m-du">—</strong></div>
          </div>
          <div class="fg">
            <label>Date du paiement <span>*</span></label>
            <input type="date" name="date_paiement" value="{{ now()->toDateString() }}" required>
          </div>
          <div class="fg">
            <label>Mode de paiement <span>*</span></label>
            <select name="type_paiement" onchange="toggleLibelle(this.value)" required>
              <option value="mobile_money">📱 Mobile Money</option>
              <option value="especes">💵 Espèces</option>
              <option value="virement">🏦 Virement bancaire</option>
              <option value="cheque">📝 Chèque</option>
              <option value="carte">💳 Carte bancaire</option>
              <option value="autre">Autre</option>
            </select>
          </div>
          <div class="fg" id="libelle-box">
            <label>Préciser (ex: Orange Money, BIA...)</label>
            <input type="text" name="libelle_paiement" placeholder="Nom du service ou de la banque">
          </div>
          <div class="fg">
            <label>Référence externe</label>
            <input type="text" name="reference_externe" placeholder="N° transaction, chèque...">
          </div>
          <div class="fg">
            <label>Notes</label>
            <input type="text" name="notes" placeholder="Commentaire optionnel">
          </div>
        </div>
      </div>
      <div class="mfoot">
        <button type="button" class="btn btn-ghost" onclick="closeAll()">Annuler</button>
        <button type="submit" class="btn btn-success">✓ Enregistrer le paiement</button>
      </div>
    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
function openPaiement(id, num, du) {
  document.getElementById('m-fid').value = id;
  document.getElementById('m-facture-num').textContent = num;
  document.getElementById('m-montant').value = Math.round(du);
  document.getElementById('m-montant').max = Math.round(du);
  document.getElementById('m-du').textContent = Math.round(du).toLocaleString('fr-FR') + ' XOF';
  openModal('mPaiement');
}
function toggleLibelle(v){
  document.getElementById('libelle-box').style.display=['mobile_money','virement','cheque'].includes(v)?'block':'none';
}
toggleLibelle('mobile_money');
</script>
@endpush
