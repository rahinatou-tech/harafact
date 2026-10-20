<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Utilisateur;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;



class AuthController extends Controller
{
    // ── Page login ──────────────────────────────────────────────────────
    public function showLogin()
    {
        if (Auth::guard('utilisateur')->check()) {
            return $this->redirectParRole(Auth::guard('utilisateur')->user());
        }
        return view('auth.login');
    }

    // ── Traitement login ─────────────────────────────────────────────────
    public function login(Request $request)
    {
        $request->validate([
            'email'      => 'required|email',
            'mot_de_passe' => 'required|string|min:6',
        ], [
            'email.required'          => 'L\'email est obligatoire.',
            'email.email'             => 'Email invalide.',
            'mot_de_passe.required'   => 'Le mot de passe est obligatoire.',
        ]);

        // Chercher l'utilisateur par email
        $utilisateur = Utilisateur::where('email', $request->email)
            ->where('est_actif', true)
            ->first();

        // Vérifier existence + mot de passe
        if (!$utilisateur || !Hash::check($request->mot_de_passe, $utilisateur->mot_de_passe)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email ou mot de passe incorrect.']);
        }

        // Vérifier si le compte est actif
        if (!$utilisateur->est_actif) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Votre compte est désactivé. Contactez l\'administrateur.']);
        }

        // Connecter l'utilisateur
        Auth::guard('utilisateur')->login($utilisateur, $request->boolean('remember'));

        // Mettre à jour la dernière connexion
        $utilisateur->update(['derniere_connexion_le' => now()]);

        $request->session()->regenerate();

        // Redirection selon le rôle (automatique)
        return $this->redirectParRole($utilisateur);
    }

    // ── Logout ───────────────────────────────────────────────────────────
    public function logout(Request $request)
    {
        Auth::guard('utilisateur')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('success', 'Vous êtes déconnecté.');
    }

    // ── Redirection automatique selon rôle ────────────────────────────────
    private function redirectParRole(Utilisateur $utilisateur)
    {
        return match($utilisateur->role) {
            'DG'        => redirect()->route('dashboard')->with('info', "Bienvenue, {$utilisateur->prenom} — Mode lecture seule"),
            'Employe'   => redirect()->route('dashboard')->with('success', "Bienvenue, {$utilisateur->prenom} !"),
            'Comptable' => redirect()->route('dashboard')->with('success', "Bienvenue, {$utilisateur->prenom} !"),
            'Admin'     => redirect()->route('config.index')->with('success', "Bienvenue, {$utilisateur->prenom} !"),
            default     => redirect()->route('login')->withErrors(['email' => 'Rôle non reconnu.']),
        };
    }


    // ── Page "mot de passe oublié" ────────────────────────────────────────
public function showForgotForm()
{
    return view('auth.forgot-password');
}

// ── Envoi du lien de réinitialisation ────────────────────────────────
public function sendResetLink(Request $request)
{
    $request->validate([
        'email' => 'required|email',
    ], [
        'email.required' => 'L\'email est obligatoire.',
        'email.email'    => 'Email invalide.',
    ]);

    $utilisateur = Utilisateur::where('email', $request->email)
                              ->where('est_actif', true)
                              ->first();

    // Toujours afficher le même message (sécurité)
    if (!$utilisateur) {
        return back()->with('success', 'Si cet email existe, un lien vous a été envoyé.');
    }

    // Générer un token unique
    $token = Str::random(64);

    // Sauvegarder dans l'utilisateur
    $utilisateur->update([
        'token_reinit'            => Hash::make($token),
        'token_reinit_expire_le'  => Carbon::now()->addMinutes(60),
    ]);

    // Envoyer l'email
    $resetUrl = route('password.reset', ['token' => $token, 'email' => $utilisateur->email]);

    Mail::send('emails.reset-password', ['url' => $resetUrl, 'utilisateur' => $utilisateur], function($message) use ($utilisateur) {
        $message->to($utilisateur->email)
                ->subject('Réinitialisation de votre mot de passe — HaRaFact');
    });

    return back()->with('success', 'Si cet email existe, un lien vous a été envoyé.');
}

// ── Page de réinitialisation ──────────────────────────────────────────
public function showResetForm(Request $request, $token)
{
    return view('auth.reset-password', [
        'token' => $token,
        'email' => $request->email,
    ]);
}

// ── Traitement réinitialisation ───────────────────────────────────────
public function resetPassword(Request $request)
{
    $request->validate([
        'email'            => 'required|email',
        'token'            => 'required',
        'mot_de_passe'     => 'required|min:6|confirmed',
    ], [
        'mot_de_passe.required'  => 'Le mot de passe est obligatoire.',
        'mot_de_passe.min'       => 'Minimum 6 caractères.',
        'mot_de_passe.confirmed' => 'Les mots de passe ne correspondent pas.',
    ]);

    $utilisateur = Utilisateur::where('email', $request->email)
                              ->where('est_actif', true)
                              ->first();

    // Vérifications
    if (!$utilisateur || !$utilisateur->token_reinit) {
        return back()->withErrors(['email' => 'Lien invalide ou expiré.']);
    }

    if (!Hash::check($request->token, $utilisateur->token_reinit)) {
        return back()->withErrors(['email' => 'Lien invalide ou expiré.']);
    }

    if (Carbon::now()->isAfter($utilisateur->token_reinit_expire_le)) {
        return back()->withErrors(['email' => 'Ce lien a expiré. Faites une nouvelle demande.']);
    }

    // Mettre à jour le mot de passe
    $utilisateur->update([
        'mot_de_passe'           => Hash::make($request->mot_de_passe),
        'token_reinit'           => null,
        'token_reinit_expire_le' => null,
    ]);

    return redirect()->route('login')
                     ->with('success', 'Mot de passe mis à jour. Vous pouvez vous connecter.');
}

// ── LOGIN ──────────────────────────────────────────────────────
    public function apiLogin(Request $request)
    {
       $data = $request->validate([
        'email'       => 'required|email',
        'mot_de_passe'=> 'required|string',
        'fcm_token'   => 'nullable|string',
        'nom_appareil'=> 'nullable|string|max:100',
    ]);

    $user = Utilisateur::where('email', $data['email'])->first();

    \Log::info('🔍 Tentative de connexion', [
        'email'          => $data['email'],
        'user_trouve'    => $user ? true : false,
        'password_match' => $user ? Hash::check($data['mot_de_passe'], $user->mot_de_passe) : false,
        'mot_de_passe_saisi' => $data['mot_de_passe'],
    ]);

    if (!$user || !Hash::check($data['mot_de_passe'], $user->mot_de_passe)) {
        return response()->json(['message' => 'Identifiants incorrects.'], 401);
    }
 
        if (!$user->est_actif) {
            return response()->json(['message' => 'Compte désactivé. Contactez votre administrateur.'], 403);
        }
 
        // Générer les tokens
        $accessToken  = Str::random(64);
        $refreshToken = Str::random(80);
 
        $user->update([
            'refresh_token_hash'       => Hash::make($refreshToken),
            'refresh_token_expire_le'  => Carbon::now()->addDays(30),
            'derniere_connexion_le'    => now(),
            'token_fcm'                => $data['fcm_token'] ?? $user->token_fcm,
            'nom_appareil'             => $data['nom_appareil'] ?? $user->nom_appareil,
        ]);
 
        // Stocker l'access token via Sanctum ou custom cache
        cache()->put("access_token_{$accessToken}", $user->id, now()->addHours(8));
 
        return response()->json([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in'    => 28800, // 8h en secondes
            'utilisateur'   => $this->formatUser($user),
        ]);
    }
 
    // ── REFRESH ────────────────────────────────────────────────────
    public function apiRefresh(Request $request)
    {
        $data = $request->validate([
            'refresh_token' => 'required|string',
        ]);
 
        // Trouver l'utilisateur par refresh token
        $users = Utilisateur::whereNotNull('refresh_token_hash')
            ->where('refresh_token_expire_le', '>', now())
            ->get();
 
        $user = null;
        foreach ($users as $u) {
            if (Hash::check($data['refresh_token'], $u->refresh_token_hash)) {
                $user = $u;
                break;
            }
        }
 
        if (!$user) {
            return response()->json(['message' => 'Refresh token invalide ou expiré.'], 401);
        }
 
        // Nouveau access token
        $accessToken  = Str::random(64);
        $refreshToken = Str::random(80);
 
        $user->update([
            'refresh_token_hash'      => Hash::make($refreshToken),
            'refresh_token_expire_le' => Carbon::now()->addDays(30),
        ]);
 
        cache()->put("access_token_{$accessToken}", $user->id, now()->addHours(8));
 
        return response()->json([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in'    => 28800,
        ]);
    }
 
    // ── LOGOUT ────────────────────────────────────────────────────
    public function apiLogout(Request $request)
    {
        $token = $request->bearerToken();
        if ($token) {
            cache()->forget("access_token_{$token}");
        }
 
        auth()->user()->update([
            'refresh_token_hash'      => null,
            'refresh_token_expire_le' => null,
        ]);
 
        return response()->json(['message' => 'Déconnecté avec succès.']);
    }
 
    // ── ME ────────────────────────────────────────────────────────
    public function apiMe(Request $request)
{
    try {
        $user = $request->user() ?? auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Non authentifié.', 'debug' => 'user null'], 401);
        }

        $user->load('entreprise');

        return response()->json($this->formatUser($user, true));

    } catch (\Exception $e) {
        return response()->json([
            'message' => $e->getMessage(),
            'file'    => basename($e->getFile()),
            'line'    => $e->getLine(),
        ], 500);
    }
}
 
 
    // ── FCM TOKEN ─────────────────────────────────────────────────
    public function updateFcmToken(Request $request)
    {
        $request->validate(['token' => 'required|string']);
        auth()->user()->update(['token_fcm' => $request->token]);
        return response()->json(['message' => 'Token FCM mis à jour.']);
    }
 
    // ── DEMANDE RÉINIT MOT DE PASSE ────────────────────────────────
    public function apiDemandeReinit(Request $request)
    {
        $request->validate(['email' => 'required|email']);
 
        $user = Utilisateur::where('email', $request->email)->first();
        if (!$user) {
            // Réponse neutre pour sécurité
            return response()->json(['message' => 'Si cet email existe, un code a été envoyé.']);
        }
 
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update([
            'token_reinit'            => Hash::make($code),
            'token_reinit_expire_le'  => now()->addMinutes(15),
        ]);
 
        // Envoyer le code par email
        \Mail::raw("Votre code de réinitialisation : $code (valable 15 min)", function ($m) use ($user) {
            $m->to($user->email)->subject('Réinitialisation mot de passe');
        });
 
        return response()->json(['message' => 'Si cet email existe, un code a été envoyé.']);
    }
 
    // ── RÉINIT MOT DE PASSE ────────────────────────────────────────
    public function apiReinitPassword(Request $request)
    {
        $data = $request->validate([
            'email'           => 'required|email',
            'code'            => 'required|string|size:6',
            'nouveau_mot_de_passe' => 'required|string|min:8|confirmed',
        ]);
 
        $user = Utilisateur::where('email', $data['email'])
            ->where('token_reinit_expire_le', '>', now())
            ->first();
 
        if (!$user || !Hash::check($data['code'], $user->token_reinit)) {
            return response()->json(['message' => 'Code invalide ou expiré.'], 422);
        }
 
        $user->update([
            'mot_de_passe'            => Hash::make($data['nouveau_mot_de_passe']),
            'token_reinit'            => null,
            'token_reinit_expire_le'  => null,
        ]);
 
        return response()->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }
 
    // ── FORMAT USER ───────────────────────────────────────────────
    private function formatUser(Utilisateur $user, bool $withEntreprise = false): array
    {
        $data = [
            'id'          => $user->id,
            'nom'         => $user->nom,
            'prenom'      => $user->prenom,
            'nom_complet' => $user->nom_complet,
            'initiales'   => $user->initiales,
            'email'       => $user->email,
            'role'        => $user->role,
            'est_actif'   => $user->est_actif,
        ];
 
        if ($withEntreprise && $user->relationLoaded('entreprise')) {
            $e = $user->entreprise;
            $data['entreprise'] = [
                'id'       => $e->id,
                'nom'      => $e->nom,
                'devise'   => $e->devise ?? 'XOF',
                'logo_url' => $e->logo_url,
            ];
        }
 
        return $data;
    }

}