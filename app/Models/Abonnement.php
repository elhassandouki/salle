<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Abonnement extends Model
{
    protected $fillable = [
        'abonne_id', 'activite_id', 'type_abonnement', 
        'date_debut', 'date_fin', 'montant', 'statut', 'zk_sync'
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'montant' => 'decimal:2',
        'zk_sync' => 'boolean'
    ];

    public function abonne(): BelongsTo
    {
        return $this->belongsTo(Abonne::class);
    }

    public function activite(): BelongsTo
    {
        return $this->belongsTo(Activite::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    public function getJoursRestantsAttribute()
    {
        $now = now();
        $dateFin = \Carbon\Carbon::parse($this->date_fin);
        
        if ($dateFin->lt($now)) {
            return 0;
        }
        
        return $now->diffInDays($dateFin);
    }

    public function getStatutCouleurAttribute()
    {
        return match($this->statut) {
            'actif' => 'success',
            'expiré' => 'danger',
            'suspendu' => 'warning',
            default => 'secondary'
        };
    }

    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeExpires($query)
    {
        return $query->where('statut', 'expiré');
    }

    public function scopeExpirant($query, $jours = 7)
    {
        return $query->where('statut', 'actif')
            ->where('date_fin', '<=', now()->addDays($jours));
    }
}