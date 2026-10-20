<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    protected $table = 'paiements';

    protected $fillable = [
        'entreprise_id','facture_id','date_paiement','montant','devise',
        'taux_change','montant_devise_principale','type_paiement',
        'libelle_paiement','details_paiement','reference_externe',
        'notes','est_auto','saisie_par',
    ];

    protected $casts = [
        'date_paiement'            => 'date',
        'details_paiement'         => 'array',
        'est_auto'                 => 'boolean',
        // ✅ float
        'montant'                  => 'float',
        'taux_change'              => 'float',
        'montant_devise_principale'=> 'float',
    ];

    public function entreprise() { return $this->belongsTo(Entreprise::class, 'entreprise_id'); }
    public function facture()    { return $this->belongsTo(Facture::class, 'facture_id'); }
    public function saiseur()    { return $this->belongsTo(Utilisateur::class, 'saisie_par'); }

    protected static function booted(): void
    {
        static::created(fn(Paiement $p) => $p->facture->recalculerMontants());
        static::deleted(fn(Paiement $p) => $p->facture->recalculerMontants());
    }

    public function getModeLabelAttribute(): string
    {
        return match ($this->type_paiement) {
            'virement'     => 'Virement bancaire',
            'cheque'       => 'Chèque',
            'carte'        => 'Carte bancaire',
            'especes'      => 'Espèces',
            'prelevement'  => 'Prélèvement',
            'mobile_money' => 'Mobile Money',
            default        => 'Autre',
        };
    }
}