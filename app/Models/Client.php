<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $table    = 'clients';
    protected $deletedAt= 'supprime_le';

    protected $fillable = [
        'entreprise_id','type_client','code_client','raison_sociale','prenom','nom',
        'numero_fiscal','numero_tva','email','telephone','adresse_ligne1','adresse_ligne2',
        'ville','pays','devise','delai_paiement_jours','groupe_taxes_id',
        'plafond_credit','score_fiabilite','delai_paiement_moyen',
        'notes','est_actif','cree_par',
    ];

    protected $casts = [
        'est_actif'            => 'boolean',
        // ✅ float
        'plafond_credit'       => 'float',
        'delai_paiement_moyen' => 'float',
        'score_fiabilite'      => 'float',
    ];

    public function entreprise()  { return $this->belongsTo(Entreprise::class, 'entreprise_id'); }
    public function groupeTaxe()  { return $this->belongsTo(GroupeTaxe::class, 'groupe_taxes_id'); }
    public function devis()       { return $this->hasMany(Devis::class, 'client_id'); }
    public function factures()    { return $this->hasMany(Facture::class, 'client_id'); }
    public function createur()    { return $this->belongsTo(Utilisateur::class, 'cree_par'); }

    public function getNomAffichageAttribute(): string
    {
        // Le Nom est toujours prioritaire pour l'affichage (listes, factures...).
        // La raison sociale n'est utilisée en repli que si le nom est réellement
        // vide (cas d'anciens clients "entreprise" créés sans nom de contact).
        if (!empty($this->nom)) {
            return trim($this->prenom . ' ' . $this->nom);
        }

        return $this->raison_sociale ?? '';
    }

    public function getMontantDuTotalAttribute(): float
    {
        return (float) $this->factures()
            ->whereIn('statut', ['envoye', 'partiellement_paye', 'en_retard'])
            ->sum('montant_du');
    }

    public function getScoreCouleurAttribute(): string
    {
        $s = $this->score_fiabilite ?? 50;
        return $s >= 80 ? 'green' : ($s >= 50 ? 'orange' : 'red');
    }

   public static function genererCode(int $entrepriseId): string
{
    // withTrashed() inclut les clients supprimés (leur code reste "consommé")
    $dernier = self::withTrashed()
        ->where('entreprise_id', $entrepriseId)
        ->where('code_client', 'like', 'CLI-%')
        ->orderByRaw("CAST(substring(code_client from 5) AS INTEGER) DESC")
        ->first();

    $prochainNumero = $dernier
        ? ((int) substr($dernier->code_client, 4)) + 1
        : 1;

    $code = 'CLI-' . str_pad($prochainNumero, 4, '0', STR_PAD_LEFT);

    // Sécurité anti-collision (ex: requêtes concurrentes)
    while (self::withTrashed()->where('entreprise_id', $entrepriseId)->where('code_client', $code)->exists()) {
        $prochainNumero++;
        $code = 'CLI-' . str_pad($prochainNumero, 4, '0', STR_PAD_LEFT);
    }

    return $code;
}
}