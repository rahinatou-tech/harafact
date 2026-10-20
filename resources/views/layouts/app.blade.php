<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'HaRaFact')</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--blue:#1A3C6E;--blue-mid:#2E5FA3;--blue-lit:#4A90D9;--teal:#00C6A2;--warn:#F59E0B;--danger:#EF4444;--green:#00875A;--bg:#F4F7FF;--border:#E2ECF8;--text:#0D1B2A;--muted:#5B7299;--sw:234px;--card:#fff}
html,body{height:100%;font-family:'Poppins',sans-serif;background:var(--bg);color:var(--text);-webkit-font-smoothing:antialiased}
.sidebar{position:fixed;top:0;left:0;bottom:0;width:var(--sw);z-index:400;display:flex;flex-direction:column;background:linear-gradient(175deg,#4A90D9 0%,#2E5FA3 45%,#1A3C6E 100%)}
.s-logo{padding:20px 18px 14px;border-bottom:1px solid rgba(255,255,255,0.07);display:flex;align-items:center;gap:11px}
.s-logo-mark{width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:900;color:#fff;flex-shrink:0}
.s-logo-text{font-size:17px;font-weight:800;color:#fff}
.s-logo-sub{font-size:10px;color:rgba(255,255,255,0.45);margin-top:1px}
.s-nav{flex:1;padding:12px 10px;overflow-y:auto}
.s-nav::-webkit-scrollbar{width:0}
.s-sep{font-size:9.5px;font-weight:700;color:rgba(255,255,255,0.25);text-transform:uppercase;letter-spacing:1.2px;padding:12px 10px 5px}
.s-link{display:flex;align-items:center;gap:9px;padding:9px 11px;border-radius:11px;font-size:12.5px;font-weight:500;color:rgba(255,255,255,0.5);text-decoration:none;cursor:pointer;transition:all 0.18s;margin-bottom:2px;position:relative}
.s-link:hover{background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.85)}
.s-link.on{background:rgba(74,144,217,0.2);color:#fff;font-weight:700;border:1px solid rgba(74,144,217,0.25)}
.s-link.on::before{content:'';position:absolute;left:0;top:22%;height:56%;width:3px;background:linear-gradient(180deg,#4A90D9,#00C6A2);border-radius:0 3px 3px 0}
.s-ico{font-size:15px;width:20px;text-align:center;flex-shrink:0}
.s-foot{padding:10px 10px 14px;border-top:1px solid rgba(255,255,255,0.07)}
.s-user{display:flex;align-items:center;gap:9px;padding:9px 11px;border-radius:11px;background:rgba(255,255,255,0.05)}
.s-av{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#2E5FA3,#00C6A2);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff;flex-shrink:0}
.s-uname{font-size:12px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.s-urole{font-size:10px;color:rgba(255,255,255,0.4);margin-top:1px}
.s-logout{margin-left:auto;background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.35);font-size:15px;padding:3px;transition:color 0.15s}
.s-logout:hover{color:#EF4444}
.main{margin-left:var(--sw);min-height:100vh}
.topbar{position:fixed;top:0;left:var(--sw);right:0;z-index:300;height:58px;background:rgba(244,247,255,0.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 26px;gap:12px}
.tb-title{flex:1}
.tb-title h1{font-size:15px;font-weight:800;color:var(--blue)}
.tb-title p{font-size:11px;color:var(--muted);margin-top:1px}
.tb-btn{display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,#1A3C6E,#2E5FA3);color:#fff;border:none;border-radius:10px;padding:8px 16px;font-size:13px;font-weight:700;cursor:pointer;font-family:'Poppins',sans-serif;box-shadow:0 4px 14px rgba(26,60,110,0.28);transition:all 0.18s;text-decoration:none}
.tb-btn:hover{transform:translateY(-1px)}
.tb-btn.ghost{background:transparent;border:1.5px solid var(--border);color:var(--muted);box-shadow:none}
.tb-btn.ghost:hover{border-color:var(--blue-mid);color:var(--blue)}
.content{padding:76px 26px 30px}
.card{background:var(--card);border:1px solid var(--border);border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,0.04),0 4px 12px rgba(0,0,0,0.06)}
.card-header{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.card-title{font-size:13.5px;font-weight:800;color:var(--text)}
.card-body{padding:20px}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:18px 20px;box-shadow:0 1px 3px rgba(0,0,0,0.04),0 4px 12px rgba(0,0,0,0.06);transition:transform 0.2s}
.stat-card:hover{transform:translateY(-2px)}
.stat-label{font-size:11.5px;font-weight:600;color:var(--muted);margin-bottom:10px}
.stat-value{font-size:24px;font-weight:900;letter-spacing:-0.8px;line-height:1}
.stat-foot{margin-top:9px;font-size:11.5px;color:var(--muted)}
.up{color:#00875A;font-weight:700}
.down{color:#EF4444;font-weight:700}
table{width:100%;border-collapse:collapse}
thead th{padding:10px 16px;text-align:left;font-size:10.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.6px;background:#F4F7FF;border-bottom:1px solid var(--border)}
tbody tr{border-bottom:1px solid #EEF3FC;transition:background 0.12s}
tbody tr:last-child{border-bottom:none}
tbody tr:hover{background:#F8FBFF}
tbody td{padding:12px 16px;font-size:13px;vertical-align:middle}
.tag{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700}
.t-paye      {background:rgba(0,135,90,0.10);color:#00875A;border:1px solid rgba(0,135,90,0.18)}
.t-partiel   {background:rgba(74,144,217,0.10);color:#1A3C6E;border:1px solid rgba(74,144,217,0.18)}
.t-envoye    {background:rgba(124,58,237,0.10);color:#5B21B6;border:1px solid rgba(124,58,237,0.18)}
.t-brouillon {background:rgba(91,114,153,0.10);color:var(--muted);border:1px solid var(--border)}
.t-retard    {background:rgba(239,68,68,0.10);color:#DC2626;border:1px solid rgba(239,68,68,0.18)}
.t-annule    {background:rgba(55,65,81,0.08);color:#6B7280;border:1px solid #E5E7EB}
.t-attente   {background:rgba(217,119,6,0.10);color:#B45309;border:1px solid rgba(217,119,6,0.18)}
.fg{margin-bottom:14px}
.fg label{display:block;font-size:11.5px;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px}
.fg label span{color:#EF4444}
.fg input,.fg select,.fg textarea{width:100%;padding:10px 13px;border:1.5px solid var(--border);border-radius:10px;font-family:'Poppins',sans-serif;font-size:13px;color:var(--text);background:#F8FAFF;outline:none;transition:all 0.2s}
.fg input:focus,.fg select:focus,.fg textarea:focus{border-color:var(--blue-mid);background:#fff;box-shadow:0 0 0 3px rgba(46,95,163,0.1)}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-grid.cols3{grid-template-columns:1fr 1fr 1fr}
.form-full{grid-column:1/-1}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;border:none;font-family:'Poppins',sans-serif;transition:all 0.18s;text-decoration:none}
.btn:hover{transform:translateY(-1px)}
.btn-primary{background:linear-gradient(135deg,#1A3C6E,#2E5FA3);color:#fff;box-shadow:0 4px 14px rgba(26,60,110,0.28)}
.btn-success{background:linear-gradient(135deg,#065F46,#059669);color:#fff}
.btn-warning{background:linear-gradient(135deg,#B45309,#F59E0B);color:#fff}
.btn-danger{background:linear-gradient(135deg,#991B1B,#EF4444);color:#fff}
.btn-ghost{background:#F4F7FF;color:var(--muted);border:1.5px solid var(--border)}
.btn-ghost:hover{border-color:var(--blue-mid);color:var(--blue);background:#fff}
.btn-sm{padding:7px 14px;font-size:12px;border-radius:8px}
.btn-icon{width:34px;height:34px;padding:0;border-radius:9px;background:#F4F7FF;border:1.5px solid var(--border);color:var(--muted);font-size:14px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:all 0.15s;font-family:'Poppins',sans-serif}
.btn-icon:hover{background:#fff;border-color:var(--blue-mid);color:var(--blue)}
.btn-icon.del:hover{border-color:#EF4444;color:#EF4444;background:#FEF2F2}
.overlay{position:fixed;inset:0;z-index:500;background:rgba(8,18,40,0.6);backdrop-filter:blur(5px);display:none;align-items:center;justify-content:center;padding:20px}
.overlay.open{display:flex}
.modal{background:#fff;border-radius:20px;width:100%;box-shadow:0 30px 70px rgba(8,18,40,0.25);overflow:hidden;animation:popIn 0.25s cubic-bezier(0.22,1,0.36,1)}
.modal-sm{max-width:440px}
.modal-md{max-width:580px}
.modal-lg{max-width:820px;max-height:92vh;overflow-y:auto}
@keyframes popIn{from{opacity:0;transform:scale(0.94) translateY(18px)}to{opacity:1;transform:scale(1) translateY(0)}}
.mhead{padding:22px 26px 18px;position:relative;overflow:hidden}
.mhead::before{content:'';position:absolute;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,0.10);top:-70px;right:-50px}
.mhead-title{font-size:17px;font-weight:800;color:#fff;position:relative;z-index:1}
.mhead-sub{font-size:12px;color:rgba(255,255,255,0.55);margin-top:4px;position:relative;z-index:1}
.mhead-x{position:absolute;top:16px;right:16px;width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,0.15);border:none;cursor:pointer;font-size:15px;color:rgba(255,255,255,0.7);display:flex;align-items:center;justify-content:center;transition:all 0.15s;z-index:2}
.mhead-x:hover{background:rgba(239,68,68,0.4);color:#fff}
.mhead.blue{background:linear-gradient(135deg,#4A90D9,#2E5FA3,#1A3C6E)}
.mhead.green{background:linear-gradient(135deg,#059669,#065F46)}
.mhead.warn{background:linear-gradient(135deg,#F59E0B,#B45309)}
.mhead.danger{background:linear-gradient(135deg,#EF4444,#991B1B)}
.mbody{padding:22px 26px}
.mfoot{padding:16px 26px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px;background:#FAFBFD}
.toast{position:fixed;bottom:24px;right:24px;z-index:999;padding:13px 20px;border-radius:13px;font-size:13px;font-weight:600;display:none;align-items:center;gap:10px;box-shadow:0 10px 30px rgba(0,0,0,0.2)}
.toast.show{display:flex;animation:slideUp 0.3s ease}
@keyframes slideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.toast.ok{background:linear-gradient(135deg,#065F46,#059669);color:#fff}
.toast.err{background:linear-gradient(135deg,#991B1B,#EF4444);color:#fff}
.alert{padding:11px 16px;border-radius:10px;font-size:12.5px;margin-bottom:14px;border:1px solid}
.alert.ok{background:#D1FAE5;color:#065F46;border-color:#6EE7B7}
.alert.err{background:#FEE2E2;color:#991B1B;border-color:#FCA5A5}
.search-box{position:relative}
.search-box input{padding:9px 14px 9px 36px;border:1.5px solid var(--border);border-radius:10px;font-size:13px;background:#F8FAFF;outline:none;width:240px;transition:all 0.2s;font-family:'Poppins',sans-serif;color:var(--text)}
.search-box input:focus{border-color:var(--blue-mid);background:#fff;width:280px}
.search-ico{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#B0BEC5;font-size:13px;pointer-events:none}
.pgn{display:flex;align-items:center;justify-content:space-between;padding:13px 20px;border-top:1px solid var(--border)}
.pgn-info{font-size:12px;color:var(--muted)}
.progress{background:#EEF3FC;border-radius:6px;height:8px;overflow:hidden}
.progress-fill{height:100%;border-radius:6px;transition:width 0.5s}
.ligne-head{display:grid;grid-template-columns:2.5fr 0.7fr 1.2fr 0.7fr 1.2fr 36px;gap:8px;padding:0 4px;margin-bottom:6px}
.ligne-head span{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px}
.ligne-row{display:grid;grid-template-columns:2.5fr 0.7fr 1.2fr 0.7fr 1.2fr 36px;gap:8px;margin-bottom:8px;align-items:center}
.ligne-row input,.ligne-row select{width:100%;padding:9px 10px;border:1.5px solid var(--border);border-radius:9px;font-family:'Poppins',sans-serif;font-size:12.5px;color:var(--text);background:#F8FAFF;outline:none;transition:border 0.15s}
.ligne-row input:focus,.ligne-row select:focus{border-color:var(--blue-mid);background:#fff}
.ligne-del{width:34px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:9px;background:#FEF2F2;border:1.5px solid #FECACA;color:#EF4444;cursor:pointer;font-size:14px;flex-shrink:0}
.btn-add-ligne{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border:1.5px dashed var(--blue-mid);border-radius:9px;font-size:12px;font-weight:600;color:var(--blue-mid);background:rgba(46,95,163,0.04);cursor:pointer;font-family:'Poppins',sans-serif;transition:all 0.15s}
.btn-add-ligne:hover{background:rgba(46,95,163,0.1)}
.totaux-box{background:#F4F7FF;border:1px solid var(--border);border-radius:12px;padding:16px 18px}
.totaux-row{display:flex;justify-content:space-between;align-items:center;padding:5px 0;font-size:13px}
.totaux-row.big{border-top:2px solid var(--border);margin-top:8px;padding-top:10px}
.totaux-lbl{color:var(--muted);font-weight:500}
.totaux-val{font-weight:700;color:var(--text)}
.totaux-val.main{font-size:20px;font-weight:900;color:var(--blue)}
@media(max-width:1100px){.stats-grid{grid-template-columns:repeat(2,1fr)}}
</style>
@stack('styles')
</head>
<body>

<aside class="sidebar">
  <div class="s-logo">
    <div class="s-logo-mark">H</div>
    <div>
      <div class="s-logo-text">HaRaFact</div>
      <div class="s-logo-sub">{{ auth()->user()->entreprise->nom ?? '' }}</div>
    </div>
  </div>
  <nav class="s-nav">
    <!-- ===== TOUS LES RÔLES ===== -->
    <div class="s-sep">Principal</div>
    <a href="{{ route('dashboard') }}" class="s-link {{ request()->routeIs('dashboard') ? 'on' : '' }}"><span class="s-ico">⊞</span> Tableau de bord</a>

    <!-- ===== DG — LECTURE SEULE ===== -->
    @if(auth()->user()->isDG())
      <a href="{{ route('factures.index') }}" class="s-link {{ request()->routeIs('factures.*') ? 'on' : '' }}"><span class="s-ico">◫</span> Factures</a>
      <a href="{{ route('paiements.index') }}" class="s-link {{ request()->routeIs('paiements.*') ? 'on' : '' }}"><span class="s-ico">◈</span> Paiements</a>
      <a href="{{ route('clients.index') }}" class="s-link {{ request()->routeIs('clients.*') ? 'on' : '' }}"><span class="s-ico">◉</span> Clients</a>
      <a href="{{ route('produits.index') }}" class="s-link {{ request()->routeIs('catalogue.*') ? 'on' : '' }}"><span class="s-ico">◉</span>Catalogue</a>
      <a href="{{ route('devis.index') }}"class="s-link {{ request()->routeIs('devis.*') ? 'on' : '' }}"><span class="s-ico">◉</span>Devis</a>
<a href="{{ route('utilisateurs.index') }}"class="s-link {{ request()->routeIs('utilisateurs.*') ? 'on' : '' }}"><span class="s-ico">◉</span>Utilisateurs</a>
<a href="{{ route('config.index') }}"class="s-link {{ request()->routeIs('config.*') ? 'on' : '' }}"><span class="s-ico">◉</span>Configuration</a>
    @endif

    <!-- ===== EMPLOYE — CRÉER & ENVOYER ===== -->
    @if(auth()->user()->isEmploye())
      <a href="{{ route('factures.index') }}" class="s-link {{ request()->routeIs('factures.*') ? 'on' : '' }}"><span class="s-ico">◫</span> Factures</a>
      <a href="{{ route('clients.index') }}" class="s-link {{ request()->routeIs('clients.*') ? 'on' : '' }}"><span class="s-ico">◉</span> Clients</a>
      <a href="{{ route('produits.index') }}" class="s-link {{ request()->routeIs('produits.*') ? 'on' : '' }}"><span class="s-ico">◻</span> Catalogue</a>
      <a href="{{ route('devis.index') }}" class="s-link {{ request()->routeIs('devis.*') ? 'on' : '' }}"><span class="s-ico">📋</span> Devis</a>
    @endif

    <!-- ===== COMPTABLE — VALIDER & PAIEMENTS ===== -->
    @if(auth()->user()->isComptable())
      <a href="{{ route('factures.index') }}" class="s-link {{ request()->routeIs('factures.*') ? 'on' : '' }}"><span class="s-ico">◫</span> Factures à valider</a>
      <a href="{{ route('paiements.index') }}" class="s-link {{ request()->routeIs('paiements.*') ? 'on' : '' }}"><span class="s-ico">◈</span> Paiements</a>
    @endif

    <!-- ===== ADMIN — CONFIGURATION ===== -->
    @if(auth()->user()->peutConfigurer())
      <div class="s-sep">Administration</div>
      <a href="{{ route('utilisateurs.index') }}" class="s-link {{ request()->routeIs('utilisateurs.*') ? 'on' : '' }}"><span class="s-ico">◷</span> Utilisateurs</a>
      <a href="{{ route('config.index') }}" class="s-link {{ request()->routeIs('config.*') ? 'on' : '' }}"><span class="s-ico">⊕</span> Configuration</a>
    @endif
  </nav>
  <div class="s-foot">
    <div class="s-user">
      <div class="s-av">{{ auth()->user()->initiales }}</div>
      <div style="flex:1;min-width:0">
        <div class="s-uname">{{ auth()->user()->nom_complet }}</div>
        <div class="s-urole">{{ auth()->user()->role }}</div>
      </div>
      <form method="POST" action="{{ route('logout') }}">@csrf
        <button type="submit" class="s-logout" title="Déconnexion">⏻</button>
      </form>
    </div>
  </div>
</aside>

<div class="topbar">
  <div class="tb-title">
    <h1>@yield('page-title','Tableau de bord')</h1>
    <p>@yield('page-sub')</p>
  </div>
  @yield('topbar-actions')
</div>

<main class="main">
  <div class="content">
    @if(session('success'))<div class="alert ok">✅ {{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert err">❌ {{ session('error') }}</div>@endif
    @if($errors->any())
    <div class="alert err">
      <div><strong>Erreurs :</strong><ul style="margin:4px 0 0 16px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    </div>
    @endif
    @yield('content')
  </div>
</main>

<div class="toast" id="toast"><span id="toastMsg"></span></div>

<script>
function showToast(msg,type='ok'){
  const t=document.getElementById('toast');
  t.className=`toast show ${type}`;
  document.getElementById('toastMsg').textContent=msg;
  setTimeout(()=>t.classList.remove('show'),3500);
}
function closeAll(){document.querySelectorAll('.overlay').forEach(o=>o.classList.remove('open'));}
function openModal(id){document.getElementById(id).classList.add('open');}
document.querySelectorAll('.overlay').forEach(o=>o.addEventListener('click',e=>{if(e.target===o)closeAll();}));
function fmt(n){return Math.round(n).toLocaleString('fr-FR')+' XOF';}
</script>
@stack('scripts')
</body>
</html>