<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssuranceCompany extends Model
{
    protected $table = 'services';

    protected $fillable = [
        'nom', 'description', 'statut', 'type', 'couleur',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('assurance', function (Builder $query) {
            $query->where('type', 'assurance');
        });
    }

    public function abonneAssurances(): HasMany
    {
        return $this->hasMany(AbonneAssurance::class, 'service_id');
    }

    public function getTauxCouvertureAttribute(): float
    {
        return 100.0;
    }

    public function getDelaiRemboursementAttribute(): int
    {
        return 0;
    }

    public function getTelephoneAttribute(): ?string
    {
        return null;
    }

    public function getEmailAttribute(): ?string
    {
        return null;
    }

    public function getTauxCouverturePourcentageAttribute(): string
    {
        return $this->taux_couverture . '%';
    }

    public function getDelaiRemboursementTexteAttribute(): string
    {
        return $this->delai_remboursement . ' jours';
    }

    public function getAssuresActifsCountAttribute(): int
    {
        return $this->abonneAssurances()->where('statut', 'actif')->count();
    }

    public function getTotalReclamationsAttribute(): int
    {
        return $this->abonneAssurances()->with('reclamations')->get()
            ->sum(fn ($abonneAssurance) => $abonneAssurance->reclamations->count());
    }

    public function getTotalRemboursementsAttribute(): float
    {
        return (float) $this->abonneAssurances()->with('reclamations')->get()
            ->sum(fn ($abonneAssurance) => $abonneAssurance->reclamations->sum('montant_rembourse'));
    }

    public function scopeActive($query)
    {
        return $query->where('statut', 'actif');
    }
}
