<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>HaRaFact — Connexion</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; }
    body { font-family: 'Poppins', sans-serif; min-height: 100vh; display: flex; overflow: hidden; }

    .left { width: 55%; position: relative; overflow: hidden; flex-shrink: 0; }
    .left-img { position: absolute; inset: 0; background: url('{{ asset("images/right.jpeg") }}') center/cover no-repeat; }
    .left-overlay { position: absolute; inset: 0; background: linear-gradient(160deg, rgba(0,5,30,0.45) 0%, rgba(0,10,50,0.30) 50%, rgba(0,5,20,0.50) 100%); }
    .left-content { position: relative; z-index: 2; height: 100%; display: flex; flex-direction: column; justify-content: space-between; padding: 52px 56px; }

    .logo { display: flex; align-items: center; gap: 13px; animation: slideDown 0.7s cubic-bezier(0.22,1,0.36,1) both; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    .logo-icon { width: 48px; height: 48px; background: linear-gradient(135deg, #1d4ed8, #3b82f6); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800; color: #fff; box-shadow: 0 8px 28px rgba(29,78,216,0.6); }
    .logo-name { font-size: 22px; font-weight: 800; color: #fff; letter-spacing: -0.5px; text-shadow: 0 2px 8px rgba(0,0,0,0.4); }
    .logo-name span { color: #7dd3fc; }

    .hero { animation: slideUp 0.8s cubic-bezier(0.22,1,0.36,1) 0.1s both; }
    @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    .hero-tag { display: inline-flex; align-items: center; gap: 7px; background: rgba(125,211,252,0.15); border: 1px solid rgba(125,211,252,0.4); border-radius: 30px; padding: 6px 16px; font-size: 12px; color: #e0f2fe; font-weight: 500; margin-bottom: 22px; backdrop-filter: blur(8px); }
    .hero-tag::before { content: '●'; font-size: 8px; color: #7dd3fc; }
    .hero h1 { font-size: clamp(30px, 3.2vw, 46px); font-weight: 800; color: #fff; line-height: 1.18; letter-spacing: -1.5px; margin-bottom: 20px; text-shadow: 0 2px 20px rgba(0,0,0,0.5); }
    .hero h1 em { font-style: normal; background: linear-gradient(90deg, #7dd3fc, #a5b4fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .hero p { font-size: 15px; color: rgba(255,255,255,0.80); line-height: 1.75; max-width: 380px; font-weight: 300; text-shadow: 0 1px 6px rgba(0,0,0,0.4); }

    .features { display: flex; flex-direction: column; gap: 12px; animation: slideUp 0.8s cubic-bezier(0.22,1,0.36,1) 0.2s both; }
    .feature-item { display: flex; align-items: center; gap: 14px; background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.18); backdrop-filter: blur(16px); border-radius: 14px; padding: 14px 18px; transition: background 0.2s; }
    .feature-item:hover { background: rgba(255,255,255,0.16); }
    .feature-icon { font-size: 22px; flex-shrink: 0; }
    .feature-text strong { display: block; font-size: 13px; font-weight: 600; color: #fff; margin-bottom: 2px; }
    .feature-text span { font-size: 11px; color: rgba(255,255,255,0.60); font-weight: 300; }

    .right { flex: 1; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; }
    .right-bg { position: absolute; inset: 0; background: url('{{ asset("images/back.jpeg") }}') center/cover no-repeat; filter: brightness(0.45) saturate(1.2); }

    .form-card { position: relative; z-index: 2; width: 100%; max-width: 420px; margin: 0 32px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.18); backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px); border-radius: 28px; padding: 48px 44px; box-shadow: 0 24px 64px rgba(0,0,0,0.40), inset 0 1px 0 rgba(255,255,255,0.15); animation: fadeCard 0.7s cubic-bezier(0.22,1,0.36,1) 0.15s both; }
    @keyframes fadeCard { from { opacity: 0; transform: scale(0.97) translateY(16px); } to { opacity: 1; transform: scale(1) translateY(0); } }

    .card-header { margin-bottom: 28px; text-align: center; }
    .card-header h2 { font-size: 26px; font-weight: 800; color: #fff; letter-spacing: -0.8px; margin-bottom: 6px; }
    .card-header p { font-size: 13px; color: rgba(255,255,255,0.50); }

    .role-badge { display: flex; align-items: center; justify-content: center; gap: 7px; background: rgba(125,211,252,0.08); border: 1px solid rgba(125,211,252,0.20); border-radius: 20px; padding: 9px 16px; font-size: 12px; color: rgba(125,211,252,0.75); font-weight: 500; margin-bottom: 26px; text-align: center; }

    .alert { display: flex; align-items: center; gap: 9px; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.40); color: #fca5a5; border-radius: 12px; padding: 11px 15px; font-size: 13px; margin-bottom: 18px; }
    .alert-success { background: rgba(34,197,94,0.15); border-color: rgba(34,197,94,0.40); color: #86efac; }
    .alert-info { background: rgba(125,211,252,0.10); border-color: rgba(125,211,252,0.25); color: #7dd3fc; }

    .field { margin-bottom: 18px; }
    .field label { display: block; font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.60); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px; }
    .field .error-msg { font-size: 11px; color: #fca5a5; margin-top: 5px; }
    .input-wrap { position: relative; }
    .input-wrap input { width: 100%; padding: 14px 16px 14px 46px; background: rgba(255,255,255,0.10); border: 1.5px solid rgba(255,255,255,0.18); border-radius: 14px; font-family: 'Poppins', sans-serif; font-size: 14px; color: #fff; outline: none; transition: border-color 0.2s, background 0.2s, box-shadow 0.2s; }
    .input-wrap input::placeholder { color: rgba(255,255,255,0.25); }
    .input-wrap input:focus { border-color: rgba(125,211,252,0.65); background: rgba(255,255,255,0.15); box-shadow: 0 0 0 4px rgba(29,78,216,0.20); }
    .input-wrap input.is-invalid { border-color: rgba(239,68,68,0.70); }
    .ico { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); font-size: 16px; pointer-events: none; opacity: 0.50; }
    .eye { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); font-size: 15px; border: none; background: none; cursor: pointer; opacity: 0.40; color: white; transition: opacity 0.2s; }
    .eye:hover { opacity: 0.9; }

    .opts { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; font-size: 13px; }
    .chk { display: flex; align-items: center; gap: 8px; color: rgba(255,255,255,0.50); cursor: pointer; }
    .chk input { accent-color: #3b82f6; width: 15px; height: 15px; padding:0; border:none; }
    .lnk { color: #7dd3fc; font-weight: 600; text-decoration: none; font-size: 13px; }
    .lnk:hover { color: #bae6fd; }

    .btn-login { width: 100%; padding: 15px; background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%); color: #fff; font-family: 'Poppins', sans-serif; font-size: 15px; font-weight: 700; border: none; border-radius: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 9px; box-shadow: 0 8px 30px rgba(29,78,216,0.55); transition: all 0.22s; }
    .btn-login:hover { transform: translateY(-2px); box-shadow: 0 14px 40px rgba(29,78,216,0.65); }
    .btn-login:active { transform: scale(0.98); }
    .spinner { width: 18px; height: 18px; border: 2.5px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; display: none; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .secure { margin-top: 22px; text-align: center; font-size: 12px; color: rgba(255,255,255,0.30); }

    @media (max-width: 860px) {
      .left { display: none; }
      .right { min-height: 100vh; }
      .form-card { margin: 24px; padding: 36px 28px; }
    }
  </style>
</head>
<body>

<div class="left">
  <div class="left-img"></div>
  <div class="left-overlay"></div>
  <div class="left-content">
    <div class="logo">
      <div class="logo-icon">HF</div>
      <div class="logo-name">Hara<span>Fact</span></div>
    </div>
    <div class="hero">
      <div class="hero-tag">Gestion intelligente des factures</div>
      <h1>Vos factures,<br/>enfin <em>sous contrôle.</em></h1>
      <p>La solution numérique pensée pour les PME. Créez, envoyez et suivez vos factures en quelques clics — simplement, rapidement, efficacement.</p>
    </div>
    <div class="features">
      <div class="feature-item">
        <div class="feature-icon">📄</div>
        <div class="feature-text"><strong>Génération PDF automatique</strong><span>Factures professionnelles en un clic</span></div>
      </div>
      <div class="feature-item">
        <div class="feature-icon">📲</div>
        <div class="feature-text"><strong>Envoi WhatsApp &amp; Email</strong><span>Partagez vos documents instantanément</span></div>
      </div>
      <div class="feature-item">
        <div class="feature-icon">📊</div>
        <div class="feature-text"><strong>Tableau de bord complet</strong><span>Suivez votre trésorerie en temps réel</span></div>
      </div>
    </div>
  </div>
</div>

<div class="right">
  <div class="right-bg"></div>
  <div class="form-card">

    <div class="card-header">
      <h2>Connexion</h2>
      <p>Accédez à votre espace de gestion</p>
    </div>

    <div class="role-badge">
      🔐 Le système détecte automatiquement votre accès
    </div>

    @if(session('success'))
      <div class="alert alert-success">✅ {{ session('success') }}</div>
    @endif

    @if(session('info'))
      <div class="alert alert-info">ℹ️ {{ session('info') }}</div>
    @endif

    @if($errors->any())
      <div class="alert">⚠ {{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.post') }}" id="loginForm">
      @csrf

      <div class="field">
        <label>Adresse email</label>
        <div class="input-wrap">
          <span class="ico">✉️</span>
          <input type="email" name="email" placeholder="vous@entreprise.com"
                 value="{{ old('email') }}" required autocomplete="email"
                 class="{{ $errors->has('email') ? 'is-invalid' : '' }}"/>
        </div>
        @error('email')<p class="error-msg">{{ $message }}</p>@enderror
      </div>

      <div class="field">
        <label>Mot de passe</label>
        <div class="input-wrap">
          <span class="ico">🔒</span>
          <input type="password" name="mot_de_passe" id="pwd" placeholder="••••••••"
                 required autocomplete="current-password"
                 class="{{ $errors->has('mot_de_passe') ? 'is-invalid' : '' }}"/>
          <button type="button" class="eye" onclick="togglePwd(this)">👁</button>
        </div>
        @error('mot_de_passe')<p class="error-msg">{{ $message }}</p>@enderror
      </div>

      <div class="opts">
        <label class="chk">
          <input type="checkbox" name="remember" value="1"/> Se souvenir de moi
        </label>
       <a href="{{ route('password.request') }}" class="lnk">Mot de passe oublié ?</a>
      </div>

      <button type="submit" class="btn-login" id="btn">
        <span id="btnTxt">Se connecter</span>
        <div class="spinner" id="sp"></div>
      </button>
    </form>

    <p class="secure">🔒 Connexion sécurisée &nbsp;·&nbsp; Accès selon votre rôle</p>
  </div>
</div>

<script>
function togglePwd(btn) {
  const inp = document.getElementById('pwd');
  inp.type = inp.type === 'password' ? 'text' : 'password';
  btn.textContent = inp.type === 'password' ? '👁' : '🙈';
}
document.getElementById('loginForm').addEventListener('submit', function() {
  document.getElementById('btnTxt').style.display = 'none';
  document.getElementById('sp').style.display = 'block';
  document.getElementById('btn').style.opacity = '0.7';
  document.getElementById('btn').style.pointerEvents = 'none';
});
</script>
</body>
</html>