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
        'promoteur_id',
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

    public function promoteur()
    {
        return $this->belongsTo(User::class, 'promoteur_id');
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
     * Accessors - Formatage FCFA
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

    /**
     * Vérifications d'état
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function isHeld()
    {
        return $this->status === 'held';
    }

    /**
     * Marquer comme payé
     */
    public function markAsPaid()
    {
        $this->status = 'paid';
        $this->paid_at = now();
        $this->save();
    }

    /**
     * Mettre en attente (en cas de remboursement)
     */
    public function hold()
    {
        $this->status = 'held';
        $this->save();
    }

    /**
     * Remettre en pending depuis held
     */
    public function release()
    {
        if ($this->isHeld()) {
            $this->status = 'pending';
            $this->save();
        }
    }

    /**
     * Calculer le montant réel à verser au promoteur
     */
    public function getPayoutAmount()
    {
        // Le montant net moins les frais de processeur de paiement
        return max(0, $this->net_amount - $this->payment_processor_fee);
    }

    /**
     * Obtenir le délai de paiement (en jours)
     */
    public function getDaysUntilPayout()
    {
        if ($this->isPaid()) {
            return 0;
        }
        
        // Par défaut, paiement 7 jours après la création
        $payoutDate = $this->created_at->addDays(7);
        return max(0, now()->diffInDays($payoutDate, false));
    }

    /**
     * Vérifier si la commission est prête à être payée
     */
    public function isReadyForPayout()
    {
        return $this->isPending() && 
               $this->getDaysUntilPayout() <= 0 &&
               $this->order->isPaid();
    }

    /**
     * Obtenir toutes les commissions prêtes à être payées
     */
    public static function readyForPayout()
    {
        return self::pending()
            ->whereHas('order', function ($query) {
                $query->where('payment_status', 'paid');
            })
            ->where('created_at', '<=', now()->subDays(7))
            ->get();
    }

    /**
     * Statistiques par promoteur
     */
    public static function getPromoteurStats($promoteurId, $startDate = null, $endDate = null)
    {
        $query = self::where('promoteur_id', $promoteurId);
        
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        
        return [
            'total_revenue' => $query->clone()->paid()->sum('net_amount'),
            'pending_revenue' => $query->clone()->pending()->sum('net_amount'),
            'held_revenue' => $query->clone()->held()->sum('net_amount'),
            'total_orders' => $query->clone()->count(),
            'average_commission_rate' => $query->clone()->avg('commission_rate'),
        ];
    }

    /**
     * Statistiques globales de la plateforme
     */
    public static function getPlatformStats($startDate = null, $endDate = null)
    {
        $query = self::query();
        
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        
        return [
            'total_platform_revenue' => $query->clone()->sum('commission_amount'),
            'total_promoteur_revenue' => $query->clone()->sum('net_amount'),
            'total_gross_revenue' => $query->clone()->sum('gross_amount'),
            'pending_payouts' => $query->clone()->pending()->sum('net_amount'),
            'total_transactions' => $query->clone()->count(),
        ];
    }
}