<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Communication extends Model
{
    protected $table='communications';
    protected $fillable=['entreprise_id','type_comm','facture_id','devis_id','utilisateur_id','statut','planifie_le','envoye_le','email_destinataire','sujet','template','id_message_fournisseur','ouvert_le','type_relance','decalage_jours','canal_relance','titre_push','corps_push','type_push','id_message_fcm','message_erreur'];
    protected $casts=['planifie_le'=>'datetime','envoye_le'=>'datetime','ouvert_le'=>'datetime'];
    public function entreprise()  { return $this->belongsTo(Entreprise::class,'entreprise_id'); }
    public function facture()     { return $this->belongsTo(Facture::class,'facture_id'); }
    public function devis()       { return $this->belongsTo(Devis::class,'devis_id'); }
    public function utilisateur() { return $this->belongsTo(Utilisateur::class,'utilisateur_id'); }
    public function scopeRelances($q)  { return $q->where('type_comm','relance'); }
    public function scopeEmails($q)    { return $q->where('type_comm','email'); }
    public function scopePlanifies($q) { return $q->where('statut','planifie')->where('planifie_le','<=',now()); }
}
