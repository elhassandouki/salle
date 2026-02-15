<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class Abonne extends Model
{
    protected $fillable = [
        'uid',
        'nom', 
        'prenom', 
        'cin',
        'card_id',
        'telephone', 
        'email',
        'sexe',
        'date_naissance', 
        'lieu_naissance',
        'nationalite',
        'situation_familiale',
        'profession',
        'adresse', 
        'notes',
        'photo'
    ];

    protected $casts = [
        'date_naissance' => 'date',
    ];

    /**
     * Relation avec les abonnements
     */
    public function abonnements(): HasMany
    {
        return $this->hasMany(Abonnement::class)->orderBy('date_fin', 'desc');
    }

    public function assurances(): HasMany
    {
        return $this->hasMany(AbonneAssurance::class);
    }

    public function pointages(): HasMany
    {
        return $this->hasMany(Pointage::class);
    }

    public function paiements()
    {
        return $this->hasManyThrough(Paiement::class, Abonnement::class);
    }

    /**
     * Récupérer l'abonnement actif
     */
    public function getAbonnementActifAttribute()
    {
        return $this->abonnements()
            ->where('date_fin', '>=', Carbon::today())
            ->where('statut', 'actif')
            ->first();
    }

    /**
     * Vérifier si l'abonné est actif
     */
    public function getEstActifAttribute(): bool
    {
        return $this->abonnements()
            ->where('date_fin', '>=', Carbon::today())
            ->where('statut', 'actif')
            ->exists();
    }

    /**
     * Récupérer la date de fin d'abonnement
     */
    public function getDateFinAbonnementAttribute(): ?string
    {
        $abonnement = $this->abonnements()
            ->where('statut', 'actif')
            ->latest('date_fin')
            ->first();
        
        return $abonnement ? $abonnement->date_fin->format('d/m/Y') : null;
    }

    /**
     * Récupérer le type d'abonnement
     */
    public function getTypeAbonnementAttribute(): ?string
    {
        $abonnement = $this->abonnements()
            ->where('statut', 'actif')
            ->first();
        
        return $abonnement ? $abonnement->type_abonnement : null;
    }

    public function getFullNameAttribute()
    {
        return $this->nom . ' ' . $this->prenom;
    }
    
    /**
     * Nom complet (alias)
     */
    public function getNomCompletAttribute(): string
    {
        return $this->nom . ' ' . $this->prenom;
    }

    public function getAgeAttribute()
    {
        return $this->date_naissance ? Carbon::parse($this->date_naissance)->age : null;
    }

    /**
     * Scope pour les abonnés actifs
     */
    public function scopeActifs($query)
    {
        return $query->whereHas('abonnements', function($q) {
            $q->where('statut', 'actif')
              ->where('date_fin', '>=', Carbon::today());
        });
    }

    /**
     * Scope pour les abonnés inactifs (sans abonnement actif)
     */
    public function scopeInactifs($query)
    {
        return $query->whereDoesntHave('abonnements', function($q) {
            $q->where('statut', 'actif')
              ->where('date_fin', '>=', Carbon::today());
        });
    }

    /**
     * Scope pour les abonnés sans abonnement (alias pour inactifs)
     */
    public function scopeSansAbonnement($query)
    {
        return $query->inactifs();
    }

    /**
     * Scope pour les abonnés dont l'abonnement expire bientôt
     */
    public function scopeExpireBientot($query, $jours = 7)
    {
        return $query->whereHas('abonnements', function($q) use ($jours) {
            $q->where('date_fin', '>=', Carbon::today())
              ->where('date_fin', '<=', Carbon::today()->addDays($jours))
              ->where('statut', 'actif');
        });
    }
}