<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ConfigRecurrence extends Model
{
    protected $table = 'configs_recurrence';

    protected $fillable = [
        'entreprise_id','client_id','nom','frequence','intervalle',
        'date_debut','date_fin','max_occurrences','total_genere',
        'prochaine_generation_le','jour_du_mois','envoi_auto','devise','statut','cree_par',
    ];

    protected $casts = [
        'date_debut'               => 'date',
        'date_fin'                 => 'date',
        'prochaine_generation_le'  => 'date',
        'envoi_auto'               => 'boolean',
    ];

    public function entreprise() { return $this->belongsTo(Entreprise::class, 'entreprise_id'); }
    public function client()     { return $this->belongsTo(Client::class, 'client_id'); }
    public function factures()   { return $this->hasMany(Facture::class, 'config_recurrence_id'); }

    public function calculerProchaineDate(): \Carbon\Carbon
    {
        $base = ($this->prochaine_generation_le ?? now())->copy();
        return match ($this->frequence) {
            'quotidienne'   => $base->addDays($this->intervalle),
            'hebdomadaire'  => $base->addWeeks($this->intervalle),
            'mensuelle'     => $base->addMonths($this->intervalle),
            'trimestrielle' => $base->addMonths(3 * $this->intervalle),
            'semestrielle'  => $base->addMonths(6 * $this->intervalle),
            'annuelle'      => $base->addYears($this->intervalle),
            default         => $base->addMonths($this->intervalle),
        };
    }

    public function estTerminee(): bool
    {
        return ($this->date_fin && now()->gt($this->date_fin))
            || ($this->max_occurrences && $this->total_genere >= $this->max_occurrences);
    }
}