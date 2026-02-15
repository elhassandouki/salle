<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activite extends Model
{
    protected $fillable = [
        'nom', 'description', 'coach_id', 'prix_mensuel', 
        'prix_trimestriel', 'prix_annuel', 'capacite_max', 
        'couleur', 'statut'
    ];

    protected $casts = [
        'prix_mensuel' => 'decimal:2',
        'prix_trimestriel' => 'decimal:2',
        'prix_annuel' => 'decimal:2',
        'capacite_max' => 'integer'
    ];

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    public function abonnements(): HasMany
    {
        return $this->hasMany(Abonnement::class);
    }

    public function getAbonnesActifsCountAttribute()
    {
        return $this->abonnements()->where('statut', 'actif')->count();
    }

    public function getDisponibiliteAttribute()
    {
        return max(0, $this->capacite_max - $this->abonnes_actifs_count);
    }

    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif');
    }

    public function getPrixAttribute()
    {
        return [
            'mensuel' => $this->prix_mensuel,
            'trimestriel' => $this->prix_trimestriel,
            'annuel' => $this->prix_annuel
        ];
    }
}