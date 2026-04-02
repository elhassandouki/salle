<?php
// This file was renamed to Subscription.php. Please use App\Models\Subscription instead.

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'abonne_id', 'service_id', 'type_abonnement', 
        'date_debut', 'date_fin', 'montant', 'remise', 'montant_total', 'montant_paye', 'reste', 'statut', 'auto_renew', 'notes'
    ];
    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'montant' => 'decimal:2',
        'remise' => 'decimal:2',
        'montant_total' => 'decimal:2',
        'montant_paye' => 'decimal:2',
        'reste' => 'decimal:2',
        'auto_renew' => 'boolean'
    ];
    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class, 'subscription_id');
    }

    public function abonne(): BelongsTo
    {
        return $this->belongsTo(Abonne::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function getStatutCouleurAttribute()
    {
        return match($this->statut) {
            'actif' => 'success',
            'expiré' => 'danger',
            'suspendu' => 'warning',
            default => 'secondary'
        };
    }

    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeExpires($query)
    {
        return $query->where('statut', 'expiré');
    }

    public function scopeExpirant($query, $jours = 7)
    {
        return $query->where('statut', 'actif')
            ->where('date_fin', '<=', now()->addDays($jours));
    }
}