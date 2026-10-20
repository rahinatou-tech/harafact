<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Facture extends Model
{
    use SoftDeletes;

    protected $table    = 'factures';
    protected $deletedAt= 'supprime_le';

    protected $fillable = [
        'entreprise_id','type_facture','numero_facture','statut','statut_validation',
        'client_id','snapshot_client','date_emission','date_echeance','envoye_le',
        'soumise_le','soumise_par','rejet_raison','devise','taux_change',
        'total_ht','total_remises_ht','total_taxes','detail_taxes','total_ttc',
        'montant_paye','montant_du','facture_parente_id','devis_origine_id',
        'proforma_origine_id','config_recurrence_id','type_acompte','valeur_acompte',
        'motif_avoir','paiement_auto','notes','notes_internes','url_pdf','chemin_pdf',
        'email_envoye_a','whatsapp_envoye_a','validee_par','validee_le','cree_par',
        'modifie_par','token_public','valide_par','valide_le','canal_envoi','envoye_client_le',
    ];

    protected $casts = [
        // JSON
        'snapshot_client'  => 'array',
        'detail_taxes'     => 'array',
        // Dates
        'date_emission'    => 'date',
        'date_echeance'    => 'date',
        'envoye_le'        => 'datetime',
        'soumise_le'       => 'datetime',
        'validee_le'       => 'datetime',
        // Booléen
        'paiement_auto'    => 'boolean',
        // ✅ float au lieu de decimal:2 → PostgreSQL retourne un vrai nombre dans le JSON
        'total_ht'         => 'float',
        'total_remises_ht' => 'float',
        'total_taxes'      => 'float',
        'total_ttc'        => 'float',
        'montant_paye'     => 'float',
        'montant_du'       => 'float',
        'valeur_acompte'   => 'float',
        'taux_change'      => 'float',
    ];

    public function entreprise()       { return $this->belongsTo(Entreprise::class, 'entreprise_id'); }
    public function client()           { return $this->belongsTo(Client::class, 'client_id'); }
    public function factureParente()   { return $this->belongsTo(Facture::class, 'facture_parente_id'); }
    public function proformaOrigine()  { return $this->belongsTo(Facture::class, 'proforma_origine_id'); }
    public function devisOrigine()     { return $this->belongsTo(Devis::class, 'devis_origine_id'); }
    public function configRecurrence() { return $this->belongsTo(ConfigRecurrence::class, 'config_recurrence_id'); }
    public function facturesFilles()   { return $this->hasMany(Facture::class, 'facture_parente_id'); }
    public function paiements()        { return $this->hasMany(Paiement::class, 'facture_id'); }
    public function communications()   { return $this->hasMany(Communication::class, 'facture_id'); }
    public function createur()         { return $this->belongsTo(Utilisateur::class, 'cree_par'); }
    public function soumetteur()       { return $this->belongsTo(Utilisateur::class, 'soumise_par'); }
    public function validateur()       { return $this->belongsTo(Utilisateur::class, 'validee_par'); }
    public function lignes()           { return $this->hasMany(LigneDocument::class, 'document_id')->where('type_document', 'facture')->orderBy('ordre_affichage'); }

    public function scopePayees($q)   { return $q->where('statut', 'paye'); }
    public function scopeEnRetard($q) { return $q->where('statut', 'en_retard'); }
    public function scopeDuMois($q)   { return $q->whereMonth('date_emission', now()->month)->whereYear('date_emission', now()->year); }
    public function scopeASuivre($q)  { return $q->whereIn('type_facture', ['acompte', 'recurrente'])->whereIn('statut', ['envoye', 'partiellement_paye', 'en_retard']); }

    public function estPayee(): bool  { return $this->montant_du <= 0; }
    public function aUnSuivi(): bool  { return !$this->paiement_auto; }

    public function getPourcentagePaye(): float
    {
        if ($this->total_ttc <= 0) return 0;
        return min(100, round(($this->montant_paye / $this->total_ttc) * 100, 1));
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type_facture) {
            'simple'     => 'Facture',
            'proforma'   => 'Pro forma',
            'avoir'      => 'Avoir',
            'acompte'    => 'Acompte',
            'solde'      => 'Solde',
            'recurrente' => 'Récurrente',
            default      => ucfirst($this->type_facture),
        };
    }

    public function recalculerMontants(): void
    {
        $totalPaye = $this->paiements()->sum('montant');
        $montantDu = max(0, $this->total_ttc - $totalPaye);
        $statut    = $this->statut;

        if ($montantDu <= 0)       $statut = 'paye';
        elseif ($totalPaye > 0)    $statut = 'partiellement_paye';

        $this->update([
            'montant_paye' => $totalPaye,
            'montant_du'   => $montantDu,
            'statut'       => $statut,
        ]);
    }
}