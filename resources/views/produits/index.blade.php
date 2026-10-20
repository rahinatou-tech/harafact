@extends('layouts.app')
@section('title','Catalogue')
@section('page-title','Catalogue Produits & Services')
@section('page-sub', $stats['total'].' articles · '.$stats['actifs'].' actifs')
@section('topbar-actions')
@if(auth()->user()->peutCreer())
<button class="tb-btn" onclick="openModal('mAdd')">＋ Ajouter</button>
@endif
@endsection
@section('content')
<div class="stats-grid">
  <div class="stat-card"><div class="stat-label">Total articles</div><div class="stat-value" style="color:var(--blue)">{{ $stats['total'] }}</div></div>
  <div class="stat-card"><div class="stat-label">Actifs</div><div class="stat-value" style="color:var(--green)">{{ $stats['actifs'] }}</div></div>
  <div class="stat-card"><div class="stat-label">Produits</div><div class="stat-value" style="color:var(--blue-mid)">{{ $stats['produits'] }}</div></div>
  <div class="stat-card"><div class="stat-label">Services</div><div class="stat-value" style="color:var(--muted)">{{ $stats['services'] }}</div></div>
</div>
<div class="card">
  <div class="card-header">
    <div class="card-title">Catalogue {{ $entreprise->type_activite === 'produits' ? '(Produits)' : ($entreprise->type_activite === 'services' ? '(Services)' : '(Produits & Services)') }}</div>
    <form method="GET" style="display:flex;gap:8px">
      <div class="search-box"><span class="search-ico">🔍</span><input type="text" name="q" placeholder="Nom, référence..." value="{{ request('q') }}" onchange="this.form.submit()"></div>
      @if($entreprise->type_activite === 'les_deux')
      <select name="type" onchange="this.form.submit()" style="padding:9px 12px;border:1.5px solid var(--border);border-radius:10px;font-family:'Poppins',sans-serif;font-size:13px;color:var(--text);background:#F8FAFF;outline:none">
        <option value="">Tous</option><option value="produit" {{ request('type')==='produit'?'selected':'' }}>📦 Produits</option><option value="service" {{ request('type')==='service'?'selected':'' }}>⚙️ Services</option>
      </select>
      @endif
    </form>
  </div>
  <div style="overflow-x:auto">
    <table>
      <thead><tr><th>Article</th><th>Type</th><th>Unité</th><th>Prix HT</th><th>Taxe</th><th>Stock</th><th>Statut</th><th>Actions</th></tr></thead>
      <tbody>
        @forelse($produits as $p)
        <tr>
          <td><div style="font-weight:700">{{ $p->nom }}</div><div style="font-size:11px;color:var(--muted)">{{ $p->reference ?? '—' }}{{ $p->code_comptable ? ' · '.$p->code_comptable : '' }}</div></td>
          <td><span style="font-size:12px">{{ $p->type==='produit'?'📦 Produit':'⚙️ Service' }}</span></td>
          <td style="font-size:13px;color:var(--muted)">{{ $p->unite ?? '—' }}</td>
          <td style="font-weight:800;color:var(--blue)">{{ number_format($p->prix_unitaire_ht,0,',',' ') }} XOF</td>
          <td style="font-size:12.5px;color:var(--muted)">{{ $p->groupeTaxe?->nom ?? '—' }}</td>
          <td style="font-size:13px">{{ $p->stock ?? '—' }}</td>
          <td><span class="tag {{ $p->est_actif?'t-paye':'t-annule' }}">{{ $p->est_actif?'Actif':'Inactif' }}</span></td>
          <td>
            <div style="display:flex;gap:5px">
              @if(auth()->user()->peutCreer())
              <button class="btn-icon" onclick="openEdit({{ $p->id }})">✏️</button>
              <form method="POST" action="{{ route('produits.destroy',$p) }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')<button type="submit" class="btn-icon del">🗑️</button></form>
              @endif
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--muted)"><div style="font-size:32px;margin-bottom:10px">📦</div>Aucun article<br><button class="btn btn-primary btn-sm" style="margin-top:14px" onclick="openModal('mAdd')">Ajouter le premier</button></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($produits->hasPages())<div class="pgn"><div class="pgn-info">{{ $produits->firstItem() }}–{{ $produits->lastItem() }} sur {{ $produits->total() }}</div><div>{{ $produits->links() }}</div></div>@endif
</div>

<div class="overlay" id="mAdd">
  <div class="modal modal-md">
    <div class="mhead blue"><button class="mhead-x" onclick="closeAll()">✕</button><div class="mhead-title">➕ Nouveau produit/service</div></div>
    <form method="POST" action="{{ route('produits.store') }}">@csrf
      <div class="mbody">
        <div class="form-grid">
          <div class="fg"><label>Nom <span>*</span></label><input type="text" name="nom" required value="{{ old('nom') }}"></div>
          <div class="fg"><label>Référence</label><input type="text" name="reference" placeholder="PRD-001" value="{{ old('reference') }}"></div>
          <div class="fg"><label>Type <span>*</span></label>
            <select name="type">
              @if($entreprise->type_activite !== 'services')<option value="produit">📦 Produit</option>@endif
              @if($entreprise->type_activite !== 'produits')<option value="service">⚙️ Service</option>@endif
            </select>
          </div>
          <div class="fg"><label>Unité</label><input type="text" name="unite" placeholder="heure, jour, unité, forfait..." value="{{ old('unite') }}"></div>
          <div class="fg"><label>Prix unitaire HT (XOF) <span>*</span></label><input type="number" name="prix_unitaire_ht" min="0" step="1" required value="{{ old('prix_unitaire_ht') }}"></div>
          <div class="fg"><label>Groupe de taxes</label>
            <select name="groupe_taxes_id">
              <option value="">— Sans taxe —</option>
              @foreach($groupesTaxes as $g)<option value="{{ $g->id }}">{{ $g->nom }} ({{ round($g->taux_total*100,1) }}%)</option>@endforeach
            </select>
          </div>
          <div class="fg"><label>Code comptable OHADA</label><input type="text" name="code_comptable" placeholder="706" value="{{ old('code_comptable') }}"></div>
          <div class="fg"><label>Stock initial</label><input type="number" name="stock" min="0" value="{{ old('stock') }}"></div>
          <div class="fg form-full"><label>Description</label><textarea name="description" rows="2" placeholder="Description longue...">{{ old('description') }}</textarea></div>
        </div>
      </div>
      <div class="mfoot"><button type="button" class="btn btn-ghost" onclick="closeAll()">Annuler</button><button type="submit" class="btn btn-primary">✓ Ajouter</button></div>
    </form>
  </div>
</div>

<div class="overlay" id="mEdit">
  <div class="modal modal-md">
    <div class="mhead warn"><button class="mhead-x" onclick="closeAll()">✕</button><div class="mhead-title">✏️ Modifier</div><div class="mhead-sub" id="e-sub"></div></div>
    <form method="POST" id="eForm">@csrf @method('PUT')
      <div class="mbody">
        <div class="form-grid">
          <div class="fg"><label>Nom</label><input type="text" name="nom" id="e-nom"></div>
          <div class="fg"><label>Prix HT (XOF)</label><input type="number" name="prix_unitaire_ht" id="e-prix" min="0" step="1"></div>
          <div class="fg"><label>Unité</label><input type="text" name="unite" id="e-unite"></div>
          <div class="fg"><label>Statut</label><select name="est_actif" id="e-statut"><option value="1">✅ Actif</option><option value="0">❌ Inactif</option></select></div>
          <input type="hidden" name="type" id="e-type">
        </div>
      </div>
      <div class="mfoot"><button type="button" class="btn btn-ghost" onclick="closeAll()">Annuler</button><button type="submit" class="btn btn-warning">💾 Enregistrer</button></div>
    </form>
  </div>
</div>
@endsection
@push('scripts')
<script>
const prods=@json($produitsJson);
function openEdit(id){
  const p=prods.find(x=>x.id===id);if(!p)return;
  document.getElementById('e-sub').textContent=p.nom;
  document.getElementById('eForm').action=`/produits/${id}`;
  document.getElementById('e-nom').value=p.nom;
  document.getElementById('e-prix').value=p.prix;
  document.getElementById('e-unite').value=p.unite||'';
  document.getElementById('e-statut').value=p.statut?'1':'0';
  document.getElementById('e-type').value=p.type;
  openModal('mEdit');
}
</script>
@endpush