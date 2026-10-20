<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Entreprise;

class ConfigController extends Controller
{
    // ════════════════════════════════════════════════════════
    //  Helper privé — charge l'entreprise ou abort
    // ════════════════════════════════════════════════════════

    private function getEntreprise(): Entreprise
    {
        $entreprise = Entreprise::find(auth()->user()->entreprise_id);

        if (!$entreprise) {
            abort(404, 'Entreprise introuvable pour cet utilisateur.');
        }

        return $entreprise;
    }

    // ════════════════════════════════════════════════════════
    //  WEB — Admin uniquement (middleware IsAdmin sur routes)
    // ════════════════════════════════════════════════════════

    public function index()
    {
        $entreprise = $this->getEntreprise();
        return view('config.index', compact('entreprise'));
    }

    public function update(Request $request)
    {
        $entreprise = $this->getEntreprise();

        $data = $request->validate([
            // Logo
            'logo'                   => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            // Identité
            'nom'                    => 'required|string|max:150',
            'forme_juridique'        => 'nullable|string|max:50',
            'numero_fiscal'          => 'nullable|string|max:50',
            'numero_tva'             => 'nullable|string|max:50',
            'type_activite'          => 'nullable|in:produits,services,les_deux',
            // Contact
            'telephone'              => 'nullable|string|max:30',
            'email'                  => 'nullable|email|max:150',
            'site_web'               => 'nullable|string|max:250',
            // Adresse
            'adresse_ligne1'         => 'nullable|string|max:200',
            'adresse_ligne2'         => 'nullable|string|max:200',
            'ville'                  => 'nullable|string|max:100',
            'pays'                   => 'nullable|string|size:2',
            // Facturation
            'devise_defaut'          => 'nullable|string|size:3',
            'delai_paiement_jours'   => 'nullable|integer|min:0|max:365',
            'pied_page_facture'      => 'nullable|string|max:500',
            'debut_exercice_fiscal'  => 'nullable|integer|min:1|max:12',
            'fuseau_horaire'         => 'nullable|string|max:50',
            // Coordonnées bancaires
            'banque_nom'             => 'nullable|string|max:150',
            'banque_iban'            => 'nullable|string|max:50',
            'banque_bic'             => 'nullable|string|max:20',
            'banque_rib'             => 'nullable|string|max:50',
            // Notifications
            'notif_envoi_auto_facture'   => 'nullable|boolean',
            'notif_envoi_recu_paiement'  => 'nullable|boolean',
            'notif_envoi_auto_devis'     => 'nullable|boolean',
            'notif_email_expediteur'     => 'nullable|email|max:150',
            'notif_nom_expediteur'       => 'nullable|string|max:100',
            // SMTP
            'smtp_host'              => 'nullable|string|max:150',
            'smtp_port'              => 'nullable|string|max:10',
            'smtp_user'              => 'nullable|string|max:150',
            'smtp_password'          => 'nullable|string|max:255',
            // WhatsApp
            'whatsapp_token'         => 'nullable|string|max:255',
        ]);

        // ── Upload logo ──────────────────────────────────────
        if ($request->hasFile('logo')) {
            // Supprimer l'ancien logo s'il existe
            if ($entreprise->logo_url) {
                $oldPath = str_replace('/storage/', 'public/', $entreprise->logo_url);
                Storage::delete($oldPath);
            }
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo_url'] = '/storage/' . $path;
        }
        unset($data['logo']); // retirer le file object du tableau

        // Coordonnées bancaires → JSON
        $coordonnees = $entreprise->coordonnees_bancaires ?? [];
        if ($request->filled('banque_nom'))  $coordonnees['banque'] = $request->banque_nom;
        if ($request->filled('banque_iban')) $coordonnees['iban']   = $request->banque_iban;
        if ($request->filled('banque_bic'))  $coordonnees['bic']    = $request->banque_bic;
        if ($request->filled('banque_rib'))  $coordonnees['rib']    = $request->banque_rib;
        $data['coordonnees_bancaires'] = $coordonnees;

        // Checkboxes (non cochées = absent du POST = false)
        $data['notif_envoi_auto_facture']  = $request->boolean('notif_envoi_auto_facture');
        $data['notif_envoi_recu_paiement'] = $request->boolean('notif_envoi_recu_paiement');
        $data['notif_envoi_auto_devis']    = $request->boolean('notif_envoi_auto_devis');

        // Ne pas écraser smtp_password et whatsapp_token si vides
        if (empty($data['smtp_password']))   unset($data['smtp_password']);
        if (empty($data['whatsapp_token']))  unset($data['whatsapp_token']);

        // Nettoyage des clés bancaires isolées
        unset($data['banque_nom'], $data['banque_iban'], $data['banque_bic'], $data['banque_rib']);

        $entreprise->update($data);

        return redirect()->route('config.index')->with('success', 'Configuration sauvegardée avec succès.');
    }

    // ════════════════════════════════════════════════════════
    //  API — Admin/DG uniquement (middleware role:Admin,DG)
    // ════════════════════════════════════════════════════════

    public function apiShow(Request $request)
    {
        $entreprise = Entreprise::find(auth()->user()->entreprise_id);

        if (!$entreprise) {
            return response()->json(['message' => 'Entreprise introuvable.'], 404);
        }

        $coordonnees = $entreprise->coordonnees_bancaires ?? [];

        return response()->json([
            // Identité
            'id'                     => $entreprise->id,
            'nom'                    => $entreprise->nom,
            'forme_juridique'        => $entreprise->forme_juridique,
            'numero_fiscal'          => $entreprise->numero_fiscal,
            'numero_tva'             => $entreprise->numero_tva,
            'type_activite'          => $entreprise->type_activite,
            // Contact
            'telephone'              => $entreprise->telephone,
            'email'                  => $entreprise->email,
            'site_web'               => $entreprise->site_web,
            'logo_url'               => $entreprise->logo_url,
            // Adresse
            'adresse_ligne1'         => $entreprise->adresse_ligne1,
            'adresse_ligne2'         => $entreprise->adresse_ligne2,
            'ville'                  => $entreprise->ville,
            'pays'                   => $entreprise->pays,
            // Facturation
            'devise_defaut'          => $entreprise->devise_defaut ?? 'XOF',
            'delai_paiement_jours'   => $entreprise->delai_paiement_jours ?? 30,
            'pied_page_facture'      => $entreprise->pied_page_facture,
            'debut_exercice_fiscal'  => $entreprise->debut_exercice_fiscal ?? 1,
            'fuseau_horaire'         => $entreprise->fuseau_horaire ?? 'Africa/Niamey',
            // Coordonnées bancaires
            'coordonnees_bancaires'  => [
                'banque' => $coordonnees['banque'] ?? null,
                'iban'   => $coordonnees['iban']   ?? null,
                'bic'    => $coordonnees['bic']    ?? null,
                'rib'    => $coordonnees['rib']    ?? null,
            ],
            // Notifications
            'notif_envoi_auto_facture'   => (bool) $entreprise->notif_envoi_auto_facture,
            'notif_envoi_recu_paiement'  => (bool) $entreprise->notif_envoi_recu_paiement,
            'notif_envoi_auto_devis'     => (bool) $entreprise->notif_envoi_auto_devis,
            'notif_email_expediteur'     => $entreprise->notif_email_expediteur,
            'notif_nom_expediteur'       => $entreprise->notif_nom_expediteur,
            // SMTP (mot de passe non renvoyé)
            'smtp_host'              => $entreprise->smtp_host,
            'smtp_port'              => $entreprise->smtp_port,
            'smtp_user'              => $entreprise->smtp_user,
            // WhatsApp (token non renvoyé en clair)
            'whatsapp_configured'    => !empty($entreprise->whatsapp_token),
        ]);
    }

    public function apiUpdate(Request $request)
    {
        $entreprise = Entreprise::find(auth()->user()->entreprise_id);

        if (!$entreprise) {
            return response()->json(['message' => 'Entreprise introuvable.'], 404);
        }

        $data = $request->validate([
            // Identité
            'nom'                    => 'sometimes|required|string|max:150',
            'forme_juridique'        => 'nullable|string|max:50',
            'numero_fiscal'          => 'nullable|string|max:50',
            'numero_tva'             => 'nullable|string|max:50',
            'type_activite'          => 'nullable|in:produits,services,les_deux',
            // Contact
            'telephone'              => 'nullable|string|max:30',
            'email'                  => 'nullable|email|max:150',
            'site_web'               => 'nullable|string|max:250',
            // Adresse
            'adresse_ligne1'         => 'nullable|string|max:200',
            'adresse_ligne2'         => 'nullable|string|max:200',
            'ville'                  => 'nullable|string|max:100',
            'pays'                   => 'nullable|string|size:2',
            // Facturation
            'devise_defaut'          => 'nullable|string|size:3',
            'delai_paiement_jours'   => 'nullable|integer|min:0|max:365',
            'pied_page_facture'      => 'nullable|string|max:500',
            'debut_exercice_fiscal'  => 'nullable|integer|min:1|max:12',
            'fuseau_horaire'         => 'nullable|string|max:50',
            // Coordonnées bancaires
            'coordonnees_bancaires'          => 'nullable|array',
            'coordonnees_bancaires.banque'   => 'nullable|string|max:150',
            'coordonnees_bancaires.iban'     => 'nullable|string|max:50',
            'coordonnees_bancaires.bic'      => 'nullable|string|max:20',
            'coordonnees_bancaires.rib'      => 'nullable|string|max:50',
            // Notifications
            'notif_envoi_auto_facture'   => 'nullable|boolean',
            'notif_envoi_recu_paiement'  => 'nullable|boolean',
            'notif_envoi_auto_devis'     => 'nullable|boolean',
            'notif_email_expediteur'     => 'nullable|email|max:150',
            'notif_nom_expediteur'       => 'nullable|string|max:100',
            // SMTP
            'smtp_host'              => 'nullable|string|max:150',
            'smtp_port'              => 'nullable|string|max:10',
            'smtp_user'              => 'nullable|string|max:150',
            'smtp_password'          => 'nullable|string|max:255',
            // WhatsApp
            'whatsapp_token'         => 'nullable|string|max:255',
        ]);

        // Ne pas écraser si non fourni
        if (!$request->has('smtp_password') || $request->smtp_password === null) {
            unset($data['smtp_password']);
        }
        if (!$request->has('whatsapp_token') || $request->whatsapp_token === null) {
            unset($data['whatsapp_token']);
        }

        $entreprise->update($data);

        return response()->json([
            'message'    => 'Configuration mise à jour avec succès.',
            'entreprise' => [
                'id'       => $entreprise->id,
                'nom'      => $entreprise->nom,
                'devise'   => $entreprise->devise_defaut ?? 'XOF',
                'logo_url' => $entreprise->logo_url,
            ],
        ]);
    }
}