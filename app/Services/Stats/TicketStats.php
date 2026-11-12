<?php

namespace App\Services\Stats;

use App\Models\Order;
use App\Models\Ticket;
use App\Support\Stats\DatePeriod;
use Illuminate\Support\Facades\DB;

/**
 * Billets. Vendu = statut `sold` + `used` d'une commande `paid` (réservé, annulé, remboursé : non vendu).
 * La période s'applique à la date de vente de la commande (paid_at, repli created_at) pour TOUS les
 * indicateurs de vente, de sorte que scannés <= vendus. Capacité, disponibles, remplissage et réservés
 * sont instantanés (la période est ignorée). Réservations non payées = « disponibles » (décision propriétaire).
 */
class TicketStats
{
    public function sold(Scope $scope, ?DatePeriod $period = null): int
    {
        return $this->soldQuery($scope, $period)->whereIn('tickets.status', ['sold', 'used'])->count();
    }

    /** Billets scannés (`used`) parmi les billets vendus de la période. */
    public function scanned(Scope $scope, ?DatePeriod $period = null): int
    {
        return $this->soldQuery($scope, $period)->where('tickets.status', 'used')->count();
    }

    /** Billets réservés (commande en attente de paiement), instantané. */
    public function reserved(Scope $scope): int
    {
        return Ticket::query()
            ->ofOrders(Order::query()->forScope($scope)->whereIn('payment_status', ['pending', 'processing']))
            ->where('tickets.status', 'reserved')
            ->count();
    }

    /** Capacité = somme des quantity_available des types du périmètre ; null pour un acheteur. */
    public function capacity(Scope $scope): ?int
    {
        $query = $scope->constrainTicketTypes(DB::table('ticket_types'));

        return $query === null ? null : (int) $query->sum('ticket_types.quantity_available');
    }

    /** Places disponibles = capacité − vendus (toutes dates) ; null si capacité inconnue. */
    public function available(Scope $scope): ?int
    {
        $capacity = $this->capacity($scope);

        return $capacity === null ? null : max(0, $capacity - $this->sold($scope));
    }

    /** Taux de remplissage (%) = vendus / capacité, 1 décimale ; null si capacité nulle ou inconnue. */
    public function fillRate(Scope $scope): ?float
    {
        $capacity = $this->capacity($scope);

        return $capacity ? round($this->sold($scope) / $capacity * 100, 1) : null;
    }

    /** Taux d'utilisation (%) = scannés / vendus, 1 décimale ; null sans billet vendu. */
    public function usageRate(Scope $scope, ?DatePeriod $period = null): ?float
    {
        $sold = $this->sold($scope, $period);

        return $sold > 0 ? round($this->scanned($scope, $period) / $sold * 100, 1) : null;
    }

    private function soldQuery(Scope $scope, ?DatePeriod $period)
    {
        $orders = Order::query()->paid()->forScope($scope);
        if ($period !== null) {
            $orders->inPeriod($period);
        }

        return Ticket::query()->ofOrders($orders);
    }
}
