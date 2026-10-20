<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LigneDocument extends Model
{
    protected $table = 'lignes_documents';

    protected $fillable = [
        'type_document','document_id','ordre_affichage','produit_id',
        'designation','quantite','unite','prix_unitaire_ht',
        'groupe_taxes_id','snapshot_taxes','type_remise','valeur_remise',
        'total_ht_ligne','detail_taxes_ligne','total_taxes_ligne','total_ttc_ligne',
    ];

    protected $casts = [
        'snapshot_taxes'    => 'array',
        'detail_taxes_ligne'=> 'array',
        // ✅ float
        'quantite'          => 'float',
        'prix_unitaire_ht'  => 'float',
        'valeur_remise'     => 'float',
        'total_ht_ligne'    => 'float',
        'total_taxes_ligne' => 'float',
        'total_ttc_ligne'   => 'float',
    ];

    public function produit()    { return $this->belongsTo(ProduitService::class, 'produit_id'); }
    public function groupeTaxe() { return $this->belongsTo(GroupeTaxe::class, 'groupe_taxes_id'); }

    public function calculerTotaux(): void
    {
        $remise = 0;
        if ($this->type_remise === 'pourcentage')
            $remise = $this->quantite * $this->prix_unitaire_ht * ($this->valeur_remise / 100);
        elseif ($this->type_remise === 'montant_fixe')
            $remise = $this->valeur_remise;

        $totalHT     = round(($this->quantite * $this->prix_unitaire_ht) - $remise, 2);
        $taxDetail   = [];
        $totalTaxes  = 0;

        if ($this->groupeTaxe) {
            $calcul     = $this->groupeTaxe->calculer($totalHT);
            $taxDetail  = $calcul['detail'];
            $totalTaxes = $calcul['total_taxes'];
        }

        $this->update([
            'total_ht_ligne'     => $totalHT,
            'detail_taxes_ligne' => $taxDetail,
            'total_taxes_ligne'  => $totalTaxes,
            'total_ttc_ligne'    => round($totalHT + $totalTaxes, 2),
        ]);
    }
}