<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\CurrencyHelper;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'promoteur_id',  // GARDER le nom de votre table existante
        'gross_amount',
        'commission_rate',
        'commission_amount',
        'net_amount',
        'platform_fee',
        'payment_processor_fee',
        'status',
        'paid_at',
        'notes'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'commission_rate' => 'decimal:2',
        'gross_amount' => 'integer',
        'commission_amount' => 'integer',
        'net_amount' => 'integer',
        'platform_fee' => 'integer',
        'payment_processor_fee' => 'integer',
    ];

    /**
     * Relations
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relation principale avec le promoteur (utilise promoteur_id)
     */
    public function promoteur()
    {
        return $this->belongsTo(User::class, 'promoteur_id');
    }

    /**
     * Alias pour compatibilité avec le code utilisant 'promoter'
     */
    public function promoter()
    {
        return $this->promoteur();
    }

    /**
     * Relation avec l'événement via la commande
     */
    public function event()
    {
        return $this->hasOneThrough(Event::class, Order::class, 'id', 'id', 'order_id', 'event_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeHeld($query)
    {
        return $query->where('status', 'held');
    }

    public function scopeForPeriod($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    public function scopeForPromoter($query, $promoterId)
    {
        return $query->where('promoteur_id', $promoterId);
    }

    public function scopeCurrentMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    /**
     * Accessors - Formatage FCFA (si CurrencyHelper existe)
     */
    public function getFormattedGrossAmountAttribute()
    {
        if (class_exists('App\Helpers\CurrencyHelper')) {
            return CurrencyHelper::formatFCFA($this->gross_amount);
        }
        return number_format($this->gross_amount, 0, ',', ' ') . ' FCFA';
    }

    public function getFormattedCommissionAmountAttribute()
    {
        if (class_exists('App\Helpers\CurrencyHelper')) {
            return CurrencyHelper::formatFCFA($this->commission_amount);
        }
        return number_format($this->commission_amount, 0, ',', ' ') . ' FCFA';
    }

    public function getFormattedNetAmountAttribute()
    {
        if (class_exists('App\Helpers\CurrencyHelper')) {
            return CurrencyHelper::formatFCFA($this->net_amount);
        }
        return number_format($this->net_amount, 0, ',', ' ') . ' FCFA';
    }

    public function getFormattedPlatformFeeAttribute()
    {
        if (class_exists('App\Helpers\CurrencyHelper')) {
            return CurrencyHelper::formatFCFA($this->platform_fee);
        }
        return number_format($this->platform_fee, 0, ',', ' ') . ' FCFA';
    }

    public function getFormattedCommissionRateAttribute()
    {
        return $this->commission_rate . '%';
    }

    /**
     * Mutators
     */
    public function setCommissionRateAttribute($value)
    {
        // Permet de passer 15 au lieu de 0.15 pour 15%
        $this->attributes['commission_rate'] = is_numeric($value) && $value > 1 ? $value : $value * 100;
    }

    /**
     * Méthodes utiles
     */
    
    /**
     * Marquer comme payée
     */
    public function markAsPaid()
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);
    }

    /**
     * Mettre en attente
     */
    public function markAsHeld($reason = null)
    {
        $this->update([
            'status' => 'held',
            'notes' => $reason
        ]);
    }

    /**
     * Calculer automatiquement les montants
     */
    public static function calculateAmounts($grossAmount, $commissionRate, $platformFee = 0)
    {
        $commissionAmount = ($grossAmount * $commissionRate) / 100;
        $netAmount = $grossAmount - $commissionAmount - $platformFee;

        return [
            'gross_amount' => $grossAmount,
            'commission_amount' => $commissionAmount,
            'net_amount' => max(0, $netAmount), // Éviter les montants négatifs
            'platform_fee' => $platformFee
        ];
    }

    /**
     * Vérifier si la commission peut être payée
     */
    public function canBePaid()
    {
        return in_array($this->status, ['pending']) && $this->order && $this->order->isPaid();
    }

    /**
     * Obtenir le pourcentage de commission réel
     */
    public function getActualCommissionRate()
    {
        if ($this->gross_amount > 0) {
            return round(($this->commission_amount / $this->gross_amount) * 100, 2);
        }
        return 0;
    }
}