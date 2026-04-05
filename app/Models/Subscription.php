<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
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

    public function abonne(): BelongsTo
    {
        return $this->belongsTo(Abonne::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function activite(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class, 'subscription_id');
    }

    public function getJoursRestantsAttribute(): int
    {
        if (! $this->date_fin || $this->date_fin->isPast()) {
            return 0;
        }

        return now()->diffInDays($this->date_fin);
    }

    public function getStatutCouleurAttribute(): string
    {
        return match ($this->normalizeStatus($this->statut)) {
            'actif' => 'success',
            'expire' => 'danger',
            'suspendu' => 'warning',
            default => 'secondary',
        };
    }

    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif');
    }

    public function updateStatut(): void
    {
        if ($this->normalizeStatus($this->statut) === 'suspendu') {
            return;
        }

        $nouveauStatut = $this->date_fin && $this->date_fin->isPast() ? 'expire' : 'actif';

        if ($this->normalizeStatus($this->statut) !== $nouveauStatut) {
            $this->forceFill(['statut' => $nouveauStatut])->save();
        }
    }

    protected function normalizeStatus(?string $statut): string
    {
        return match ($statut) {
            'expire', 'expiré', 'expirأ©', 'expirط£آ©', 'expirط·آ£ط¢آ©' => 'expire',
            default => (string) $statut,
        };
    }
}
