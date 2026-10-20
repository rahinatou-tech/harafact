<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Devis extends Model
{
    protected $table = 'devis';

    protected $fillable = [
        'entreprise_id','numero_devis','statut','client_id','snapshot_client',
        'date_emission','date_validite','devise','taux_change',
        'total_ht','total_remises_ht','total_taxes','detail_taxes','total_ttc',
        'converti_en','facture_convertie_id','notes','notes_internes',
        'url_pdf','cree_par','envoye_le','repondu_le',
    ];

    protected $casts = [
        'snapshot_client'  => 'array',
        'detail_taxes'     => 'array',
        'date_emission'    => 'date',
        'date_validite'    => 'date',
        'envoye_le'        => 'datetime',
        'repondu_le'       => 'datetime',
        // ✅ float
        'total_ht'         => 'float',
        'total_remises_ht' => 'float',
        'total_taxes'      => 'float',
        'total_ttc'        => 'float',
        'taux_change'      => 'float',
    ];

    public function entreprise()       { return $this->belongsTo(Entreprise::class, 'entreprise_id'); }
    public function client()           { return $this->belongsTo(Client::class, 'client_id'); }
    public function factureConvertie() { return $this->belongsTo(Facture::class, 'facture_convertie_id'); }
    public function createur()         { return $this->belongsTo(Utilisateur::class, 'cree_par'); }
    public function communications()   { return $this->hasMany(Communication::class, 'devis_id'); }
    public function lignes()           { return $this->hasMany(LigneDocument::class, 'document_id')->where('type_document', 'devis')->orderBy('ordre_affichage'); }

    public function estExpire(): bool       { return $this->date_validite && $this->date_validite->isPast() && $this->statut === 'envoye'; }
    public function peutEtreConverti(): bool{ return $this->statut === 'accepte'; }

    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            'brouillon' => 'Brouillon',
            'envoye'    => 'Envoyé',
            'accepte'   => 'Accepté',
            'refuse'    => 'Refusé',
            'expire'    => 'Expiré',
            'converti'  => 'Converti',
            default     => ucfirst($this->statut),
        };
    }
}