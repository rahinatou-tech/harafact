<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; padding: 40px; color: #333;">
  <h2>Réinitialisation de mot de passe</h2>
  <p>Bonjour {{ $utilisateur->prenom }},</p>
  <p>Cliquez sur le bouton ci-dessous pour réinitialiser votre mot de passe. Ce lien expire dans <strong>60 minutes</strong>.</p>
  <a href="{{ $url }}" style="display:inline-block; padding:12px 28px; background:#1d4ed8; color:#fff; border-radius:8px; text-decoration:none; font-weight:bold; margin:20px 0;">
    Réinitialiser mon mot de passe
  </a>
  <p>Si vous n'avez pas fait cette demande, ignorez cet email.</p>
  <p style="color:#999; font-size:12px;">— L'équipe HaRaFact</p>
</body>
</html>