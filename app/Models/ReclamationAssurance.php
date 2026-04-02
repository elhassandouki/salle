<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ReclamationAssurance extends Model
{
    protected $table = 'reclamations';

    protected $fillable = [
        'abonne_id',
        'service_id',
        'type',
        'montant_total',
        'montant_rembourse',
        'date_reclamation',
        'date_traitement',
        'statut',
        'justificatif_path',
        'notes',
    ];

    protected $casts = [
        'montant_total' => 'decimal:2',
        'montant_rembourse' => 'decimal:2',
        'date_reclamation' => 'date',
        'date_traitement' => 'date',
    ];

    public function abonne(): BelongsTo
    {
        return $this->belongsTo(Abonne::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(AssuranceCompany::class, 'service_id');
    }

    public function getMontantRemboursableAttribute(): float
    {
        return (float) ($this->montant_rembourse ?? 0);
    }

    public function getTypeTextAttribute(): string
    {
        return match ($this->type) {
            'consultation' => 'Consultation',
            'examen' => 'Examen medical',
            'medicament' => 'Medicament',
            'rehabilitation' => 'Rehabilitation',
            default => (string) $this->type,
        };
    }

    public function getStatutTextAttribute(): string
    {
        return match ($this->statut) {
            'en_attente' => 'En attente',
            'approuve' => 'Approuve',
            'refuse' => 'Refuse',
            'rembourse' => 'Rembourse',
            default => (string) $this->statut,
        };
    }

    public function getStatutCouleurAttribute(): string
    {
        return match ($this->statut) {
            'en_attente' => 'warning',
            'approuve' => 'info',
            'refuse' => 'danger',
            'rembourse' => 'success',
            default => 'secondary',
        };
    }

    public function getPourcentageRemboursementAttribute(): float
    {
        if ((float) $this->montant_total === 0.0) {
            return 0;
        }

        return ((float) $this->montant_rembourse / (float) $this->montant_total) * 100;
    }

    public function getJustificatifUrlAttribute(): ?string
    {
        if (! $this->justificatif_path) {
            return null;
        }

        return Storage::url($this->justificatif_path);
    }

    public function getDelaiTraitementAttribute(): ?int
    {
        if (! $this->date_traitement || ! $this->date_reclamation) {
            return null;
        }

        return $this->date_reclamation->diffInDays($this->date_traitement);
    }
}
