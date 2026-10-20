<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>HaRaFact — Mot de passe oublié</title>
  {{-- Même style que login --}}
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; }
    body { font-family: 'Poppins', sans-serif; min-height: 100vh; display: flex; overflow: hidden; }

    .right { flex: 1; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; }
    .right-bg { position: absolute; inset: 0; background: url('{{ asset("images/back.jpeg") }}') center/cover no-repeat; filter: brightness(0.45) saturate(1.2); }

    .form-card { position: relative; z-index: 2; width: 100%; max-width: 420px; margin: 0 32px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.18); backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px); border-radius: 28px; padding: 48px 44px; box-shadow: 0 24px 64px rgba(0,0,0,0.40), inset 0 1px 0 rgba(255,255,255,0.15); animation: fadeCard 0.7s cubic-bezier(0.22,1,0.36,1) 0.15s both; }
    @keyframes fadeCard { from { opacity: 0; transform: scale(0.97) translateY(16px); } to { opacity: 1; transform: scale(1) translateY(0); } }

    .card-header { margin-bottom: 28px; text-align: center; }
    .card-header h2 { font-size: 26px; font-weight: 800; color: #fff; letter-spacing: -0.8px; margin-bottom: 6px; }
    .card-header p { font-size: 13px; color: rgba(255,255,255,0.50); }

    .alert { display: flex; align-items: center; gap: 9px; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.40); color: #fca5a5; border-radius: 12px; padding: 11px 15px; font-size: 13px; margin-bottom: 18px; }
    .alert-success { background: rgba(34,197,94,0.15); border-color: rgba(34,197,94,0.40); color: #86efac; }

    .field { margin-bottom: 18px; }
    .field label { display: block; font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.60); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px; }
    .input-wrap { position: relative; }
    .input-wrap input { width: 100%; padding: 14px 16px 14px 46px; background: rgba(255,255,255,0.10); border: 1.5px solid rgba(255,255,255,0.18); border-radius: 14px; font-family: 'Poppins', sans-serif; font-size: 14px; color: #fff; outline: none; transition: border-color 0.2s, background 0.2s, box-shadow 0.2s; }
    .input-wrap input::placeholder { color: rgba(255,255,255,0.25); }
    .input-wrap input:focus { border-color: rgba(125,211,252,0.65); background: rgba(255,255,255,0.15); box-shadow: 0 0 0 4px rgba(29,78,216,0.20); }
    .ico { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); font-size: 16px; pointer-events: none; opacity: 0.50; }

    .btn-login { width: 100%; padding: 15px; background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%); color: #fff; font-family: 'Poppins', sans-serif; font-size: 15px; font-weight: 700; border: none; border-radius: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 9px; box-shadow: 0 8px 30px rgba(29,78,216,0.55); transition: all 0.22s; margin-top: 8px; }
    .btn-login:hover { transform: translateY(-2px); box-shadow: 0 14px 40px rgba(29,78,216,0.65); }

    .lnk { color: #7dd3fc; font-weight: 600; text-decoration: none; font-size: 13px; }
    .lnk:hover { color: #bae6fd; }
    .secure { margin-top: 22px; text-align: center; font-size: 12px; color: rgba(255,255,255,0.30); }
  </style>
</head>
<body>

<div class="right">
  <div class="right-bg"></div>
  <div class="form-card">

    <div class="card-header">
      <h2>Mot de passe oublié</h2>
      <p>Entrez votre email pour recevoir un lien de réinitialisation</p>
    </div>

    @if(session('success'))
      <div class="alert alert-success">✅ {{ session('success') }}</div>
    @endif

    @if($errors->any())
      <div class="alert">⚠ {{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
      @csrf
      <div class="field">
        <label>Adresse email</label>
        <div class="input-wrap">
          <span class="ico">✉️</span>
          <input type="email" name="email" placeholder="vous@entreprise.com"
                 value="{{ old('email') }}" required/>
        </div>
      </div>

      <button type="submit" class="btn-login">📧 Envoyer le lien</button>
    </form>

    <p style="text-align:center; margin-top:24px;">
      <a href="{{ route('login') }}" class="lnk">← Retour à la connexion</a>
    </p>

    <p class="secure">🔒 Lien valable 60 minutes</p>
  </div>
</div>

</body>
</html>