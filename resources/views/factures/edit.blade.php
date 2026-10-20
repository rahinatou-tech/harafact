@extends('layouts.app')
@section('title','Modifier la facture')
@section('page-title','Modifier · '.$facture->numero_facture)
@section('topbar-actions')
<a href="{{ route('factures.show',$facture) }}" class="tb-btn ghost">← Retour</a>
@endsection
@section('content')
<div class="card" style="max-width:600px;margin:0 auto">
  <div class="card-header"><div class="card-title">Modification limitée</div></div>
  <form method="POST" action="{{ route('factures.update',$facture) }}">@csrf @method('PUT')
    <div class="card-body">
      <div class="alert" style="background:#FEF3C7;border:1px solid #FCD34D;color:#92400E;margin-bottom:16px">⚠️ Seuls le statut, la date d'échéance et les notes peuvent être modifiés sur une facture émise.</div>
      <div class="form-grid">
        <div class="fg"><label>Statut <span>*</span></label>
          <select name="statut">
            <option value="brouillon"         {{ $facture->statut==='brouillon'?'selected':'' }}>◌ Brouillon</option>
            <option value="envoye"            {{ $facture->statut==='envoye'?'selected':'' }}>↗ Envoyé</option>
            <option value="partiellement_paye"{{ $facture->statut==='partiellement_paye'?'selected':'' }}>½ Partiellement payé</option>
            <option value="paye"              {{ $facture->statut==='paye'?'selected':'' }}>✓ Payé</option>
            <option value="en_retard"         {{ $facture->statut==='en_retard'?'selected':'' }}>⚠ En retard</option>
            <option value="annule"            {{ $facture->statut==='annule'?'selected':'' }}>— Annulé</option>
          </select>
        </div>
        <div class="fg"><label>Date d'échéance</label><input type="date" name="date_echeance" value="{{ $facture->date_echeance?->format('Y-m-d') }}"></div>
        <div class="fg form-full"><label>Notes visibles</label><textarea name="notes" rows="2">{{ $facture->notes }}</textarea></div>
        <div class="fg form-full"><label>Notes internes</label><textarea name="notes_internes" rows="2">{{ $facture->notes_internes }}</textarea></div>
      </div>
    </div>
    <div class="mfoot" style="padding:16px 20px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px;background:#FAFBFD">
      <a href="{{ route('factures.show',$facture) }}" class="btn btn-ghost">Annuler</a>
      <button type="submit" class="btn btn-warning">💾 Enregistrer</button>
    </div>
  </form>
</div>
@endsection
