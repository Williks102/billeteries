<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
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
        'paid_at',
        'payment_reference',
        'order_number',
        'billing_email',
        'billing_phone',
        'guest_token',
        'expires_at',
        'admin_notes',
    ];

    protected $casts = [
        'total_amount' => 'integer', // Montant en FCFA
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
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

    public function scopePendingPayment($query)
    {
        return $query->whereIn('payment_status', ['pending', 'failed']);
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('payment_status', 'failed');

    }

    public function scopeProcessing($query)
    {
        return $query->where('payment_status', 'processing');
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

        // Taux inchangé : réglage promoteur > catégorie > défaut, sinon 10 %.
        $commissionRate = $commissionSetting ? $commissionSetting->commission_rate : 10.00;

        // Assiette = prix des billets uniquement (somme des lignes). Pour une ancienne commande dont
        // total_amount incluait 500 FCFA de frais historiques, ces frais ne sont donc pas commissionnés.
        $subtotal = $this->orderItems()->exists()
            ? (int) $this->orderItems()->sum('total_price')
            : (int) $this->total_amount;

        // Plus aucun frais fixe : la plateforme ne se rémunère que par la commission (platform_fee_fixed ignoré).
        $commissionCalc = CurrencyHelper::calculateCommission($subtotal, $commissionRate, 0);

        return [
            'gross_amount' => $subtotal,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionCalc['commission'],
            'net_amount' => $commissionCalc['net'],
            'platform_fee' => 0,
        ];
    }

    /**
     * Créer la commission après paiement
     */
    public function createCommission()
    {
        if ($this->total_amount == 0) {
        return;
    }
    
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
     * Marquer comme payé : délégué à OrderPaymentService (verrou, idempotence, billets,
     * commission unique, email après commit). Aucun autre chemin ne doit passer la commande à `paid`.
     */
    public function markAsPaid($paymentReference = null)
    {
        return app(\App\Services\OrderPaymentService::class)
            ->confirmPayment($this, $paymentReference !== null ? (string) $paymentReference : null);
    }

    /**
     * Annuler la commande (libère le stock, idempotent)
     */
    public function cancel(): bool
    {
        return $this->releaseStock();
    }

    /**
     * Libère le stock d'une commande pending/processing. Idempotent :
     * sans effet (false) si la commande est déjà payée, échouée ou remboursée.
     * Transaction + verrou sur la commande puis sur les types de billets (id croissant).
     */
    public function releaseStock(): bool
    {
        return DB::transaction(function () {
            $order = static::whereKey($this->getKey())->lockForUpdate()->first();

            if (!$order || !in_array($order->payment_status, ['pending', 'processing'], true)) {
                return false;
            }

            $items = $order->orderItems()->orderBy('ticket_type_id')->get();
            foreach ($items as $item) {
                $ticketType = TicketType::whereKey($item->ticket_type_id)->lockForUpdate()->first();
                $ticketType?->releaseTickets($item->quantity);
            }

            $ticketIds = DB::table('order_tickets')->where('order_id', $order->id)->pluck('ticket_id');
            Ticket::whereIn('id', $ticketIds)->where('status', 'reserved')->update(['status' => 'cancelled']);

            $order->update(['payment_status' => 'failed']);
            $this->payment_status = 'failed';

            return true;
        });
    }

    /**
     * Paiement tardif sur une commande libérée : re-réserve le stock si possible.
     * Doit être appelé sous verrou de la commande. Tout ou rien.
     */
    public function reclaimStock(): bool
    {
        if ($this->payment_status !== 'failed') {
            return false;
        }

        try {
            return DB::transaction(function () {
                foreach ($this->orderItems()->orderBy('ticket_type_id')->get() as $item) {
                    $ticketType = TicketType::findOrFail($item->ticket_type_id);
                    if (!$ticketType->reserveTickets($item->quantity)) {
                        throw new \RuntimeException('stock indisponible');
                    }
                }

                $ticketIds = DB::table('order_tickets')->where('order_id', $this->id)->pluck('ticket_id');
                Ticket::whereIn('id', $ticketIds)->where('status', 'cancelled')->update(['status' => 'reserved']);

                return true;
            });
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    /**
     * Commandes pending/processing dont la réservation a expiré
     */
    public function scopeExpiredReservations($query)
    {
        return $query->whereIn('payment_status', ['pending', 'processing'])
                     ->whereNotNull('expires_at')
                     ->where('expires_at', '<', now());
    }

    /**
     * Table centralisée des transitions de statut de paiement autorisées (M2).
     * Tout changement de statut (admin ou service) doit la respecter ; `paid` n'est atteint que
     * via OrderPaymentService::confirmPayment, `failed` via releaseStock, `refunded` via refund.
     * Interdits notamment : refunded -> *, paid -> pending|failed, failed -> pending.
     */
    public const STATUS_TRANSITIONS = [
        'pending' => ['paid', 'failed'],
        'processing' => ['paid', 'failed'],
        'failed' => ['paid'], // paiement tardif : reclaimStock sous verrou
        'paid' => ['refunded'],
        'refunded' => [],
    ];

    public static function canTransition(?string $from, string $to): bool
    {
        return in_array($to, self::STATUS_TRANSITIONS[$from] ?? [], true);
    }

    public function canTransitionTo(string $to): bool
    {
        return self::canTransition($this->payment_status, $to);
    }

    /**
     * Rembourser la commande (paid -> refunded) : verrou, billets annulés, stock libéré,
     * commission suspendue. Délégué à OrderPaymentService.
     */
    public function refund()
    {
        return app(\App\Services\OrderPaymentService::class)->refund($this);
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

    /**
     * NOUVELLE RELATION : Paiements liés à cette commande
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * NOUVEAU : Vérifier si la commande peut être payée
     */
    public function canBePaid(): bool
    {
        return in_array($this->payment_status, ['pending', 'failed']);
    }

    /**
     * NOUVEAU : Marquer paiement comme échoué
     */
    public function markPaymentAsFailed(): void
    {
        $this->releaseStock();
    }

    /**
     * AMÉLIORATION : Status avec plus de détails
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return match($this->payment_status) {
            'pending' => 'En attente',
            'processing' => 'En cours de traitement',
            'paid' => 'Payé',
            'failed' => 'Échec',
            'cancelled' => 'Annulé',
            'refunded' => 'Remboursé',
            default => 'Inconnu'
        };
    }

    /**
     * AMÉLIORATION : Icône pour le statut
     */
    public function getPaymentStatusIconAttribute(): string
    {
        return match($this->payment_status) {
            'pending' => 'fas fa-clock text-warning',
            'processing' => 'fas fa-spinner fa-spin text-info',
            'paid' => 'fas fa-check-circle text-success',
            'failed' => 'fas fa-times-circle text-danger',
            'cancelled' => 'fas fa-ban text-secondary',
            'refunded' => 'fas fa-undo text-warning',
            default => 'fas fa-question-circle text-muted'
        };
    }

    /**
     * NOUVEAU : Méthode pour obtenir l'URL de paiement selon le statut
     */
    public function getPaymentActionUrl(): ?string
    {
        if ($this->canBePaid()) {
            return route('checkout.payment-method', $this);
        }

        if ($this->payment_status === 'paid') {
            return route('orders.show', $this);
        }

        return null;
    }

    /**
     * NOUVEAU : Texte du bouton d'action selon le statut
     */
    public function getPaymentActionText(): ?string
    {
        return match($this->payment_status) {
            'pending' => 'Payer maintenant',
            'failed' => 'Réessayer le paiement',
            'paid' => 'Voir la commande',
            'processing' => 'Paiement en cours...',
            default => null
        };
    }

    /**
     * N4 : une commande créée en invité (guest_token) et rattachée à un compte par email n'apparaît
     * dans l'historique de l'acheteur qu'une fois payée (un tiers ne peut pas polluer l'historique d'autrui).
     */
    public function scopeVisibleInBuyerHistory($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('guest_token')->orWhere('payment_status', 'paid');
        });
    }
}
