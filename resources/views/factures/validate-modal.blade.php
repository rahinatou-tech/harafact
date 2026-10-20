<!-- Modal validation -->
<div class="overlay" id="validateModal">
  <div class="modal modal-sm">
    <div class="mhead green">
      <button class="mhead-x" onclick="closeAll()">✕</button>
      <div class="mhead-title">✅ Valider la facture</div>
      <div class="mhead-sub">{{ $facture->numero_facture }} · {{ number_format($facture->total_ttc,0,',',' ') }} XOF</div>
    </div>
    <form action="{{ route('factures.valider', $facture) }}" method="POST">
      @csrf
      <div class="mbody">
        @if($facture->type_facture === 'recurrente')
        <p style="color:var(--muted);margin-bottom:14px">
          Confirmez-vous la validation de <strong>{{ $facture->numero_facture }}</strong> ?
        </p>
        <div class="alert ok">
          ℹ️ Vous pourrez ensuite l'envoyer au client via le bouton « Envoyer ».
        </div>
        @else
        <p style="color:var(--muted);margin-bottom:14px">
          Confirmez-vous la validation de <strong>{{ $facture->numero_facture }}</strong> ?
          La facture sera marquée comme <strong>payée</strong> et envoyée au client immédiatement.
        </p>
        <div class="alert ok">
          ℹ️ Un paiement sera automatiquement enregistré et le PDF sera envoyé par email{{ $facture->client->telephone ? '/WhatsApp' : '' }}.
        </div>
        @endif
      </div>
      <div class="mfoot">
        <button type="button" class="btn btn-ghost" onclick="closeAll()">Annuler</button>
        <button type="submit" class="btn btn-success">✅ Confirmer la validation</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal rejet -->
<div class="overlay" id="rejectModal">
  <div class="modal modal-sm">
    <div class="mhead danger">
      <button class="mhead-x" onclick="closeAll()">✕</button>
      <div class="mhead-title">❌ Rejeter la facture</div>
      <div class="mhead-sub">{{ $facture->numero_facture }}</div>
    </div>
    <form action="{{ route('factures.rejeter', $facture) }}" method="POST">
      @csrf
      <div class="mbody">
        <div class="fg">
          <label>Motif du rejet <span>*</span></label>
          <textarea name="motif_rejet" rows="4"
            placeholder="Ex: Montants incorrects, données manquantes..."
            required></textarea>
        </div>
      </div>
      <div class="mfoot">
        <button type="button" class="btn btn-ghost" onclick="closeAll()">Annuler</button>
        <button type="submit" class="btn btn-danger">🗑️ Rejeter</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal envoi (recurrente + solde) -->
<div class="overlay" id="sendModal">
  <div class="modal modal-sm">
    <div class="mhead blue">
      <button class="mhead-x" onclick="closeAll()">✕</button>
      <div class="mhead-title">📤 Envoyer la facture</div>
      <div class="mhead-sub">{{ $facture->numero_facture }}</div>
    </div>
    <form id="sendForm" method="POST">
      @csrf
      <div class="mbody">
        <p style="color:var(--muted);margin-bottom:16px">Choisissez le moyen d'envoi :</p>

        <div class="fg">
          <label style="text-transform:none;font-size:13px;display:flex;align-items:center;gap:10px;padding:10px;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;margin-bottom:8px">
            <input type="radio" name="type_envoi" value="email" {{ $facture->client->email ? '' : 'disabled' }}>
            <span>
              📧 <strong>Email</strong>
              @if($facture->client->email)
                <span style="color:var(--muted);font-size:11px;display:block">{{ $facture->client->email }}</span>
              @else
                <span style="color:var(--danger);font-size:11px;display:block">⚠️ Pas d'email</span>
              @endif
            </span>
          </label>

          <label style="text-transform:none;font-size:13px;display:flex;align-items:center;gap:10px;padding:10px;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;margin-bottom:8px">
            <input type="radio" name="type_envoi" value="whatsapp" {{ $facture->client->telephone ? '' : 'disabled' }}>
            <span>
              💬 <strong>WhatsApp</strong>
              @if($facture->client->telephone)
                <span style="color:var(--muted);font-size:11px;display:block">{{ $facture->client->telephone }}</span>
              @else
                <span style="color:var(--danger);font-size:11px;display:block">⚠️ Pas de téléphone</span>
              @endif
            </span>
          </label>

          <label style="text-transform:none;font-size:13px;display:flex;align-items:center;gap:10px;padding:10px;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;margin-bottom:8px">
            <input type="radio" name="type_envoi" value="both" {{ ($facture->client->email && $facture->client->telephone) ? '' : 'disabled' }}>
            <span>
              📧💬 <strong>Email + WhatsApp</strong>
              @if(!$facture->client->email || !$facture->client->telephone)
                <span style="color:var(--danger);font-size:11px;display:block">⚠️ Manque email ou téléphone</span>
              @endif
            </span>
          </label>

          <label style="text-transform:none;font-size:13px;display:flex;align-items:center;gap:10px;padding:10px;border:1.5px solid var(--border);border-radius:10px;cursor:pointer">
            <input type="radio" name="type_envoi" value="print">
            <span>
              🖨️ <strong>Imprimer</strong>
              <span style="color:var(--muted);font-size:11px;display:block">Remettre en main propre</span>
            </span>
          </label>
        </div>
      </div>
      <div class="mfoot">
        <button type="button" class="btn btn-ghost" onclick="closeAll()">Annuler</button>
        <button type="submit" class="btn btn-primary">📤 Envoyer</button>
      </div>
    </form>
  </div>
</div>

<script>
function openValidateModal() { openModal('validateModal'); }
function openRejectModal()   { openModal('rejectModal'); }
function openSendModal()     { openModal('sendModal'); }

document.getElementById('sendForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const typeEnvoi = document.querySelector('input[name="type_envoi"]:checked')?.value;
    if (!typeEnvoi) { showToast('Veuillez sélectionner un moyen d\'envoi', 'err'); return; }

    if (typeEnvoi === 'print') {
        window.open('{{ route("factures.pdf", $facture) }}', '_blank');
        closeAll();
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("factures.envoyer", $facture) }}';

    const csrf = document.createElement('input');
    csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = '{{ csrf_token() }}';
    form.appendChild(csrf);

    const input = document.createElement('input');
    input.type = 'hidden'; input.name = 'type_envoi'; input.value = typeEnvoi;
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
});
</script>