<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coach extends Model
{
    protected $fillable = [
        'nom', 'prenom', 'specialite', 'telephone', 'email',
        'salaire', 'date_embauche', 'statut'
    ];

    protected $casts = [
        'salaire' => 'decimal:2',
        'date_embauche' => 'date'
    ];

    public function activites(): HasMany
    {
        return $this->hasMany(Activite::class);
    }

    public function getActivitesActivesCountAttribute()
    {
        return $this->activites()->where('statut', 'actif')->count();
    }

    public function getFullNameAttribute()
    {
        return $this->nom . ' ' . $this->prenom;
    }

    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif');
    }
}