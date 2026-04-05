<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbonneAssurance extends Model
{
    protected $table = 'subscriptions';

    protected $fillable = [
        'abonne_id',
        'service_id',
        'type_abonnement',
        'date_debut',
        'date_fin',
        'montant',
        'remise',
        'montant_total',
        'montant_paye',
        'reste',
        'statut',
        'auto_renew',
        'notes',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'montant' => 'decimal:2',
        'remise' => 'decimal:2',
        'montant_total' => 'decimal:2',
        'montant_paye' => 'decimal:2',
        'reste' => 'decimal:2',
        'auto_renew' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('assurance', function (Builder $query) {
            $query->whereHas('service', function (Builder $serviceQuery) {
                $serviceQuery->where('type', 'assurance');
            });
        });
    }

    public function abonne(): BelongsTo
    {
        return $this->belongsTo(Abonne::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(AssuranceCompany::class, 'service_id');
    }

    public function reclamations(): HasMany
    {
        return $this->hasMany(ReclamationAssurance::class, 'service_id', 'service_id')
            ->where('abonne_id', $this->abonne_id);
    }

    public function getNumeroContratAttribute(): string
    {
        return 'SUB-' . $this->id;
    }

    public function getPlafondAnnuelAttribute(): float
    {
        return (float) ($this->montant_total ?? $this->montant ?? 0);
    }

    public function getMontantUtiliseAttribute(): float
    {
        return (float) $this->reclamations()
            ->whereIn('statut', ['approuve', 'rembourse'])
            ->sum('montant_rembourse');
    }

    public function getSoldeAttribute(): float
    {
        return max(0, $this->plafond_annuel - $this->montant_utilise);
    }

    public function getPourcentageUtiliseAttribute(): float
    {
        if ($this->plafond_annuel == 0) {
            return 0;
        }

        return ($this->montant_utilise / $this->plafond_annuel) * 100;
    }

    public function getStatutCouleurAttribute(): string
    {
        return match ($this->normalizeStatus($this->statut)) {
            'actif' => 'success',
            'expire' => 'danger',
            'resilie' => 'warning',
            default => 'secondary',
        };
    }

    public function getJoursRestantsAttribute(): int
    {
        if ($this->normalizeStatus($this->statut) !== 'actif') {
            return 0;
        }

        if (! $this->date_fin || $this->date_fin->isPast()) {
            return 0;
        }

        return now()->diffInDays($this->date_fin);
    }

    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeExpires($query)
    {
        return $query->whereIn('statut', ['expire', 'expiré', 'expirأ©', 'expirط£آ©', 'expirط·آ£ط¢آ©']);
    }

    public function scopeExpirant($query, $jours = 30)
    {
        return $query->where('statut', 'actif')
            ->where('date_fin', '<=', now()->addDays($jours));
    }

    public function updateMontantUtilise(): void
    {
        $totalReclamations = $this->reclamations()
            ->where('statut', 'approuve')
            ->sum('montant_rembourse');

        $this->forceFill([
            'montant_paye' => $totalReclamations,
            'reste' => max(0, $this->plafond_annuel - $totalReclamations),
        ])->save();
    }

    protected function normalizeStatus(?string $statut): string
    {
        return match ($statut) {
            'expire', 'expiré', 'expirأ©', 'expirط£آ©', 'expirط·آ£ط¢آ©' => 'expire',
            default => (string) $statut,
        };
    }
}
