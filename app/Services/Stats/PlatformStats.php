<?php

namespace App\Services\Stats;

use App\Support\Stats\DatePeriod;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Chiffres de plateforme (utilisateurs, événements) pour les écrans admin. Lecture seule, agrégats SQL :
 * le nombre de requêtes ne dépend pas du volume de données.
 * « Créés sur la période » = created_at dans la période ; les totaux sont instantanés.
 */
class PlatformStats
{
    /**
     * @return array{total: int, admins: int, promoteurs: int, acheteurs: int, verified: int, new: int, new_promoteurs: int, new_acheteurs: int, new_verified: int, new_active: int}
     */
    public function users(DatePeriod $period): array
    {
        $total = DB::table('users')
            ->selectRaw("COUNT(*) as total,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
                SUM(CASE WHEN role = 'promoteur' THEN 1 ELSE 0 END) as promoteurs,
                SUM(CASE WHEN role = 'acheteur' THEN 1 ELSE 0 END) as acheteurs,
                SUM(CASE WHEN email_verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified")
            ->first();

        $new = $this->between(DB::table('users'), 'users.created_at', $period)
            ->selectRaw("COUNT(*) as total,
                SUM(CASE WHEN role = 'promoteur' THEN 1 ELSE 0 END) as promoteurs,
                SUM(CASE WHEN role = 'acheteur' THEN 1 ELSE 0 END) as acheteurs,
                SUM(CASE WHEN email_verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified")
            ->first();

        $active = $this->between(DB::table('users'), 'users.created_at', $period)
            ->where(function ($q) {
                $q->whereExists(fn ($s) => $s->select(DB::raw(1))->from('orders')->whereColumn('orders.user_id', 'users.id'))
                    ->orWhereExists(fn ($s) => $s->select(DB::raw(1))->from('events')->whereColumn('events.promoter_id', 'users.id'));
            })
            ->count();

        return [
            'total' => (int) $total->total,
            'admins' => (int) $total->admins,
            'promoteurs' => (int) $total->promoteurs,
            'acheteurs' => (int) $total->acheteurs,
            'verified' => (int) $total->verified,
            'new' => (int) $new->total,
            'new_promoteurs' => (int) $new->promoteurs,
            'new_acheteurs' => (int) $new->acheteurs,
            'new_verified' => (int) $new->verified,
            'new_active' => $active,
        ];
    }

    /**
     * Événements par statut. Sans période : tous les événements (instantané) ; avec période : ceux créés dedans.
     *
     * @return array{total: int, published: int, pending: int, draft: int, rejected: int}
     */
    public function events(?DatePeriod $createdIn = null): array
    {
        $query = DB::table('events');
        if ($createdIn !== null) {
            $this->between($query, 'events.created_at', $createdIn);
        }

        $rows = $query->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'total' => (int) $rows->sum(),
            'published' => (int) ($rows['published'] ?? 0),
            'pending' => (int) ($rows['pending'] ?? 0),
            'draft' => (int) ($rows['draft'] ?? 0),
            'rejected' => (int) ($rows['rejected'] ?? 0),
        ];
    }

    /**
     * Inscriptions par jour (période bornée de 92 jours au plus) ou par mois (sinon), jours/mois vides à 0.
     *
     * @return array{unit: string, rows: list<array{date: string, count: int}>}
     */
    public function registrations(DatePeriod $period): array
    {
        $byMonth = ! $period->isBounded() || $period->start->diffInDays($period->end) > 92;
        $bucket = $byMonth ? 'SUBSTR(users.created_at, 1, 7)' : 'SUBSTR(users.created_at, 1, 10)';

        $counts = $this->between(DB::table('users'), 'users.created_at', $period)
            ->selectRaw("{$bucket} as bucket, COUNT(*) as total")->groupBy('bucket')->pluck('total', 'bucket');

        if ($counts->isEmpty() && ! $period->isBounded()) {
            return ['unit' => 'month', 'rows' => []];
        }

        $from = $period->start ?? \Carbon\CarbonImmutable::parse($counts->keys()->sort()->first().'-01');
        $to = $period->end ?? \Carbon\CarbonImmutable::parse($counts->keys()->sort()->last().'-01');
        $cursor = $byMonth ? $from->startOfMonth() : $from->startOfDay();
        $last = $byMonth ? $to->startOfMonth() : $to->startOfDay();

        $rows = [];
        while ($cursor->lessThanOrEqualTo($last)) {
            $key = $cursor->format($byMonth ? 'Y-m' : 'Y-m-d');
            $rows[] = ['date' => $key, 'count' => (int) ($counts[$key] ?? 0)];
            $cursor = $byMonth ? $cursor->addMonthNoOverflow() : $cursor->addDay();
        }

        return ['unit' => $byMonth ? 'month' : 'day', 'rows' => $rows];
    }

    /**
     * Promoteurs classés par net (commissions pending/paid des commandes payées de la période).
     * Deux requêtes quel que soit le nombre de promoteurs.
     *
     * @return list<array{id: int, name: string, net: int, orders: int}>
     */
    public function topPromoters(DatePeriod $period, int $limit = 5): array
    {
        $paid = \App\Models\Order::query()->paid()->inPeriod($period)->select('orders.id');

        $rows = DB::table('commissions')
            ->whereIn('commissions.order_id', $paid)
            ->whereIn('commissions.status', ['pending', 'paid'])
            ->selectRaw('commissions.promoter_id as promoter_id, SUM(commissions.net_amount) as net, COUNT(DISTINCT commissions.order_id) as orders_count')
            ->groupBy('commissions.promoter_id')
            ->orderByDesc('net')
            ->limit($limit)
            ->get();

        $names = DB::table('users')->whereIn('id', $rows->pluck('promoter_id'))->pluck('name', 'id');

        return $rows->map(fn ($r) => [
            'id' => (int) $r->promoter_id,
            'name' => (string) ($names[$r->promoter_id] ?? 'Promoteur supprimé'),
            'net' => (int) $r->net,
            'orders' => (int) $r->orders_count,
        ])->all();
    }

    /**
     * Nouveaux utilisateurs de la période les plus actifs (commandes + événements créés). Une requête.
     *
     * @return list<array{id: int, name: string, role: string, actions: int}>
     */
    public function mostActiveNewUsers(DatePeriod $period, int $limit = 5): array
    {
        return $this->between(DB::table('users'), 'users.created_at', $period)
            ->selectRaw('users.id, users.name, users.role, ((SELECT COUNT(*) FROM orders WHERE orders.user_id = users.id) + (SELECT COUNT(*) FROM events WHERE events.promoter_id = users.id)) as actions')
            ->orderByDesc('actions')->orderBy('users.id')
            ->limit($limit)->get()
            ->filter(fn ($u) => (int) $u->actions > 0)
            ->map(fn ($u) => ['id' => (int) $u->id, 'name' => (string) $u->name, 'role' => (string) $u->role, 'actions' => (int) $u->actions])
            ->values()->all();
    }

    private function between(Builder $query, string $column, DatePeriod $period): Builder
    {
        if ($period->start !== null) {
            $query->where($column, '>=', $period->startForDb());
        }
        if ($period->end !== null) {
            $query->where($column, '<=', $period->endForDb());
        }

        return $query;
    }
}
