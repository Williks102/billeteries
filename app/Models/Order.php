<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\CurrencyHelper;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_id',
        'total_amount',
        'payment_status',
        'payment_method',
        'payment_reference',
        'order_number',
        'billing_email',
        'billing_phone'
    ];

    protected $casts = [
        'total_amount' => 'integer', // Montant en FCFA
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function tickets()
    {
        return $this->belongsToMany(Ticket::class, 'order_tickets');
    }

    public function commission()
    {
        return $this->hasOne(Commission::class);
    }

    

    /**
     * Scopes
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('payment_status', 'failed');
    }

    /**
     * Accessors - Formatage
     */
    public function getFormattedTotalAttribute()
    {
        return CurrencyHelper::formatFCFA($this->total_amount);
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    /**
     * Vérifications d'état
     */
    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    public function isPending()
    {
        return $this->payment_status === 'pending';
    }

    public function isFailed()
    {
        return $this->payment_status === 'failed';
    }

    public function isRefunded()
    {
        return $this->payment_status === 'refunded';
    }

    /**
     * Générer un numéro de commande unique
     */
    public static function generateOrderNumber()
    {
        do {
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (self::where('order_number', $orderNumber)->exists());
        
        return $orderNumber;
    }

    /**
     * Calculer le total de la commande
     */
    public function calculateTotal()
    {
        return $this->orderItems()->sum('total_price');
    }

    /**
     * Calculer les commissions pour cette commande
     */
    public function calculateCommission()
    {
        $event = $this->event;
        $commissionSetting = CommissionSetting::getCommissionForEvent($event, $event->promoter_id);
        
        if (!$commissionSetting) {
            // Commission par défaut : 10% + 500 FCFA
            $commissionRate = 10.00;
            $platformFee = 500;
        } else {
            $commissionRate = $commissionSetting->commission_rate;
            $platformFee = $commissionSetting->platform_fee_fixed ?? 500;
        }
        
        $commissionCalc = CurrencyHelper::calculateCommission(
            $this->total_amount, 
            $commissionRate, 
            $platformFee
        );
        
        return [
            'gross_amount' => $this->total_amount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionCalc['commission'],
            'net_amount' => $commissionCalc['net'],
            'platform_fee' => $platformFee,
        ];
    }

    /**
     * Créer la commission après paiement
     */
    public function createCommission()
    {
        if ($this->isPaid() && !$this->commission) {
            $commissionData = $this->calculateCommission();
            
            Commission::create([
                'order_id' => $this->id,
                'promoter_id' => $this->event->promoter_id,
                'gross_amount' => $commissionData['gross_amount'],
                'commission_rate' => $commissionData['commission_rate'],
                'commission_amount' => $commissionData['commission_amount'],
                'net_amount' => $commissionData['net_amount'],
                'platform_fee' => $commissionData['platform_fee'],
                'status' => 'pending'
            ]);
        }
    }

    /**
     * Marquer comme payé
     */
    public function markAsPaid($paymentReference = null)
    {
        $this->payment_status = 'paid';
        if ($paymentReference) {
            $this->payment_reference = $paymentReference;
        }
        $this->save();
        
        // Marquer les billets comme vendus
        $this->tickets()->update(['status' => 'sold']);
        
        // Créer la commission
        $this->createCommission();

        // Déclencher l'email de confirmation paiement
        $emailService = app(\App\Services\EmailService::class);
        $emailService->sendPaymentConfirmation($this);
        
        return $this;
    }

    /**
     * Annuler la commande
     */
    public function cancel()
    {
        $this->payment_status = 'failed';
        $this->save();
        
        // Remettre les billets disponibles
        $this->tickets()->update(['status' => 'available']);
        
        // Libérer le stock
        foreach ($this->orderItems as $item) {
            $item->ticketType->releaseTickets($item->quantity);
        }
    }

    /**
     * Rembourser la commande
     */
    public function refund()
    {
        $this->payment_status = 'refunded';
        $this->save();
        
        // Annuler les billets
        $this->tickets()->update(['status' => 'cancelled']);
        
        // Mettre à jour la commission
        if ($this->commission) {
            $this->commission->update(['status' => 'held']);
        }
    }

    /**
     * Obtenir le nombre total de billets
     */
    public function getTotalTicketsAttribute()
    {
        return $this->orderItems()->sum('quantity');
    }

    /**
     * Vérifier si la commande peut être téléchargée (PDF)
     */
    public function canDownloadTickets()
    {
        return $this->isPaid() && $this->tickets()->count() > 0;
    }
}