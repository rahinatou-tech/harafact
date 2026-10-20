<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; padding: 40px; color: #333;">
  <h2>Facture {{ $facture->numero_facture }}</h2>
  <p>Bonjour {{ $facture->client->nom_affichage }},</p>
  <p>Veuillez trouver ci-joint votre facture d'un montant de <strong>{{ number_format($facture->total_ttc, 2) }} {{ $facture->devise }}</strong>.</p>
  <p>Date d'échéance : <strong>{{ $facture->date_echeance->format('d/m/Y') }}</strong></p>
  <p>Merci pour votre confiance.</p>
  <p style="color:#999; font-size:12px;">— L'équipe HaRaFact</p>
</body>
</html>