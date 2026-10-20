@extends('layouts.app')
@section('title','Clients')
@section('page-title','Clients')
@section('page-sub', $stats['total'].' clients · '.$stats['actifs'].' actifs')

@section('topbar-actions')
@if(auth()->user()->peutCreer())
<button class="tb-btn" onclick="openModal('mAdd')">＋ Nouveau client</button>
@endif
@endsection

@section('content')

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
  <div class="stat-card"><div class="stat-label">Total clients</div><div class="stat-value" style="color:var(--blue)">{{ $stats['total'] }}</div></div>
  <div class="stat-card"><div class="stat-label">Actifs</div><div class="stat-value" style="color:var(--green)">{{ $stats['actifs'] }}</div></div>
  <div class="stat-card"><div class="stat-label">Entreprises</div><div class="stat-value" style="color:var(--blue-mid)">{{ $stats['entreprises'] }}</div></div>
  <div class="stat-card"><div class="stat-label">Particuliers</div><div class="stat-value" style="color:var(--muted)">{{ $stats['particuliers'] }}</div></div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title">Liste des clients</div>
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
      <div class="search-box">
        <span class="search-ico">🔍</span>
        <input type="text" name="q" placeholder="Nom, code, email..." value="{{ request('q') }}" onchange="this.form.submit()">
      </div>
      <select name="type" onchange="this.form.submit()" style="padding:9px 12px;border:1.5px solid var(--border);border-radius:10px;font-family:'Poppins',sans-serif;font-size:13px;color:var(--text);background:#F8FAFF;outline:none">
        <option value="">Tous types</option>
        <option value="entreprise" {{ request('type')==='entreprise'?'selected':'' }}>Entreprises</option>
        <option value="particulier" {{ request('type')==='particulier'?'selected':'' }}>Particuliers</option>
      </select>
      <select name="statut" onchange="this.form.submit()" style="padding:9px 12px;border:1.5px solid var(--border);border-radius:10px;font-family:'Poppins',sans-serif;font-size:13px;color:var(--text);background:#F8FAFF;outline:none">
        <option value="">Tous statuts</option>
        <option value="actif"    {{ request('statut')==='actif'?'selected':'' }}>Actifs</option>
        <option value="inactif"  {{ request('statut')==='inactif'?'selected':'' }}>Archivés</option>
      </select>
    </form>
  </div>

  <div style="overflow-x:auto">
    <table>
      <thead>
        <tr>
          <th>Client</th><th>Contact</th><th>Groupe taxes</th>
          <th>Score</th><th>Solde dû</th><th>Statut</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($clients as $c)
        <tr onclick="window.location='{{ route('clients.show',$c) }}'" style="cursor:pointer">
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#1A3C6E,#2E5FA3);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#fff;flex-shrink:0">
                {{ strtoupper(substr($c->nom_affichage, 0, 2)) }}
              </div>
              <div>
                <div style="font-weight:700;font-size:13.5px">{{ $c->nom_affichage }}</div>
                <div style="font-size:11px;color:var(--muted);margin-top:1px">{{ $c->code_client }} · {{ $c->type_client === 'entreprise' ? '🏢' : '👤' }}</div>
              </div>
            </div>
          </td>
          <td>
            <div style="font-size:12.5px">{{ $c->email ?? '—' }}</div>
            <div style="font-size:11px;color:var(--muted)">{{ $c->telephone ?? '' }}</div>
          </td>
          <td style="font-size:12.5px;color:var(--muted)">{{ $c->groupeTaxe?->nom ?? '—' }}</td>
          <td>
            @if($c->score_fiabilite !== null)
            @php $sc=$c->score_fiabilite; $col=$sc>=80?'var(--green)':($sc>=50?'var(--warn)':'var(--danger)'); @endphp
            <div style="display:flex;align-items:center;gap:6px">
              <div style="width:32px;height:32px;border-radius:50%;border:2.5px solid {{ $col }};display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:{{ $col }}">{{ $sc }}</div>
            </div>
            @else
            <span style="color:var(--muted);font-size:12px">—</span>
            @endif
          </td>
          <td>
            @php $du = $c->montant_du_total; @endphp
            <span style="font-weight:700;color:{{ $du > 0 ? 'var(--danger)' : 'var(--green)' }};font-size:13px">
              {{ $du > 0 ? number_format($du,0,',',' ').' XOF' : '✓ Soldé' }}
            </span>
          </td>
          <td>
            <span class="tag {{ $c->est_actif ? 't-paye' : 't-annule' }}">{{ $c->est_actif ? 'Actif' : 'Archivé' }}</span>
          </td>
          <td onclick="event.stopPropagation()">
            <div style="display:flex;gap:5px">
              <a href="{{ route('clients.show',$c) }}" class="btn-icon" title="Voir">👁️</a>
              @if(auth()->user()->peutCreer())
              <button class="btn-icon" title="Modifier" onclick="openEdit({{ $c->id }})">✏️</button>
              <form method="POST" action="{{ route('clients.destroy',$c) }}" onsubmit="return confirm('Archiver ce client ?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn-icon del" title="Archiver">🗑️</button>
              </form>
              @endif
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--muted)">
          <div style="font-size:32px;margin-bottom:10px">👥</div>
          Aucun client trouvé<br>
          <button class="btn btn-primary btn-sm" style="margin-top:14px" onclick="openModal('mAdd')">Créer le premier client</button>
        </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  @if($clients->hasPages())
  <div class="pgn">
    <div class="pgn-info">{{ $clients->firstItem() }}–{{ $clients->lastItem() }} sur {{ $clients->total() }}</div>
    <div>{{ $clients->links() }}</div>
  </div>
  @endif
</div>

{{-- ── MODAL AJOUT ── --}}
<div class="overlay" id="mAdd">
  <div class="modal modal-lg">
    <div class="mhead blue">
      <button class="mhead-x" onclick="closeAll()">✕</button>
      <div class="mhead-title">➕ Nouveau client</div>
      <div class="mhead-sub">Remplissez les informations du client</div>
    </div>
    <form method="POST" action="{{ route('clients.store') }}">@csrf
      <div class="mbody">
        <div class="form-grid">
          <div class="fg">
            <label>Type <span>*</span></label>
            <select name="type_client" id="typeClient" onchange="toggleType()">
              <option value="entreprise">🏢 Entreprise</option>
              <option value="particulier">👤 Particulier</option>
            </select>
          </div>
          <div class="fg" id="raison-box">
            <label>Raison sociale <span>*</span></label>
            <input type="text" name="raison_sociale" placeholder="Nom de l'entreprise" value="{{ old('raison_sociale') }}">
          </div>
          <div class="fg">
            <label>Prénom</label>
            <input type="text" name="prenom" placeholder="Prénom du contact" value="{{ old('prenom') }}">
          </div>
          <div class="fg">
            <label id="nom-label">Nom du contact</label>
            <input type="text" name="nom" placeholder="Nom de famille" value="{{ old('nom') }}">
          </div>
          <div class="fg">
            <label>Email</label>
            <input type="email" name="email" placeholder="email@exemple.com" value="{{ old('email') }}">
          </div>
          <div class="fg">
            <label>Téléphone</label>
            <input type="text" name="telephone" placeholder="+227 XX XX XX XX" value="{{ old('telephone') }}">
          </div>
          <div class="fg">
            <label>Numéro fiscal (NIF/SIRET)</label>
            <input type="text" name="numero_fiscal" value="{{ old('numero_fiscal') }}">
          </div>
          
          <div class="fg form-full">
            <label>Adresse</label>
            <input type="text" name="adresse_ligne1" placeholder="Adresse de facturation" value="{{ old('adresse_ligne1') }}">
          </div>
          <div class="fg">
            <label>Ville</label>
            <input type="text" name="ville" placeholder="Niamey" value="{{ old('ville','Niamey') }}">
          </div>
          <div class="fg">
            <label>Pays <span>*</span></label>
            <select name="pays">
              <option value="NE" selected>🇳🇪 Niger</option>
              <option value="CI">🇨🇮 Côte d'Ivoire</option>
              <option value="SN">🇸🇳 Sénégal</option>
              <option value="ML">🇲🇱 Mali</option>
              <option value="BF">🇧🇫 Burkina Faso</option>
              <option value="TG">🇹🇬 Togo</option>
              <option value="BJ">🇧🇯 Bénin</option>
              <option value="FR">🇫🇷 France</option>
            </select>
          </div>
          <div class="fg">
            <label>Groupe de taxes</label>
            <select name="groupe_taxes_id">
              <option value="">— Groupe par défaut —</option>
              @foreach($groupesTaxes as $g)
              <option value="{{ $g->id }}">{{ $g->nom }} ({{ round($g->taux_total*100,1) }}%)</option>
              @endforeach
            </select>
          </div>
          <div class="fg">
            <label>Délai paiement (jours)</label>
            <input type="number" name="delai_paiement_jours" placeholder="30" min="0" max="365" value="{{ old('delai_paiement_jours') }}">
          </div>
          <div class="fg form-full">
            <label>Notes internes</label>
            <textarea name="notes" rows="2" placeholder="Informations utiles sur ce client...">{{ old('notes') }}</textarea>
          </div>
        </div>
      </div>
      <div class="mfoot">
        <button type="button" class="btn btn-ghost" onclick="closeAll()">Annuler</button>
        <button type="submit" class="btn btn-primary">✓ Créer le client</button>
      </div>
    </form>
  </div>
</div>

{{-- ── MODAL EDIT ── --}}
<div class="overlay" id="mEdit">
  <div class="modal modal-lg">
    <div class="mhead warn">
      <button class="mhead-x" onclick="closeAll()">✕</button>
      <div class="mhead-title">✏️ Modifier le client</div>
      <div class="mhead-sub" id="editSub"></div>
    </div>
    <form method="POST" id="editForm">@csrf @method('PUT')
      <div class="mbody">
        <div class="form-grid">
          <div class="fg"><label>Email</label><input type="email" name="email" id="e-email"></div>
          <div class="fg"><label>Téléphone</label><input type="text" name="telephone" id="e-tel"></div>
          <div class="fg"><label>Adresse</label><input type="text" name="adresse_ligne1" id="e-adr"></div>
          <div class="fg"><label>Ville</label><input type="text" name="ville" id="e-ville"></div>
          <div class="fg">
            <label>Pays</label>
            <select name="pays" id="e-pays">
              <option value="NE">🇳🇪 Niger</option><option value="CI">🇨🇮 Côte d'Ivoire</option>
              <option value="SN">🇸🇳 Sénégal</option><option value="FR">🇫🇷 France</option>
            </select>
          </div>
          <div class="fg">
            <label>Statut</label>
            <select name="est_actif" id="e-statut">
              <option value="1">✅ Actif</option>
              <option value="0">❌ Archivé</option>
            </select>
          </div>
          <div class="fg form-full"><label>Notes</label><textarea name="notes" id="e-notes" rows="2"></textarea></div>
          {{-- Champs cachés requis --}}
          <input type="hidden" name="type_client" id="e-type">
          
        </div>
      </div>
      <div class="mfoot">
        <button type="button" class="btn btn-ghost" onclick="closeAll()">Annuler</button>
        <button type="submit" class="btn btn-warning">💾 Enregistrer</button>
      </div>
    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
function toggleType(){
  const t=document.getElementById('typeClient').value;
  document.getElementById('raison-box').style.display=t==='entreprise'?'':'none';
  document.getElementById('nom-label').textContent=t==='particulier'?'Nom *':'Nom du contact';
}
toggleType();

const clients = @json($clientsJson);

function openEdit(id){
  const c=clients.find(x=>x.id===id);
  if(!c)return;
  document.getElementById('editSub').textContent=c.nom;
  document.getElementById('editForm').action=`/clients/${id}`;
  document.getElementById('e-email').value=c.email||'';
  document.getElementById('e-tel').value=c.telephone||'';
  document.getElementById('e-adr').value=c.adresse||'';
  document.getElementById('e-ville').value=c.ville||'';
  document.getElementById('e-pays').value=c.pays||'NE';
  document.getElementById('e-notes').value=c.notes||'';
  document.getElementById('e-statut').value=c.est_actif?'1':'0';
  document.getElementById('e-type').value=c.type;
  openModal('mEdit');
}
</script>
@endpush