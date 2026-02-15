<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pointage extends Model
{
    protected $fillable = [
        'abonne_id', 'uid', 'date_pointage', 'type', 'synced'
    ];

    protected $casts = [
        'date_pointage' => 'datetime',
        'synced' => 'boolean'
    ];

    public function abonne(): BelongsTo
    {
        return $this->belongsTo(Abonne::class);
    }

    public function getTypeTextAttribute()
    {
        return match($this->type) {
            'entree' => 'Entrée',
            'sortie' => 'Sortie',
            default => $this->type
        };
    }

    public function getCouleurTypeAttribute()
    {
        return match($this->type) {
            'entree' => 'success',
            'sortie' => 'danger',
            default => 'secondary'
        };
    }

    public function getHeureAttribute()
    {
        return $this->date_pointage->format('H:i');
    }

    public function getDateOnlyAttribute()
    {
        return $this->date_pointage->format('d/m/Y');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('date_pointage', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('date_pointage', now()->month)
            ->whereYear('date_pointage', now()->year);
    }

    public function scopeEntrees($query)
    {
        return $query->where('type', 'entree');
    }

    public function scopeSorties($query)
    {
        return $query->where('type', 'sortie');
    }

    public function scopeNotSynced($query)
    {
        return $query->where('synced', false);
    }
}