<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProduitService extends Model
{
    use SoftDeletes;

    protected $table    = 'produits_services';
    protected $deletedAt= 'supprime_le';

    protected $fillable = [
        'entreprise_id','reference','nom','description','type','unite',
        'prix_unitaire_ht','groupe_taxes_id','code_comptable','stock','est_actif','cree_par',
    ];

    protected $casts = [
        'est_actif'       => 'boolean',
        // ✅ float
        'prix_unitaire_ht'=> 'float',
    ];

    public function entreprise() { return $this->belongsTo(Entreprise::class, 'entreprise_id'); }
    public function groupeTaxe() { return $this->belongsTo(GroupeTaxe::class, 'groupe_taxes_id'); }
    public function lignes()     { return $this->hasMany(LigneDocument::class, 'produit_id'); }

    public function getPrixFormatteAttribute(): string
    {
        return number_format($this->prix_unitaire_ht, 0, ',', ' ') . ' XOF';
    }

    public function estProduit(): bool { return $this->type === 'produit'; }
    public function estService(): bool { return $this->type === 'service'; }
}