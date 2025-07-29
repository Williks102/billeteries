<?php
// app/Models/Commission.php - Version corrigée

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'promoter_id',  // CORRECTION: nom de colonne standardisé
        'gross_amount',
        'commission_rate',
        'commission_amount',
        'net_amount',
        'status',
        'paid_at',
        'notes'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'gross_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_amount' => 'decimal:2'
    ];

    /**
     * SOLUTION: Relation avec le promoteur
     * Utilise 'promoter_id' comme clé étrangère
     */
    public function promoter()
    {
        return $this->belongsTo(User::class, 'promoter_id');
    }

    /**
     * Alias pour compatibilité (si certains endroits utilisent 'promoteur')
     */
    public function promoteur()
    {
        return $this->promoter();
    }

    /**
     * Relation avec la commande
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scopes pour filtrer
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeForPeriod($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    public function scopeForPromoter($query, $promoterId)
    {
        return $query->where('promoter_id', $promoterId);
    }

    /**
     * Accesseurs formatés
     */
    public function getFormattedGrossAmountAttribute()
    {
        return number_format($this->gross_amount, 0, ',', ' ') . ' FCFA';
    }

    public function getFormattedCommissionAmountAttribute()
    {
        return number_format($this->commission_amount, 0, ',', ' ') . ' FCFA';
    }

    public function getFormattedNetAmountAttribute()
    {
        return number_format($this->net_amount, 0, ',', ' ') . ' FCFA';
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'pending' => '<span class="badge bg-warning">En attente</span>',
            'paid' => '<span class="badge bg-success">Payée</span>',
            'cancelled' => '<span class="badge bg-danger">Annulée</span>',
            'held' => '<span class="badge bg-secondary">Suspendue</span>',
            default => '<span class="badge bg-secondary">Inconnu</span>'
        };
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'pending' => 'En attente',
            'paid' => 'Payée',
            'cancelled' => 'Annulée',
            'held' => 'Suspendue',
            default => 'Inconnu'
        };
    }

    /**
     * Méthodes utilitaires
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function isHeld()
    {
        return $this->status === 'held';
    }

    /**
     * Actions sur la commission
     */
    public function markAsPaid()
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);
    }

    public function markAsHeld($reason = null)
    {
        $this->update([
            'status' => 'held',
            'notes' => $reason
        ]);
    }

    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'notes' => $reason
        ]);
    }

    /**
     * Calculs automatiques
     */
    public static function calculateCommission($grossAmount, $commissionRate)
    {
        $commissionAmount = ($grossAmount * $commissionRate) / 100;
        $netAmount = $grossAmount - $commissionAmount;

        return [
            'commission_amount' => round($commissionAmount, 2),
            'net_amount' => round($netAmount, 2)
        ];
    }

    /**
     * Créer une commission pour une commande
     */
    public static function createForOrder(Order $order, $commissionRate = 10)
    {
        // Vérifier si une commission existe déjà
        $existingCommission = self::where('order_id', $order->id)->first();
        if ($existingCommission) {
            return $existingCommission;
        }

        $calculation = self::calculateCommission($order->total_amount, $commissionRate);

        return self::create([
            'order_id' => $order->id,
            'promoter_id' => $order->event->promoteur_id, // Adapter selon votre structure
            'gross_amount' => $order->total_amount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $calculation['commission_amount'],
            'net_amount' => $calculation['net_amount'],
            'status' => 'pending'
        ]);
    }

    /**
     * Statistiques par promoteur
     */
    public static function getStatsForPromoter($promoterId, $period = null)
    {
        $query = self::where('promoter_id', $promoterId);
        
        if ($period) {
            $dateRange = self::getDateRangeForPeriod($period);
            $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        }

        return [
            'total' => $query->sum('gross_amount'),
            'commissions' => $query->sum('commission_amount'),
            'net' => $query->sum('net_amount'),
            'pending' => $query->where('status', 'pending')->sum('net_amount'),
            'paid' => $query->where('status', 'paid')->sum('net_amount'),
            'count' => $query->count()
        ];
    }

   /**
 * Obtenir les statistiques globales (méthode statique)
 */
public static function getGlobalStats($period = null)
{
    $query = self::query();
    
    if ($period) {
        $dateRange = self::getDateRangeForPeriod($period);
        $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
    }

    return [
        'total_amount' => $query->sum('commission_amount'),
        'total_net_amount' => $query->sum('net_amount'),
        'total_gross_amount' => $query->sum('gross_amount'),
        'pending' => $query->clone()->where('status', 'pending')->count(),
        'paid' => $query->clone()->where('status', 'paid')->count(),
        'held' => $query->clone()->where('status', 'held')->count(),
        'cancelled' => $query->clone()->where('status', 'cancelled')->count(),
        'avg_rate' => $query->avg('commission_rate') ?? 0,
        'total_transactions' => $query->count(),
        'pending_amount' => $query->clone()->where('status', 'pending')->sum('net_amount'),
        'paid_amount' => $query->clone()->where('status', 'paid')->sum('net_amount'),
    ];
}
    /**
 * Obtenir les plages de dates pour les filtres
 */
private static function getDateRangeForPeriod($period)
{
    switch ($period) {
        case 'today':
            return [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay()
            ];
        case 'this_week':
            return [
                'start' => now()->startOfWeek(),
                'end' => now()->endOfWeek()
            ];
        case 'this_month':
            return [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth()
            ];
        case 'last_month':
            return [
                'start' => now()->subMonth()->startOfMonth(),
                'end' => now()->subMonth()->endOfMonth()
            ];
        default:
            return [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth()
            ];
    }
}

    /**
     * Commission en attente pour un promoteur
     */
    public static function getPendingForPromoter($promoterId)
    {
        return self::where('promoter_id', $promoterId)
            ->where('status', 'pending')
            ->with(['order.event'])
            ->latest()
            ->get();
    }

    /**
     * Historique des paiements pour un promoteur
     */
    public static function getPaidHistoryForPromoter($promoterId, $limit = 10)
    {
        return self::where('promoter_id', $promoterId)
            ->where('status', 'paid')
            ->with(['order.event'])
            ->latest('paid_at')
            ->limit($limit)
            ->get();
    }
}