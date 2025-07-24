<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\CurrencyHelper;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'ticket_type_id',
        'quantity',
        'unit_price',
        'total_price',
        'commission_rate',
        'commission_amount',
        'net_amount'
    ];

    protected $casts = [
        'unit_price' => 'integer',
        'total_price' => 'integer',
        'commission_amount' => 'integer',
        'net_amount' => 'integer',
        'commission_rate' => 'decimal:2',
    ];

    /**
     * Relations
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function ticketType()
    {
        return $this->belongsTo(TicketType::class);
    }

    /**
     * Accessors - Formatage FCFA
     */
    public function getFormattedUnitPriceAttribute()
    {
        return CurrencyHelper::formatFCFA($this->unit_price);
    }

    public function getFormattedTotalPriceAttribute()
    {
        return CurrencyHelper::formatFCFA($this->total_price);
    }

    public function getFormattedCommissionAmountAttribute()
    {
        return CurrencyHelper::formatFCFA($this->commission_amount);
    }

    public function getFormattedNetAmountAttribute()
    {
        return CurrencyHelper::formatFCFA($this->net_amount);
    }

    /**
     * Calculer automatiquement le prix total
     */
    public function calculateTotalPrice()
    {
        $this->total_price = $this->unit_price * $this->quantity;
        return $this->total_price;
    }

    /**
     * Calculer la commission pour cet item
     */
    public function calculateCommission($commissionRate, $platformFeePerTicket = 0)
    {
        $this->commission_rate = $commissionRate;
        
        $totalPlatformFee = $platformFeePerTicket * $this->quantity;
        $commissionCalc = CurrencyHelper::calculateCommission(
            $this->total_price, 
            $commissionRate, 
            $totalPlatformFee
        );
        
        $this->commission_amount = $commissionCalc['commission'];
        $this->net_amount = $commissionCalc['net'];
        
        return $this;
    }

    /**
     * Obtenir le nom du type de billet
     */
    public function getTicketTypeNameAttribute()
    {
        return $this->ticketType ? $this->ticketType->name : 'Type supprimé';
    }

    /**
     * Obtenir l'événement via le type de billet
     */
    public function getEventAttribute()
    {
        return $this->ticketType ? $this->ticketType->event : null;
    }

    /**
     * Créer automatiquement un OrderItem à partir d'un panier
     */
    public static function createFromCart($orderId, $ticketTypeId, $quantity)
    {
        $ticketType = TicketType::findOrFail($ticketTypeId);
        
        // Vérifier la disponibilité
        if (!$ticketType->canPurchaseQuantity($quantity)) {
            throw new \Exception("Quantité non disponible pour {$ticketType->name}");
        }
        
        // Créer l'item
        $orderItem = new self([
            'order_id' => $orderId,
            'ticket_type_id' => $ticketTypeId,
            'quantity' => $quantity,
            'unit_price' => $ticketType->price,
        ]);
        
        $orderItem->calculateTotalPrice();
        $orderItem->save();
        
        return $orderItem;
    }

    /**
     * Générer les billets pour cet item de commande
     */
    public function generateTickets()
    {
        $tickets = [];
        
        for ($i = 0; $i < $this->quantity; $i++) {
            $ticket = Ticket::create([
                'ticket_type_id' => $this->ticket_type_id,
                'ticket_code' => Ticket::generateTicketCode(),
                'status' => 'available' // Sera changé à 'sold' après paiement
            ]);
            
            // Générer le QR code
            $ticket->generateQRCode();
            
            $tickets[] = $ticket;
        }
        
        return $tickets;
    }

    /**
     * Associer les billets à la commande
     */
    public function attachTicketsToOrder($tickets)
    {
        foreach ($tickets as $ticket) {
            // Créer l'association dans order_tickets
            \DB::table('order_tickets')->insert([
                'order_id' => $this->order_id,
                'ticket_id' => $ticket->id,
                'order_item_id' => $this->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Réserver le stock pour cet item
     */
    public function reserveStock()
    {
        return $this->ticketType->reserveTickets($this->quantity);
    }

    /**
     * Libérer le stock en cas d'annulation
     */
    public function releaseStock()
    {
        $this->ticketType->releaseTickets($this->quantity);
    }
}