<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbonneAssurance extends Model
{
    protected $fillable = [
        'abonne_id', 'assurance_company_id', 'numero_contrat', 
        'date_debut', 'date_fin', 'plafond_annuel', 'montant_utilise',
        'statut', 'notes'
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'plafond_annuel' => 'decimal:2',
        'montant_utilise' => 'decimal:2'
    ];

    public function abonne(): BelongsTo
    {
        return $this->belongsTo(Abonne::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(AssuranceCompany::class, 'assurance_company_id');
    }

    public function reclamations(): HasMany
    {
        return $this->hasMany(ReclamationAssurance::class);
    }

    public function getSoldeAttribute()
    {
        return $this->plafond_annuel - $this->montant_utilise;
    }

    public function getPourcentageUtiliseAttribute()
    {
        if ($this->plafond_annuel == 0) return 0;
        return ($this->montant_utilise / $this->plafond_annuel) * 100;
    }

    public function getStatutCouleurAttribute()
    {
        return match($this->statut) {
            'actif' => 'success',
            'expiré' => 'danger',
            'resilie' => 'warning',
            default => 'secondary'
        };
    }

    public function getJoursRestantsAttribute()
    {
        if ($this->statut !== 'actif') {
            return 0;
        }

        $now = now();
        $dateFin = \Carbon\Carbon::parse($this->date_fin);
        
        if ($dateFin->lt($now)) {
            return 0;
        }
        
        return $now->diffInDays($dateFin);
    }

    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeExpires($query)
    {
        return $query->where('statut', 'expiré');
    }

    public function scopeExpirant($query, $jours = 30)
    {
        return $query->where('statut', 'actif')
            ->where('date_fin', '<=', now()->addDays($jours));
    }

    public function updateMontantUtilise()
    {
        $totalReclamations = $this->reclamations()
            ->where('statut', 'approuve')
            ->sum('montant_remboursable');
        
        $this->update(['montant_utilise' => $totalReclamations]);
    }
}