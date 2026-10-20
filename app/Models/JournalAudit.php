<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class JournalAudit extends Model
{
    protected $table='journal_audit';
    public $timestamps=false;
    protected $fillable=['entreprise_id','utilisateur_id','type_entite','entite_id','action','source','anciennes_valeurs','nouvelles_valeurs','adresse_ip','agent_utilisateur','cree_le'];
    protected $casts=['anciennes_valeurs'=>'array','nouvelles_valeurs'=>'array','cree_le'=>'datetime'];
    public function entreprise()  { return $this->belongsTo(Entreprise::class,'entreprise_id'); }
    public function utilisateur() { return $this->belongsTo(Utilisateur::class,'utilisateur_id'); }
    public static function enregistrer(string $typeEntite,int $entiteId,string $action,array $anciennes=[],array $nouvelles=[],string $source='web'): void {
        static::create(['entreprise_id'=>auth()->user()?->entreprise_id,'utilisateur_id'=>auth()->id(),'type_entite'=>$typeEntite,'entite_id'=>$entiteId,'action'=>$action,'source'=>$source,'anciennes_valeurs'=>$anciennes?:null,'nouvelles_valeurs'=>$nouvelles?:null,'adresse_ip'=>request()->ip(),'agent_utilisateur'=>request()->userAgent(),'cree_le'=>now()]);
    }
}
