<!DOCTYPE html>
<html>
<body style="font-family:sans-serif;padding:40px;color:#333;background:#f1f5f9">
  <div style="max-width:600px;margin:0 auto;background:#fff;border-radius:16px;padding:40px;box-shadow:0 2px 12px rgba(0,0,0,0.08)">
    
    <h2 style="color:#1A3C6E;margin-bottom:8px">Votre facture {{ $facture->numero_facture }}</h2>
    <p style="color:#64748B;margin-bottom:24px">Émise le {{ $facture->date_emission?->format('d/m/Y') }}</p>

    <p>Bonjour <strong>{{ $facture->client->nom_affichage }}</strong>,</p>
    <p>Veuillez trouver ci-dessous le lien pour consulter votre facture d'un montant de <strong>{{ number_format($facture->total_ttc, 0, ',', ' ') }} {{ $facture->devise }}</strong>.</p>

    <div style="text-align:center;margin:32px 0">
      <a href="{{ $lienPublic }}" 
         style="background:#1A3C6E;color:#fff;padding:14px 32px;border-radius:10px;text-decoration:none;font-weight:700;font-size:15px">
        📄 Voir ma facture
      </a>
    </div>

    <p style="font-size:12px;color:#94A3B8">Ou copiez ce lien : {{ $lienPublic }}</p>
    <p style="font-size:12px;color:#94A3B8;margin-top:24px">— L'équipe HaRaFact</p>
  </div>
</body>
</html>