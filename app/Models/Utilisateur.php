<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Utilisateur extends Authenticatable
{
    use Notifiable, SoftDeletes;
    protected $table='utilisateurs';
    protected $deletedAt='supprime_le';
    protected $fillable=[
        'entreprise_id','nom','prenom','email','mot_de_passe','role','est_actif',
        'double_auth_active','double_auth_secret','token_reinit','token_reinit_expire_le',
        'refresh_token_hash','refresh_token_expire_le','token_fcm','nom_appareil',
        'refresh_token_revoque_le','derniere_connexion_le','cree_par',
    ];
    protected $hidden=['mot_de_passe','double_auth_secret','refresh_token_hash','token_reinit'];
    protected $casts=[
        'est_actif'=>'boolean','double_auth_active'=>'boolean',
        'token_reinit_expire_le'=>'datetime','refresh_token_expire_le'=>'datetime',
        'derniere_connexion_le'=>'datetime',
    ];
    public function getAuthPassword() { return $this->mot_de_passe; }
    public function entreprise() { return $this->belongsTo(Entreprise::class,'entreprise_id'); }
    public function createur()   { return $this->belongsTo(Utilisateur::class,'cree_par'); }
    public function isDG()        { return $this->role==='DG'; }
    public function isAdmin()     { return $this->role==='Admin'; }
    public function isComptable() { return $this->role==='Comptable'; }
    public function isEmploye()   { return $this->role==='Employe'; }
    
    // Permissions
    public function peutCreerFactures()  { return in_array($this->role, ['Employe']); }
    public function peutSoumettreFactures() { return in_array($this->role, ['Employe']); }
    public function peutValiderFactures() { return in_array($this->role, ['Comptable']); }
    public function peutEnvoyerFactures() { return in_array($this->role, ['Employe']); }
    public function peutSaisirPaiements() { return in_array($this->role, ['Comptable']); }
    public function peutConfigurer() { return $this->role==='Admin'; }
    public function peutLire() { return in_array($this->role, ['DG', 'Employe', 'Comptable', 'Admin']); }
    public function peutCreer()      { return in_array($this->role,['Employe']); }
    public function peutPayer()      { return in_array($this->role,['Comptable']); }
    public function getNomCompletAttribute(): string { return trim($this->prenom.' '.$this->nom); }
    public function getInitialesAttribute(): string { return strtoupper(substr($this->prenom,0,1).substr($this->nom,0,1)); }
}