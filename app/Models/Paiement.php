<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    protected $fillable = [
        'subscription_id', 'montant', 'mode_paiement', 
        'date_paiement', 'reference', 'imprimante_id', 'notes'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_paiement' => 'datetime'
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function getModePaiementTextAttribute()
    {
        return match($this->mode_paiement) {
            'especes' => 'Espèces',
            'carte' => 'Carte bancaire',
            'cheque' => 'Chèque',
            'virement' => 'Virement',
            default => $this->mode_paiement
        };
    }

    public function getCouleurModeAttribute()
    {
        return match($this->mode_paiement) {
            'especes' => 'success',
            'carte' => 'info',
            'cheque' => 'warning',
            'virement' => 'primary',
            default => 'secondary'
        };
    }

    public function scopeToday($query)
    {
        return $query->whereDate('date_paiement', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('date_paiement', now()->month)
            ->whereYear('date_paiement', now()->year);
    }

    public function scopeBetweenDates($query, $start, $end)
    {
        return $query->whereBetween('date_paiement', [$start, $end]);
    }
}
