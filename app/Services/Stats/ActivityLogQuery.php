<?php

namespace App\Services\Stats;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use App\Support\Stats\DatePeriod;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Lecture du journal d'activité pour l'écran admin : filtres (famille d'action, acteur, période),
 * tri du plus récent, pagination (25), lien vers le sujet quand il existe encore.
 * La vue reçoit des lignes toutes prêtes : aucune requête ni calcul dans Blade.
 */
class ActivityLogQuery
{
    public const PER_PAGE = 25;

    /** Plafond de la liste déroulante des acteurs. */
    private const MAX_ACTORS = 200;

    /** @param array{family?:?string, actor?:?string} $filters */
    public function paginate(array $filters, DatePeriod $period): LengthAwarePaginator
    {
        $query = ActivityLog::query()->with('actor:id,name,role')->ofFamily($filters['family'] ?? null);

        $actor = $filters['actor'] ?? null;
        if ($actor === 'system') {
            $query->whereNull('user_id');
        } elseif (is_string($actor) && ctype_digit($actor)) {
            $query->where('user_id', (int) $actor);
        }

        if ($period->start !== null && $period->end !== null) {
            $query->whereBetween('created_at', [$period->start, $period->end]);
        }

        $page = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(self::PER_PAGE)->withQueryString();

        $links = $this->subjectLinks($page->getCollection());

        return $page->through(fn (ActivityLog $log) => [
            'id' => $log->id,
            'when' => $log->created_at?->timezone(DatePeriod::TIMEZONE)->format('d/m/Y H:i'),
            'label' => $log->label(),
            'description' => $log->description,
            'actor' => $log->actor?->name ?? ($log->user_id ? 'Compte supprimé' : 'Système'),
            'role' => $log->actor_role,
            'details' => $this->details($log->properties ?? []),
            'subject_url' => $links[$log->subject_type.'#'.$log->subject_id] ?? null,
        ]);
    }

    /** @return array<int,array{id:int,name:string}> acteurs présents dans le journal (liste déroulante) */
    public function actors(): array
    {
        $ids = ActivityLog::query()->whereNotNull('user_id')->distinct()->limit(self::MAX_ACTORS)->pluck('user_id');

        return User::whereIn('id', $ids)->orderBy('name')->get(['id', 'name'])
            ->map(fn (User $u) => ['id' => $u->id, 'name' => (string) $u->name])->all();
    }

    /** @return array<string,string> famille => libellé */
    public function families(): array
    {
        return array_map(fn ($f) => $f[0], ActivityLog::FAMILIES);
    }

    /**
     * Liens vers les sujets qui existent encore (une requête par type de sujet, pas une par ligne).
     *
     * @return array<string,string> "Type#id" => URL
     */
    private function subjectLinks($logs): array
    {
        $routes = [
            'Order' => [Order::class, 'admin.orders.show'],
            'Event' => [Event::class, 'admin.events.show'],
            'User' => [User::class, 'admin.users.show'],
            'Ticket' => [Ticket::class, 'admin.tickets.show'],
        ];

        $links = [];
        foreach ($routes as $type => [$model, $route]) {
            $ids = $logs->where('subject_type', $type)->pluck('subject_id')->filter()->unique()->values();
            if ($ids->isEmpty()) {
                continue;
            }
            foreach ($model::whereIn('id', $ids)->pluck('id') as $id) {
                $links[$type.'#'.$id] = route($route, $id);
            }
        }

        // Un type de billet renvoie vers son événement (s'il existe encore)
        $typeLogs = $logs->where('subject_type', 'TicketType')->whereNotNull('event_id');
        $existing = Event::whereIn('id', $typeLogs->pluck('event_id')->unique())->pluck('id')->flip();
        foreach ($typeLogs as $log) {
            if ($existing->has($log->event_id)) {
                $links['TicketType#'.$log->subject_id] = route('admin.events.show', $log->event_id);
            }
        }

        return $links;
    }

    /** @return array<int,string> lignes « champ : avant → après » lisibles */
    private function details(array $properties): array
    {
        $lines = [];
        foreach ($properties as $key => $value) {
            if (is_array($value) && array_key_exists('from', $value) && array_key_exists('to', $value)) {
                $lines[] = $key.' : '.$this->scalar($value['from']).' → '.$this->scalar($value['to']);
            } elseif (! is_array($value)) {
                $lines[] = $key.' : '.$this->scalar($value);
            }
        }

        return $lines;
    }

    private function scalar(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'oui' : 'non',
            $value === null => '—',
            default => (string) $value,
        };
    }
}
