<?php

namespace App\Services\Stats;

use App\Models\Event;
use App\Models\TicketType;
use App\Support\Stats\DatePeriod;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Chiffres des pages PUBLIQUES (accueil, « à propos », fiche événement). Agrégats GLOBAUX uniquement : jamais de
 * chiffre par promoteur, par acheteur ni par événement (ventes détaillées, revenu). Aucun comptage de visiteurs.
 *  - Billets vendus : sold + used des commandes payées, toutes dates (TicketStats) ; ni réservé ni remboursé.
 *  - Événements à venir : publiés, date >= aujourd'hui (Africa/Abidjan), comme la liste de l'accueil.
 *  - Acheteurs / organisateurs : comptes inscrits de ce rôle (et non des « actifs » : aucune activité n'est mesurée).
 *  - Places restantes (décision D6) : capacité − quantity_sold. quantity_sold inclut les réservations non payées :
 *    c'est la disponibilité RÉELLE (anti-survente) ; le public ne voit ni ventes ni taux de remplissage.
 */
class PublicStats
{
    public function __construct(private readonly TicketStats $tickets) {}

    /** @return array{events_upcoming: int, tickets_sold: int, buyers: int, promoters: int} */
    public function homeCounters(?CarbonInterface $now = null): array
    {
        $today = CarbonImmutable::instance($now ?? now())->setTimezone(DatePeriod::TIMEZONE)->toDateString();
        $roles = $this->roles();

        return [
            'events_upcoming' => DB::table('events')->where('status', 'published')
                ->whereRaw('SUBSTR(events.event_date, 1, 10) >= ?', [$today])->count(),
            'tickets_sold' => $this->tickets->sold(Scope::admin()),
            'buyers' => $roles['acheteur'],
            'promoters' => $roles['promoteur'],
        ];
    }

    /** Places restantes d'un type de billet (jamais négatif). */
    public function remainingForType(TicketType $type): int
    {
        return max(0, (int) $type->quantity_available - (int) $type->quantity_sold);
    }

    /**
     * Places restantes d'un événement, calculées sur les types de billets DÉJÀ chargés (aucune requête).
     *
     * @return array{by_type: array<int, int>, total: int}
     */
    public function remaining(Event $event): array
    {
        $byType = [];
        foreach ($event->ticketTypes as $type) {
            $byType[$type->id] = $this->remainingForType($type);
        }

        return ['by_type' => $byType, 'total' => array_sum($byType)];
    }

    /** @return array{acheteur: int, promoteur: int} */
    private function roles(): array
    {
        $rows = DB::table('users')->whereIn('role', ['acheteur', 'promoteur'])
            ->selectRaw('role, COUNT(*) as total')->groupBy('role')->pluck('total', 'role');

        return ['acheteur' => (int) ($rows['acheteur'] ?? 0), 'promoteur' => (int) ($rows['promoteur'] ?? 0)];
    }
}
