<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class GroupeTaxe extends Model
{
    protected $table = 'groupes_taxes';

    protected $fillable = [
        'entreprise_id','nom','description','composantes',
        'taux_total','est_defaut','est_actif',
    ];

    protected $casts = [
        'composantes' => 'array',
        'est_defaut'  => 'boolean',
        'est_actif'   => 'boolean',
        // ✅ float
        'taux_total'  => 'float',
    ];

    public function entreprise() { return $this->belongsTo(Entreprise::class, 'entreprise_id'); }
    public function clients()    { return $this->hasMany(Client::class, 'groupe_taxes_id'); }
    public function produits()   { return $this->hasMany(ProduitService::class, 'groupe_taxes_id'); }

    public function calculer(float $montantHT): array
    {
        $composantes = $this->composantes ?? [];
        $detail      = [];
        $totalTaxes  = 0;
        $cumul       = $montantHT;

        foreach ($composantes as $comp) {
            $base = match ($comp['s_applique_sur'] ?? 'ht') {
                'ht'               => $montantHT,
                'taxe_precedente'  => $totalTaxes,
                'cumul'            => $cumul,
                default            => $montantHT,
            };

            $mt       = round($base * ($comp['taux'] ?? 0), 2);
            $cumul   += $mt;
            $totalTaxes += $mt;

            $detail[] = [
                'nom'            => $comp['nom'],
                'code'           => $comp['code'] ?? '',
                'taux'           => $comp['taux'],
                'base'           => round($base, 2),
                'montant'        => $mt,
                'code_comptable' => $comp['code_comptable'] ?? '',
            ];
        }

        return [
            'total_ht'    => round($montantHT, 2),
            'total_taxes' => round($totalTaxes, 2),
            'total_ttc'   => round($montantHT + $totalTaxes, 2),
            'detail'      => $detail,
        ];
    }
}