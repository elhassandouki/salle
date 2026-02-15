<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ReclamationAssurance extends Model
{
    protected $fillable = [
        'abonne_assurance_id', 'type', 'montant_total', 'montant_remboursable',
        'date_reclamation', 'date_traitement', 'statut', 'justificatif_path', 'notes'
    ];

    protected $casts = [
        'montant_total' => 'decimal:2',
        'montant_remboursable' => 'decimal:2',
        'date_reclamation' => 'date',
        'date_traitement' => 'date'
    ];

    public function abonneAssurance(): BelongsTo
    {
        return $this->belongsTo(AbonneAssurance::class);
    }

    public function getTypeTextAttribute()
    {
        return match($this->type) {
            'consultation' => 'Consultation',
            'examen' => 'Examen médical',
            'medicament' => 'Médicament',
            'rehabilitation' => 'Réhabilitation',
            default => $this->type
        };
    }

    public function getStatutTextAttribute()
    {
        return match($this->statut) {
            'en_attente' => 'En attente',
            'approuve' => 'Approuvé',
            'refuse' => 'Refusé',
            'rembourse' => 'Remboursé',
            default => $this->statut
        };
    }

    public function getStatutCouleurAttribute()
    {
        return match($this->statut) {
            'en_attente' => 'warning',
            'approuve' => 'info',
            'refuse' => 'danger',
            'rembourse' => 'success',
            default => 'secondary'
        };
    }

    public function getPourcentageRemboursementAttribute()
    {
        if ($this->montant_total == 0) return 0;
        return ($this->montant_remboursable / $this->montant_total) * 100;
    }

    public function getJustificatifUrlAttribute()
    {
        if (!$this->justificatif_path) {
            return null;
        }
        return Storage::url($this->justificatif_path);
    }

    public function getDelaiTraitementAttribute()
    {
        if (!$this->date_traitement) {
            return null;
        }
        return \Carbon\Carbon::parse($this->date_reclamation)
            ->diffInDays($this->date_traitement);
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeApprouvees($query)
    {
        return $query->where('statut', 'approuve');
    }

    public function scopeRemboursees($query)
    {
        return $query->where('statut', 'rembourse');
    }

    public function scopeRefusees($query)
    {
        return $query->where('statut', 'refuse');
    }

    public function scopeBetweenDates($query, $start, $end)
    {
        return $query->whereBetween('date_reclamation', [$start, $end]);
    }
}