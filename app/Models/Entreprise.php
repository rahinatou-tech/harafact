<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Entreprise extends Model
{
    use HasFactory;
    protected $table = 'entreprises';
    protected $fillable = [
        'nom','forme_juridique','numero_fiscal','numero_tva',
        'adresse_ligne1','adresse_ligne2','ville','pays',
        'telephone','email','site_web','logo_url',
        'devise_defaut','delai_paiement_jours','coordonnees_bancaires',
        'pied_page_facture','debut_exercice_fiscal','fuseau_horaire','type_activite',
        'notif_jours_avant_echeance','notif_jours_apres_echeance',
        'notif_envoi_auto_facture','notif_envoi_recu_paiement',
        'notif_envoi_auto_devis','notif_email_expediteur','notif_nom_expediteur',
        'sequences_numerotation','whatsapp_token','smtp_host','smtp_port','smtp_user','smtp_password','est_actif',
    ];
    protected $casts = [
        'coordonnees_bancaires'      => 'array',
        'notif_jours_avant_echeance' => 'array',
        'notif_jours_apres_echeance' => 'array',
        'sequences_numerotation'     => 'array',
        'notif_envoi_auto_facture'   => 'boolean',
        'notif_envoi_recu_paiement'  => 'boolean',
        'notif_envoi_auto_devis'     => 'boolean',
        'est_actif'                  => 'boolean',
    ];
    protected $hidden = ['smtp_password','whatsapp_token'];
    public function utilisateurs()   { return $this->hasMany(Utilisateur::class,'entreprise_id'); }
    public function clients()        { return $this->hasMany(Client::class,'entreprise_id'); }
    public function produits()       { return $this->hasMany(ProduitService::class,'entreprise_id'); }
    public function groupesTaxes()   { return $this->hasMany(GroupeTaxe::class,'entreprise_id'); }
    public function devis()          { return $this->hasMany(Devis::class,'entreprise_id'); }
    public function factures()       { return $this->hasMany(Facture::class,'entreprise_id'); }
    public function paiements()      { return $this->hasMany(Paiement::class,'entreprise_id'); }
    public function communications() { return $this->hasMany(Communication::class,'entreprise_id'); }
    public function prochainNumero(string $type): string {
        $prefixes=['simple'=>'FSI','proforma'=>'PRO','avoir'=>'AVO','acompte'=>'ACP','solde'=>'SOL','recurrente'=>'REC','devis'=>'DEV'];
        $sequences=$this->sequences_numerotation??[];
        $prefix=$prefixes[$type]??strtoupper(substr($type,0,3));
        $annee=now()->year;
        $sequences[$prefix]=($sequences[$prefix]??0)+1;
        $this->update(['sequences_numerotation'=>$sequences]);
        return $prefix.'-'.$annee.'-'.str_pad($sequences[$prefix],5,'0',STR_PAD_LEFT);
    }
}
