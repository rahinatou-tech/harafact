@extends('layouts.app')
@section('title','Nouveau document')
@section('page-title','Nouveau document')
@section('page-sub','Formulaire adaptatif selon le type')

@section('topbar-actions')
<a href="{{ route('factures.index') }}" class="tb-btn ghost">← Retour</a>
@endsection

@section('content')

<form method="POST" action="{{ route('factures.store') }}" id="formDoc">@csrf

<div style="display:grid;grid-template-columns:1fr 360px;gap:16px;align-items:start">

  {{-- Colonne gauche --}}
  <div>

    {{-- Type de document --}}
    <div class="card" style="margin-bottom:16px">
      <div class="card-header"><div class="card-title">Type de document</div></div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px">
          @php
            $types = [
              'simple'     => ['emoji'=>'📄','label'=>'Facture simple',  'color'=>'#1A3C6E','bg'=>'#DBEAFE','desc'=>'Standard'],
              'proforma'   => ['emoji'=>'📝','label'=>'Pro forma',        'color'=>'#0369A1','bg'=>'#E0F2FE','desc'=>'Avant-contrat'],
              'avoir'      => ['emoji'=>'↩️','label'=>'Avoir',            'color'=>'#7C3AED','bg'=>'#EDE9FE','desc'=>'Note de crédit'],
              'acompte'    => ['emoji'=>'💰','label'=>'Acompte',          'color'=>'#B45309','bg'=>'#FEF3C7','desc'=>'Versement partiel'],
              'solde'      => ['emoji'=>'🔒','label'=>'Solde',            'color'=>'#134E4A','bg'=>'#CCFBF1','desc'=>'Clôture'],
              'recurrente' => ['emoji'=>'🔄','label'=>'Récurrente',       'color'=>'#5B21B6','bg'=>'#EDE9FE','desc'=>'Envoi automatique'],
            ];
            $selectedType = old('type_facture', request('type', 'simple'));
          @endphp
          @foreach($types as $val => $t)
          <label style="display:block;cursor:pointer">
            <input type="radio" name="type_facture" value="{{ $val }}"
              {{ $val===$selectedType?'checked':'' }}
              onchange="onTypeChange('{{ $val }}')"
              style="display:none">
            <div class="type-card" id="tc-{{ $val }}"
              style="border:2px solid {{ $val===$selectedType ? $t['color'] : 'var(--border)' }};background:{{ $val===$selectedType ? $t['bg'] : '#fff' }};border-radius:12px;padding:12px;text-align:center;transition:all 0.15s">
              <div style="font-size:22px;margin-bottom:5px">{{ $t['emoji'] }}</div>
              <div style="font-size:12px;font-weight:700;color:{{ $val===$selectedType ? $t['color'] : 'var(--text)' }}">{{ $t['label'] }}</div>
              <div style="font-size:10px;color:var(--muted);margin-top:2px">{{ $t['desc'] }}</div>
            </div>
          </label>
          @endforeach
        </div>
      </div>
    </div>

    {{-- Infos générales (toujours visible) --}}
    <div class="card" style="margin-bottom:16px">
      <div class="card-header"><div class="card-title" id="titre-section-infos">Informations générales</div></div>
      <div class="card-body">
        <div class="form-grid">
          <div class="fg">
            <label>Client <span>*</span></label>
            <select name="client_id" required>
              <option value="">— Sélectionner un client —</option>
              @foreach($clients as $c)
              <option value="{{ $c->id }}" {{ old('client_id')==$c->id?'selected':'' }}>
                {{ $c->nom_affichage }} ({{ $c->code_client }})
              </option>
              @endforeach
            </select>
          </div>
          <div class="fg">
            <label>Devise</label>
            <select name="devise">
              <option value="XOF" selected>XOF — Franc CFA</option>
              <option value="EUR">EUR — Euro</option>
              <option value="USD">USD — Dollar US</option>
            </select>
          </div>
          <div class="fg">
            <label>Date d'émission <span>*</span></label>
            <input type="date" name="date_emission" value="{{ old('date_emission', now()->toDateString()) }}" required>
          </div>
          <div class="fg" id="section-echeance">
            <label>Date d'échéance</label>
            <input type="date" name="date_echeance" value="{{ old('date_echeance', now()->addDays(30)->toDateString()) }}">
          </div>
        </div>
      </div>
    </div>

    {{-- Zone AVOIR --}}
    <div class="card" id="zone-avoir" style="margin-bottom:16px;display:none">
      <div class="card-header" style="background:linear-gradient(135deg,#EDE9FE,#DDD6FE)">
        <div class="card-title" style="color:#5B21B6">↩️ Facture à corriger</div>
      </div>
      <div class="card-body">
        <div class="form-grid">
          <div class="fg">
            <label>Facture originale <span>*</span></label>
            <select name="facture_parente_id" id="sel-parente-avoir" disabled>
              <option value="">— Sélectionner la facture —</option>
              @foreach($factures_parentes as $fp)
              <option value="{{ $fp->id }}">{{ $fp->numero_facture }} · {{ $fp->client->nom_affichage }} · {{ number_format($fp->total_ttc,0,',',' ') }} XOF</option>
              @endforeach
            </select>
          </div>
          <div class="fg form-full">
            <label>Motif de l'avoir <span>*</span></label>
            <input type="text" name="motif_avoir" id="motif-avoir" disabled placeholder="Ex: Erreur de facturation, retour produit…">
          </div>
        </div>
      </div>
    </div>

    {{-- Zone ACOMPTE --}}
    <div class="card" id="zone-acompte" style="margin-bottom:16px;display:none">
      <div class="card-header" style="background:linear-gradient(135deg,#FEF3C7,#FDE68A)">
        <div class="card-title" style="color:#B45309">💰 Paramètres Acompte</div>
      </div>
      <div class="card-body">
        <div class="form-grid cols3">
          <div class="fg">
            <label>Facture principale <span>*</span></label>
            <select name="facture_parente_id" id="sel-parente-acompte" disabled onchange="calcAcompte()">
              <option value="">— Facture liée —</option>
              @foreach($factures_parentes as $fp)
              <option value="{{ $fp->id }}" data-ttc="{{ $fp->total_ttc }}">{{ $fp->numero_facture }} · {{ number_format($fp->total_ttc,0,',',' ') }} XOF</option>
              @endforeach
            </select>
          </div>
          <div class="fg">
            <label>Type d'acompte <span>*</span></label>
            <select name="type_acompte" id="acp-type" disabled onchange="updateAcpLabel()">
              <option value="pourcentage">Pourcentage (%)</option>
              <option value="montant_fixe">Montant fixe (XOF)</option>
            </select>
          </div>
          <div class="fg">
            <label id="acp-val-label">Valeur (%) <span>*</span></label>
            <input type="number" name="valeur_acompte" id="acp-val" value="30" min="0.01" step="0.01" disabled oninput="calcAcompte()">
          </div>
        </div>
        <div class="alert ok" id="acp-info">
          Sélectionnez une facture principale pour calculer l'acompte.
        </div>
      </div>
    </div>

    {{-- Zone SOLDE --}}
    <div class="card" id="zone-solde" style="margin-bottom:16px;display:none">
      <div class="card-header" style="background:linear-gradient(135deg,#CCFBF1,#99F6E4)">
        <div class="card-title" style="color:#134E4A">🔒 Facture à solder</div>
      </div>
      <div class="card-body">
        <div class="fg">
          <label>Facture principale <span>*</span></label>
          <select name="facture_parente_id" id="sel-parente-solde" disabled>
            <option value="">— Sélectionner —</option>
            @foreach($factures_parentes as $fp)
            <option value="{{ $fp->id }}" data-ttc="{{ $fp->total_ttc }}">{{ $fp->numero_facture }} · {{ $fp->client->nom_affichage }} · {{ number_format($fp->total_ttc,0,',',' ') }} XOF</option>
            @endforeach
          </select>
        </div>
        <div class="alert ok" style="margin-top:10px">
          🔒 Montant calculé automatiquement : Total − Acomptes déjà encaissés.
        </div>
      </div>
    </div>

    {{-- Zone RÉCURRENTE --}}
    <div class="card" id="zone-recurrente" style="margin-bottom:16px;display:none">
      <div class="card-header" style="background:linear-gradient(135deg,#EDE9FE,#DDD6FE)">
        <div class="card-title" style="color:#5B21B6">🔄 Paramètres Récurrence</div>
      </div>
      <div class="card-body">
        <div class="form-grid">
          <div class="fg form-full">
            <label>Nom de la récurrence <span>*</span></label>
            <input type="text" name="nom_recurrence" id="nom_recurrence" disabled placeholder="Ex: Loyer mensuel, Abonnement maintenance…">
          </div>
          <div class="fg">
            <label>Fréquence <span>*</span></label>
            <select name="frequence" id="frequence" disabled>
              <option value="mensuelle" selected>Mensuelle</option>
              <option value="hebdomadaire">Hebdomadaire</option>
              <option value="trimestrielle">Trimestrielle</option>
              <option value="semestrielle">Semestrielle</option>
              <option value="annuelle">Annuelle</option>
            </select>
          </div>
          <div class="fg">
            <label>Date du premier envoi <span>*</span></label>
            <input type="date" name="date_debut" id="date_debut" disabled value="{{ now()->toDateString() }}">
          </div>
          <div class="fg">
            <label>Jour du mois d'envoi</label>
            <input type="number" name="jour_du_mois" id="jour_du_mois" disabled min="1" max="28" placeholder="Ex: 1, 15, 28…">
          </div>
          <div class="fg">
            <label>Date de fin (optionnel)</label>
            <input type="date" name="date_fin" id="date_fin" disabled>
          </div>
          <div class="fg">
            <label>Max occurrences (optionnel)</label>
            <input type="number" name="max_occurrences" id="max_occurrences" disabled min="1" placeholder="Illimité si vide">
          </div>
        </div>

        <div class="fg" style="margin-top:8px">
          <label>Canal d'envoi automatique <span>*</span></label>
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:6px">
            <label id="canal-email" style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1.5px solid var(--blue-mid);border-radius:10px;cursor:pointer;background:#DBEAFE">
              <input type="radio" name="canal_envoi" value="email" checked disabled onchange="selectCanal('email')" style="accent-color:var(--blue)">
              <span style="font-size:13px;font-weight:600;color:var(--blue)">📧 Email</span>
            </label>
            <label id="canal-whatsapp" style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;background:#fff">
              <input type="radio" name="canal_envoi" value="whatsapp" disabled onchange="selectCanal('whatsapp')" style="accent-color:var(--blue)">
              <span style="font-size:13px;font-weight:600;color:var(--muted)">💬 WhatsApp</span>
            </label>
            <label id="canal-both" style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;background:#fff">
              <input type="radio" name="canal_envoi" value="both" disabled onchange="selectCanal('both')" style="accent-color:var(--blue)">
              <span style="font-size:13px;font-weight:600;color:var(--muted)">📧💬 Les deux</span>
            </label>
          </div>
        </div>

        <div class="alert ok" style="margin-top:12px">
          🔄 Générée et envoyée automatiquement selon la fréquence. Aucune intervention manuelle.
        </div>
      </div>
    </div>

    {{-- Lignes produits/services (simple, proforma, avoir, recurrente) --}}
    <div class="card" id="zone-lignes" style="margin-bottom:16px">
      <div class="card-header">
        <div class="card-title" id="titre-lignes">Produits & Services</div>
        <button type="button" class="btn btn-ghost btn-sm btn-add-ligne" onclick="addLigne()">＋ Ajouter une ligne</button>
      </div>
      <div class="card-body">
        <div class="ligne-head">
          <span>Désignation</span><span>Qté</span><span>Prix unit. HT</span><span>Remise%</span><span>Total HT</span><span></span>
        </div>
        <div id="lignesContainer"></div>
      </div>
    </div>

    {{-- Notes (toujours visible) --}}
    <div class="card" style="margin-bottom:16px">
      <div class="card-header"><div class="card-title">Notes</div></div>
      <div class="card-body">
        <div class="form-grid">
          <div class="fg">
            <label>Notes visibles (sur la facture)</label>
            <textarea name="notes" rows="2" placeholder="Conditions particulières…"></textarea>
          </div>
          <div class="fg">
            <label>Notes internes (non imprimées)</label>
            <textarea name="notes_internes" rows="2" placeholder="Notes confidentielles…"></textarea>
          </div>
        </div>
      </div>
    </div>

  </div>

  {{-- Colonne droite — Récapitulatif --}}
  <div style="position:sticky;top:76px">
    <div class="card">
      <div class="card-header"><div class="card-title">Récapitulatif</div></div>
      <div class="card-body">
        <div class="totaux-box" style="margin-bottom:16px">
          <div class="totaux-row"><span class="totaux-lbl">Sous-total HT</span><span class="totaux-val" id="t-ht">0 XOF</span></div>
          <div class="totaux-row"><span class="totaux-lbl">Remises</span><span class="totaux-val" id="t-rem">— 0 XOF</span></div>
          <div class="totaux-row"><span class="totaux-lbl">TVA</span><span class="totaux-val" id="t-tax">+ 0 XOF</span></div>
          <div class="totaux-row big">
            <span class="totaux-lbl" style="font-size:14px;font-weight:800;color:var(--text)">Total TTC</span>
            <span class="totaux-val main" id="t-ttc">0 XOF</span>
          </div>
        </div>

        <div id="paiement-info" class="alert ok" style="margin-bottom:16px">
          ⏳ En attente de validation par le comptable
        </div>

        <div style="display:flex;flex-direction:column;gap:10px">
          <button type="submit" class="btn btn-primary" style="width:100%" id="btn-submit">
            📤 Créer la facture
          </button>
          <a href="{{ route('factures.index') }}" class="btn btn-ghost" style="width:100%;text-align:center">Annuler</a>
        </div>
      </div>
    </div>
  </div>

</div>
</form>

@endsection

@push('scripts')
<script>
const produits = @json($produitsJson);

const typeColors = {
  simple:     {color:'#1A3C6E', bg:'#DBEAFE'},
  proforma:   {color:'#0369A1', bg:'#E0F2FE'},
  avoir:      {color:'#7C3AED', bg:'#EDE9FE'},
  acompte:    {color:'#B45309', bg:'#FEF3C7'},
  solde:      {color:'#134E4A', bg:'#CCFBF1'},
  recurrente: {color:'#5B21B6', bg:'#EDE9FE'},
};

const config = {
  simple:     { titre:'Nouvelle facture simple',    lignes:true,  parente:false, recurrente:false, submitLabel:'📤 Créer la facture' },
  proforma:   { titre:'Nouvelle facture pro forma', lignes:true,  parente:false, recurrente:false, submitLabel:'📤 Créer la pro forma' },
  avoir:      { titre:'Nouvel avoir',               lignes:true,  parente:'avoir',  recurrente:false, submitLabel:'↩️ Créer l\'avoir' },
  acompte:    { titre:'Nouvelle facture d\'acompte',lignes:false, parente:'acompte',recurrente:false, submitLabel:'💰 Créer l\'acompte' },
  solde:      { titre:'Facture de solde',           lignes:false, parente:'solde',  recurrente:false, submitLabel:'🔒 Créer le solde' },
  recurrente: { titre:'Nouvelle facture récurrente',lignes:true,  parente:false, recurrente:true,  submitLabel:'🔄 Créer la récurrence' },
};

let lignes = [{produit_id:null,designation:'',qte:1,prix:0,remise:0,taux:0}];
let currentType = '{{ $selectedType }}';

function setDisabled(ids, disabled) {
  ids.forEach(id => { const el = document.getElementById(id); if(el) el.disabled = disabled; });
}

function onTypeChange(type) {
  currentType = type;
  const cfg = config[type];

  // Mise à jour titre topbar
  document.querySelector('.tb-title h1').textContent = cfg.titre;

  // Mise à jour carte sélectionnée
  document.querySelectorAll('.type-card').forEach(c => {
    c.style.border = '2px solid var(--border)';
    c.style.background = '#fff';
    c.querySelectorAll('div')[1].style.color = 'var(--text)';
  });
  const tc = document.getElementById('tc-' + type);
  const col = typeColors[type];
  tc.style.border = `2px solid ${col.color}`;
  tc.style.background = col.bg;
  tc.querySelector('div:nth-child(2)').style.color = col.color;

  // Masquer toutes les zones spécifiques
  ['avoir','acompte','solde','recurrente'].forEach(z => {
    document.getElementById('zone-' + z).style.display = 'none';
  });

  // Désactiver tous les champs spécifiques
  document.querySelectorAll('[name="facture_parente_id"]').forEach(el => el.disabled = true);
  setDisabled(['motif-avoir','sel-parente-avoir','sel-parente-acompte','acp-type','acp-val','sel-parente-solde'], true);
  setDisabled(['nom_recurrence','frequence','date_debut','jour_du_mois','date_fin','max_occurrences'], true);
  document.querySelectorAll('[name="canal_envoi"]').forEach(el => el.disabled = true);

  // Afficher et activer la zone parente si nécessaire
  if (cfg.parente) {
    document.getElementById('zone-' + cfg.parente).style.display = 'block';

    if (type === 'avoir') {
      setDisabled(['sel-parente-avoir','motif-avoir'], false);
    }
    if (type === 'acompte') {
      setDisabled(['sel-parente-acompte','acp-type','acp-val'], false);
    }
    if (type === 'solde') {
      document.getElementById('sel-parente-solde').disabled = false;
    }
  }

  // Afficher et activer la zone récurrente
  if (cfg.recurrente) {
    document.getElementById('zone-recurrente').style.display = 'block';
    setDisabled(['nom_recurrence','frequence','date_debut','jour_du_mois','date_fin','max_occurrences'], false);
    document.querySelectorAll('[name="canal_envoi"]').forEach(el => el.disabled = false);
  }

  // Lignes
  document.getElementById('zone-lignes').style.display = cfg.lignes ? 'block' : 'none';

  // Titre lignes
  const titreLignes = {
    simple:'Produits & Services', proforma:'Produits & Services',
    avoir:'Articles à rembourser', recurrente:'Produits & Services (modèle)',
  };
  if (document.getElementById('titre-lignes')) {
    document.getElementById('titre-lignes').textContent = titreLignes[type] || 'Produits & Services';
  }

  // Bouton submit
  document.getElementById('btn-submit').textContent = cfg.submitLabel;

  // Info paiement
  const pi = document.getElementById('paiement-info');
  const infos = {
    simple:     {cls:'alert ok', txt:'⏳ En attente de validation par le comptable'},
    proforma:   {cls:'alert ok', txt:'📝 Pro forma — pas de valeur comptable'},
    avoir:      {cls:'alert ok', txt:'↩️ Avoir — remboursement ou correction'},
    acompte:    {cls:'alert ok', txt:'💰 Acompte — versement partiel du client'},
    solde:      {cls:'alert ok', txt:'🔒 Solde — clôture du contrat'},
    recurrente: {cls:'alert ok', txt:'🔄 Envoi automatique selon la fréquence'},
  };
  const info = infos[type];
  pi.className = info.cls;
  pi.textContent = info.txt;
  if (type === 'recurrente') {
    pi.style.background = '#EDE9FE';
    pi.style.borderColor = '#C4B5FD';
    pi.style.color = '#5B21B6';
  } else {
    pi.style.background = '';
    pi.style.borderColor = '';
    pi.style.color = '';
  }
}

function selectCanal(canal) {
  ['email','whatsapp','both'].forEach(c => {
    const el = document.getElementById('canal-' + c);
    if (c === canal) {
      el.style.borderColor = 'var(--blue-mid)';
      el.style.background  = '#DBEAFE';
      el.querySelector('span').style.color = 'var(--blue)';
    } else {
      el.style.borderColor = 'var(--border)';
      el.style.background  = '#fff';
      el.querySelector('span').style.color = 'var(--muted)';
    }
  });
}

// Init
onTypeChange(currentType);
renderLignes();

function addLigne() {
  lignes.push({produit_id:null,designation:'',qte:1,prix:0,remise:0,taux:0});
  renderLignes();
}

function removeLigne(i) {
  if (lignes.length <= 1) return;
  lignes.splice(i, 1);
  renderLignes();
  calcTotaux();
}

function selectProduit(i, pid) {
  const p = produits.find(x => x.id === +pid);
  if (p) {
    lignes[i].designation = p.nom;
    lignes[i].prix        = p.prix;
    lignes[i].taux        = p.taux;
    lignes[i].produit_id  = p.id;
  }
  renderLignes();
  calcTotaux();
}

function renderLignes() {
  document.getElementById('lignesContainer').innerHTML = lignes.map((l, i) => `
    <div class="ligne-row">
      <div>
        <select style="width:100%;padding:9px 10px;border:1.5px solid var(--border);border-radius:9px;font-family:'Poppins',sans-serif;font-size:12.5px;color:var(--text);background:#F8FAFF;outline:none;margin-bottom:4px" onchange="selectProduit(${i},this.value)">
          <option value="">— Catalogue ou saisie libre —</option>
          ${produits.map(p=>`<option value="${p.id}" ${l.produit_id===p.id?'selected':''}>${p.reference?p.reference+' · ':''}${p.nom}</option>`).join('')}
        </select>
        <input type="text" name="lignes[${i}][designation]" value="${l.designation||''}" placeholder="Désignation..." oninput="lignes[${i}].designation=this.value" style="width:100%;padding:7px 10px;border:1.5px solid var(--border);border-radius:9px;font-family:'Poppins',sans-serif;font-size:12px;color:var(--text);background:#F8FAFF;outline:none">
        <input type="hidden" name="lignes[${i}][produit_id]" value="${l.produit_id||''}">
        <input type="hidden" name="lignes[${i}][groupe_taxes_id]" value="">
      </div>
      <input type="number" name="lignes[${i}][quantite]" value="${l.qte}" min="0.001" step="0.001" oninput="lignes[${i}].qte=+this.value;calcTotaux()" style="padding:9px 8px;border:1.5px solid var(--border);border-radius:9px;font-family:'Poppins',sans-serif;font-size:12.5px;outline:none;background:#F8FAFF;width:100%">
      <input type="number" name="lignes[${i}][prix_unitaire_ht]" value="${l.prix}" min="0" step="1" oninput="lignes[${i}].prix=+this.value;calcTotaux()" style="padding:9px 8px;border:1.5px solid var(--border);border-radius:9px;font-family:'Poppins',sans-serif;font-size:12.5px;outline:none;background:#F8FAFF;width:100%">
      <input type="number" name="lignes[${i}][valeur_remise]" value="${l.remise}" min="0" max="100" step="0.1" oninput="lignes[${i}].remise=+this.value;calcTotaux()" style="padding:9px 8px;border:1.5px solid var(--border);border-radius:9px;font-family:'Poppins',sans-serif;font-size:12.5px;outline:none;background:#F8FAFF;width:100%">
      <div style="padding:9px 8px;border:1.5px solid var(--border);border-radius:9px;background:#EEF3FC;font-size:12.5px;font-weight:700;color:var(--blue);text-align:right">
        ${Math.round(l.qte*l.prix*(1-l.remise/100)).toLocaleString('fr-FR')}
      </div>
      <div class="ligne-del" onclick="removeLigne(${i})">✕</div>
    </div>
  `).join('');
}

function calcTotaux() {
  let ht=0, rem=0, tax=0;
  lignes.forEach(l => {
    const base   = l.qte * l.prix;
    const remise = base * l.remise / 100;
    const net    = base - remise;
    ht += base; rem += remise;
    tax += net * (l.taux || 0);
  });
  const ttc = (ht - rem) + tax;
  document.getElementById('t-ht').textContent  = fmt(ht);
  document.getElementById('t-rem').textContent = '— ' + fmt(rem);
  document.getElementById('t-tax').textContent = '+ ' + fmt(tax);
  document.getElementById('t-ttc').textContent = fmt(ttc);
  renderLignes();
}

function updateAcpLabel() {
  const t = document.getElementById('acp-type').value;
  document.getElementById('acp-val-label').textContent = (t === 'pourcentage' ? 'Valeur (%)' : 'Montant (XOF)') + ' *';
  calcAcompte();
}

function calcAcompte() {
  const sel        = document.getElementById('sel-parente-acompte');
  const opt        = sel.options[sel.selectedIndex];
  const ttcParente = opt ? +opt.dataset.ttc : 0;
  if (!ttcParente) return;
  const type    = document.getElementById('acp-type').value;
  const val     = +document.getElementById('acp-val').value;
  const montant = type === 'pourcentage' ? ttcParente * val / 100 : val;
  document.getElementById('acp-info').textContent =
    `💰 Acompte : ${Math.round(montant).toLocaleString('fr-FR')} XOF sur ${Math.round(ttcParente).toLocaleString('fr-FR')} XOF`;
  document.getElementById('t-ttc').textContent = fmt(montant);
}
</script>
@endpush