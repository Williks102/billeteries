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
        'promoter_id',  // ✅ CORRIGÉ: promoter_id (correspond à votre table)
        'gross_amount',
        'commission_rate',
        'commission_amount',
        'net_amount',
        'platform_fee',
        'payment_processor_fee',
        'status',
        'paid_at'
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
     * Relation avec promoteur (utilise promoter_id)
     */
    public function promoter()
    {
        return $this->belongsTo(User::class, 'promoter_id');
    }

    /**
     * Alias pour compatibilité avec le code utilisant 'promoteur'
     */
    public function promoteur()
    {
        return $this->promoter();
    }

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

    /**
     * Accessors - Formatages FCFA
     */
    public function getFormattedGrossAmountAttribute()
    {
        return CurrencyHelper::formatFCFA($this->gross_amount);
    }

    public function getFormattedCommissionAmountAttribute()
    {
        return CurrencyHelper::formatFCFA($this->commission_amount);
    }

    public function getFormattedNetAmountAttribute()
    {
        return CurrencyHelper::formatFCFA($this->net_amount);
    }

    public function getFormattedPlatformFeeAttribute()
    {
        return CurrencyHelper::formatFCFA($this->platform_fee);
    }

    public function getFormattedCommissionRateAttribute()
    {
        return $this->commission_rate . '%';
    }
}