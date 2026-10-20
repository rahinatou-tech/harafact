@extends('layouts.app')

@section('title', 'Configuration — HaRaFact')

@section('content')
<div class="topbar">
    <div class="tb-title">
        <h1>⚙️ Configuration</h1>
        <p>Paramètres de votre entreprise et de la plateforme</p>
    </div>
</div>

<div class="content">

    {{-- ── ALERTES ───────────────────────────────────────────── --}}
    @if(session('success'))
    <div style="background:rgba(0,135,90,0.08);border:1px solid rgba(0,135,90,0.25);border-radius:12px;padding:12px 18px;margin-bottom:20px;display:flex;align-items:center;gap:10px;color:#00875A;font-size:13px;font-weight:600;">
        ✅ {{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);border-radius:12px;padding:12px 18px;margin-bottom:20px;color:#DC2626;font-size:13px;">
        <strong>Erreurs :</strong>
        <ul style="margin:6px 0 0 16px;">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('config.update') }}" enctype="multipart/form-data">
        @csrf
        @method('POST')

        {{-- ══════════════════════════════════════════════════════
             1. IDENTITÉ DE L'ENTREPRISE
        ══════════════════════════════════════════════════════ --}}
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <span class="card-title">🏢 Identité de l'entreprise</span>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="fg form-full">
                        <label>Nom de l'entreprise <span>*</span></label>
                        <input type="text" name="nom" value="{{ old('nom', $entreprise->nom) }}" required>
                    </div>

                    {{-- Logo --}}
                    <div class="fg form-full">
                        <label>Logo de l'entreprise</label>
                        <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                            {{-- Aperçu logo actuel --}}
                            <div id="logo-preview-wrap" style="width:90px;height:90px;border-radius:12px;border:2px dashed var(--border);display:flex;align-items:center;justify-content:center;overflow:hidden;background:#F8FAFF;flex-shrink:0;">
                                @if($entreprise->logo_url)
                                    <img id="logo-preview" src="{{ $entreprise->logo_url }}" alt="Logo" style="width:100%;height:100%;object-fit:contain;">
                                @else
                                    <span id="logo-placeholder" style="font-size:28px;">🏢</span>
                                    <img id="logo-preview" src="" alt="Logo" style="width:100%;height:100%;object-fit:contain;display:none;">
                                @endif
                            </div>
                            {{-- Bouton upload --}}
                            <div style="flex:1;min-width:200px;">
                                <label for="logo-input" style="display:inline-flex;align-items:center;gap:8px;padding:9px 18px;background:linear-gradient(135deg,#1A3C6E,#2E5FA3);color:#fff;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;text-transform:none;letter-spacing:0;">
                                    📁 Choisir un logo
                                </label>
                                <input id="logo-input" type="file" name="logo" accept="image/*" style="display:none;" onchange="previewLogo(this)">
                                <p style="font-size:11px;color:var(--muted);margin-top:6px;">
                                    JPG, PNG, SVG ou WebP — max 2 Mo<br>
                                    Recommandé : fond transparent (PNG)
                                </p>
                                @if($entreprise->logo_url)
                                <p style="font-size:11px;color:var(--muted);margin-top:2px;">
                                    Logo actuel : <a href="{{ $entreprise->logo_url }}" target="_blank" style="color:var(--blue-mid);">voir</a>
                                </p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="fg">
                        <label>Forme juridique</label>
                        <input type="text" name="forme_juridique" value="{{ old('forme_juridique', $entreprise->forme_juridique) }}" placeholder="SARL, SAS, EI…">
                    </div>
                    <div class="fg">
                        <label>Type d'activité</label>
                        <select name="type_activite">
                            <option value="les_deux"  {{ old('type_activite', $entreprise->type_activite) === 'les_deux'  ? 'selected' : '' }}>Produits & Services</option>
                            <option value="produits"  {{ old('type_activite', $entreprise->type_activite) === 'produits'  ? 'selected' : '' }}>Produits uniquement</option>
                            <option value="services"  {{ old('type_activite', $entreprise->type_activite) === 'services'  ? 'selected' : '' }}>Services uniquement</option>
                        </select>
                    </div>
                    <div class="fg">
                        <label>Numéro fiscal / NIF</label>
                        <input type="text" name="numero_fiscal" value="{{ old('numero_fiscal', $entreprise->numero_fiscal) }}">
                    </div>
                    <div class="fg">
                        <label>Numéro TVA</label>
                        <input type="text" name="numero_tva" value="{{ old('numero_tva', $entreprise->numero_tva) }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════
             2. CONTACT & ADRESSE
        ══════════════════════════════════════════════════════ --}}
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <span class="card-title">📍 Contact & Adresse</span>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="fg">
                        <label>Email de l'entreprise</label>
                        <input type="email" name="email" value="{{ old('email', $entreprise->email) }}">
                    </div>
                    <div class="fg">
                        <label>Téléphone</label>
                        <input type="text" name="telephone" value="{{ old('telephone', $entreprise->telephone) }}">
                    </div>
                    <div class="fg">
                        <label>Site web</label>
                        <input type="text" name="site_web" value="{{ old('site_web', $entreprise->site_web) }}" placeholder="https://…">
                    </div>
                    <div class="fg">
                        <label>Pays</label>
                        <select name="pays">
                            <option value="NE" {{ old('pays', $entreprise->pays) === 'NE' ? 'selected' : '' }}>🇳🇪 Niger</option>
                            <option value="CI" {{ old('pays', $entreprise->pays) === 'CI' ? 'selected' : '' }}>🇨🇮 Côte d'Ivoire</option>
                            <option value="SN" {{ old('pays', $entreprise->pays) === 'SN' ? 'selected' : '' }}>🇸🇳 Sénégal</option>
                            <option value="ML" {{ old('pays', $entreprise->pays) === 'ML' ? 'selected' : '' }}>🇲🇱 Mali</option>
                            <option value="BF" {{ old('pays', $entreprise->pays) === 'BF' ? 'selected' : '' }}>🇧🇫 Burkina Faso</option>
                            <option value="BJ" {{ old('pays', $entreprise->pays) === 'BJ' ? 'selected' : '' }}>🇧🇯 Bénin</option>
                            <option value="TG" {{ old('pays', $entreprise->pays) === 'TG' ? 'selected' : '' }}>🇹🇬 Togo</option>
                            <option value="CM" {{ old('pays', $entreprise->pays) === 'CM' ? 'selected' : '' }}>🇨🇲 Cameroun</option>
                            <option value="FR" {{ old('pays', $entreprise->pays) === 'FR' ? 'selected' : '' }}>🇫🇷 France</option>
                            <option value="MA" {{ old('pays', $entreprise->pays) === 'MA' ? 'selected' : '' }}>🇲🇦 Maroc</option>
                        </select>
                    </div>
                    <div class="fg">
                        <label>Adresse ligne 1</label>
                        <input type="text" name="adresse_ligne1" value="{{ old('adresse_ligne1', $entreprise->adresse_ligne1) }}">
                    </div>
                    <div class="fg">
                        <label>Adresse ligne 2</label>
                        <input type="text" name="adresse_ligne2" value="{{ old('adresse_ligne2', $entreprise->adresse_ligne2) }}">
                    </div>
                    <div class="fg">
                        <label>Ville</label>
                        <input type="text" name="ville" value="{{ old('ville', $entreprise->ville) }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════
             3. FACTURATION
        ══════════════════════════════════════════════════════ --}}
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <span class="card-title">🧾 Paramètres de facturation</span>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="fg">
                        <label>Devise par défaut</label>
                        <select name="devise_defaut">
                            <option value="XOF" {{ old('devise_defaut', $entreprise->devise_defaut) === 'XOF' ? 'selected' : '' }}>XOF — Franc CFA (UEMOA)</option>
                            <option value="XAF" {{ old('devise_defaut', $entreprise->devise_defaut) === 'XAF' ? 'selected' : '' }}>XAF — Franc CFA (CEMAC)</option>
                            <option value="EUR" {{ old('devise_defaut', $entreprise->devise_defaut) === 'EUR' ? 'selected' : '' }}>EUR — Euro</option>
                            <option value="USD" {{ old('devise_defaut', $entreprise->devise_defaut) === 'USD' ? 'selected' : '' }}>USD — Dollar US</option>
                            <option value="GNF" {{ old('devise_defaut', $entreprise->devise_defaut) === 'GNF' ? 'selected' : '' }}>GNF — Franc Guinéen</option>
                        </select>
                    </div>
                    <div class="fg">
                        <label>Délai de paiement (jours)</label>
                        <input type="number" name="delai_paiement_jours" value="{{ old('delai_paiement_jours', $entreprise->delai_paiement_jours ?? 30) }}" min="0" max="365">
                    </div>
                    <div class="fg">
                        <label>Début exercice fiscal (mois)</label>
                        <select name="debut_exercice_fiscal">
                            @foreach(['1'=>'Janvier','2'=>'Février','3'=>'Mars','4'=>'Avril','5'=>'Mai','6'=>'Juin','7'=>'Juillet','8'=>'Août','9'=>'Septembre','10'=>'Octobre','11'=>'Novembre','12'=>'Décembre'] as $num => $label)
                            <option value="{{ $num }}" {{ (string)old('debut_exercice_fiscal', $entreprise->debut_exercice_fiscal ?? 1) === $num ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fg">
                        <label>Fuseau horaire</label>
                        <select name="fuseau_horaire">
                            <option value="Africa/Niamey"     {{ old('fuseau_horaire', $entreprise->fuseau_horaire) === 'Africa/Niamey'     ? 'selected' : '' }}>Africa/Niamey (UTC+1)</option>
                            <option value="Africa/Abidjan"    {{ old('fuseau_horaire', $entreprise->fuseau_horaire) === 'Africa/Abidjan'    ? 'selected' : '' }}>Africa/Abidjan (UTC+0)</option>
                            <option value="Africa/Dakar"      {{ old('fuseau_horaire', $entreprise->fuseau_horaire) === 'Africa/Dakar'      ? 'selected' : '' }}>Africa/Dakar (UTC+0)</option>
                            <option value="Africa/Douala"     {{ old('fuseau_horaire', $entreprise->fuseau_horaire) === 'Africa/Douala'     ? 'selected' : '' }}>Africa/Douala (UTC+1)</option>
                            <option value="Africa/Casablanca" {{ old('fuseau_horaire', $entreprise->fuseau_horaire) === 'Africa/Casablanca' ? 'selected' : '' }}>Africa/Casablanca (UTC+1)</option>
                            <option value="Europe/Paris"      {{ old('fuseau_horaire', $entreprise->fuseau_horaire) === 'Europe/Paris'      ? 'selected' : '' }}>Europe/Paris (UTC+1/2)</option>
                        </select>
                    </div>
                    <div class="fg form-full">
                        <label>Pied de page des factures</label>
                        <textarea name="pied_page_facture" rows="3" placeholder="Mentions légales, coordonnées bancaires, remerciements…">{{ old('pied_page_facture', $entreprise->pied_page_facture) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════
             4. COORDONNÉES BANCAIRES
        ══════════════════════════════════════════════════════ --}}
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <span class="card-title">🏦 Coordonnées bancaires</span>
            </div>
            <div class="card-body">
                @php
                    $banque = $entreprise->coordonnees_bancaires ?? [];
                @endphp
                <div class="form-grid">
                    <div class="fg">
                        <label>Nom de la banque</label>
                        <input type="text" name="banque_nom" value="{{ old('banque_nom', $banque['banque'] ?? '') }}">
                    </div>
                    <div class="fg">
                        <label>RIB</label>
                        <input type="text" name="banque_rib" value="{{ old('banque_rib', $banque['rib'] ?? '') }}">
                    </div>
                    <div class="fg">
                        <label>IBAN</label>
                        <input type="text" name="banque_iban" value="{{ old('banque_iban', $banque['iban'] ?? '') }}" placeholder="FR76…">
                    </div>
                    <div class="fg">
                        <label>BIC / SWIFT</label>
                        <input type="text" name="banque_bic" value="{{ old('banque_bic', $banque['bic'] ?? '') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════
             5. NOTIFICATIONS
        ══════════════════════════════════════════════════════ --}}
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <span class="card-title">🔔 Notifications email</span>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="fg">
                        <label>Email expéditeur</label>
                        <input type="email" name="notif_email_expediteur" value="{{ old('notif_email_expediteur', $entreprise->notif_email_expediteur) }}" placeholder="noreply@monentreprise.com">
                    </div>
                    <div class="fg">
                        <label>Nom expéditeur</label>
                        <input type="text" name="notif_nom_expediteur" value="{{ old('notif_nom_expediteur', $entreprise->notif_nom_expediteur) }}" placeholder="Mon Entreprise">
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;gap:10px;margin-top:8px;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;font-weight:500;">
                        <input type="hidden" name="notif_envoi_auto_facture" value="0">
                        <input type="checkbox" name="notif_envoi_auto_facture" value="1"
                            {{ old('notif_envoi_auto_facture', $entreprise->notif_envoi_auto_facture) ? 'checked' : '' }}
                            style="width:16px;height:16px;cursor:pointer;">
                        Envoyer automatiquement les factures aux clients par email
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;font-weight:500;">
                        <input type="hidden" name="notif_envoi_recu_paiement" value="0">
                        <input type="checkbox" name="notif_envoi_recu_paiement" value="1"
                            {{ old('notif_envoi_recu_paiement', $entreprise->notif_envoi_recu_paiement) ? 'checked' : '' }}
                            style="width:16px;height:16px;cursor:pointer;">
                        Envoyer un reçu de paiement au client après encaissement
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;font-weight:500;">
                        <input type="hidden" name="notif_envoi_auto_devis" value="0">
                        <input type="checkbox" name="notif_envoi_auto_devis" value="1"
                            {{ old('notif_envoi_auto_devis', $entreprise->notif_envoi_auto_devis) ? 'checked' : '' }}
                            style="width:16px;height:16px;cursor:pointer;">
                        Envoyer automatiquement les devis aux clients par email
                    </label>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════
             6. SMTP
        ══════════════════════════════════════════════════════ --}}
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <span class="card-title">📧 Configuration SMTP</span>
            </div>
            <div class="card-body">
                <p style="font-size:12px;color:var(--muted);margin-bottom:14px;">
                    Configurez votre serveur d'envoi d'emails. Laissez vide pour utiliser la configuration par défaut du serveur.
                </p>
                <div class="form-grid">
                    <div class="fg">
                        <label>Hôte SMTP</label>
                        <input type="text" name="smtp_host" value="{{ old('smtp_host', $entreprise->smtp_host) }}" placeholder="smtp.gmail.com">
                    </div>
                    <div class="fg">
                        <label>Port SMTP</label>
                        <input type="text" name="smtp_port" value="{{ old('smtp_port', $entreprise->smtp_port) }}" placeholder="587">
                    </div>
                    <div class="fg">
                        <label>Utilisateur SMTP</label>
                        <input type="text" name="smtp_user" value="{{ old('smtp_user', $entreprise->smtp_user) }}" placeholder="user@gmail.com">
                    </div>
                    <div class="fg">
                        <label>Mot de passe SMTP</label>
                        <input type="password" name="smtp_password" placeholder="Laisser vide pour ne pas changer" autocomplete="new-password">
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════
             7. WHATSAPP
        ══════════════════════════════════════════════════════ --}}
        <div class="card" style="margin-bottom:28px;">
            <div class="card-header">
                <span class="card-title">💬 WhatsApp Business</span>
                @if($entreprise->whatsapp_token)
                    <span class="tag t-paye">✅ Configuré</span>
                @else
                    <span class="tag t-brouillon">Non configuré</span>
                @endif
            </div>
            <div class="card-body">
                <div class="fg">
                    <label>Token API WhatsApp Business</label>
                    <input type="password" name="whatsapp_token"
                        placeholder="{{ $entreprise->whatsapp_token ? 'Token existant — laisser vide pour ne pas changer' : 'Entrez votre token API WhatsApp Business' }}"
                        autocomplete="new-password">
                </div>
            </div>
        </div>

        {{-- ── BOUTON SAUVEGARDER ──────────────────────────── --}}
        <div style="display:flex;justify-content:flex-end;gap:12px;padding-bottom:40px;">
            <a href="{{ route('dashboard') }}" class="btn" style="background:transparent;border:1.5px solid var(--border);color:var(--muted);">
                Annuler
            </a>
            <button type="submit" class="btn btn-primary">
                💾 Sauvegarder la configuration
            </button>
        </div>

    </form>
</div>
@push('scripts')
<script>
function previewLogo(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('logo-preview');
        const placeholder = document.getElementById('logo-placeholder');
        preview.src = e.target.result;
        preview.style.display = 'block';
        if (placeholder) placeholder.style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
}
</script>
@endpush
@endsection