<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssuranceCompany extends Model
{
    protected $fillable = [
        'nom', 'telephone', 'email', 'taux_couverture', 'delai_remboursement'
    ];

    protected $casts = [
        'taux_couverture' => 'decimal:2'
    ];

    public function abonneAssurances(): HasMany
    {
        return $this->hasMany(AbonneAssurance::class);
    }

    public function getTauxCouverturePourcentageAttribute()
    {
        return $this->taux_couverture . '%';
    }

    public function getDelaiRemboursementTexteAttribute()
    {
        return $this->delai_remboursement . ' jours';
    }

    public function getAssuresActifsCountAttribute()
    {
        return $this->abonneAssurances()->where('statut', 'actif')->count();
    }

    public function getTotalReclamationsAttribute()
    {
        return $this->abonneAssurances()->with('reclamations')->get()
            ->sum(function($abonneAssurance) {
                return $abonneAssurance->reclamations->count();
            });
    }

    public function getTotalRemboursementsAttribute()
    {
        return $this->abonneAssurances()->with('reclamations')->get()
            ->sum(function($abonneAssurance) {
                return $abonneAssurance->reclamations->sum('montant_remboursable');
            });
    }

    public function scopeActive($query)
    {
        return $query->whereHas('abonneAssurances', function($q) {
            $q->where('statut', 'actif');
        });
    }
}