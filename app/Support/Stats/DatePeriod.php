<?php

namespace App\Support\Stats;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

/**
 * Période normalisée des statistiques : bornes [début 00:00:00, fin 23:59:59.999999] en Africa/Abidjan,
 * fin INCLUSE. Une seule source de parsing de ?period= & start_date= & end_date= (Request).
 * Période « all » : aucune borne (start/end null). Défaut unique : this_month (mois civil en cours).
 */
final class DatePeriod
{
    public const TIMEZONE = 'Africa/Abidjan';

    public const DEFAULT = 'this_month';

    public const PRESETS = ['today', '7d', '30d', 'this_month', 'last_month', 'this_year', 'all'];

    private const LABELS = [
        'today' => "Aujourd'hui",
        '7d' => '7 derniers jours',
        '30d' => '30 derniers jours',
        'this_month' => 'Ce mois-ci',
        'last_month' => 'Mois dernier',
        'this_year' => 'Cette année',
        'all' => 'Toute la période',
    ];

    private function __construct(
        public readonly string $key,
        public readonly ?CarbonImmutable $start,
        public readonly ?CarbonImmutable $end,
        public readonly string $label,
    ) {}

    /** Période par défaut (mois civil en cours). */
    public static function default(?CarbonInterface $now = null): self
    {
        return self::preset(self::DEFAULT, $now);
    }

    public static function preset(string $key, ?CarbonInterface $now = null): self
    {
        $today = self::today($now);

        [$start, $end] = match ($key) {
            'today' => [$today, $today],
            '7d' => [$today->subDays(6), $today],
            '30d' => [$today->subDays(29), $today],
            'this_month' => [$today->startOfMonth(), $today->endOfMonth()],
            'last_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [$today->startOfYear(), $today->endOfYear()],
            'all' => [null, null],
            default => throw new InvalidPeriodException("Période inconnue : {$key}."),
        };

        return new self($key, $start?->startOfDay(), $end?->endOfDay(), self::LABELS[$key]);
    }

    public static function all(): self
    {
        return self::preset('all');
    }

    /** Période personnalisée : dates inclusives (début 00:00:00, fin 23:59:59.999999). */
    public static function custom(CarbonInterface|string $start, CarbonInterface|string $end): self
    {
        $s = self::day($start);
        $e = self::day($end);

        if ($s->greaterThan($e)) {
            throw new InvalidPeriodException('La date de début doit précéder la date de fin.');
        }

        return new self('custom', $s->startOfDay(), $e->endOfDay(), 'Du '.$s->format('d/m/Y').' au '.$e->format('d/m/Y'));
    }

    /**
     * Parseur unique de la requête : ?period=<preset|custom>&start_date=Y-m-d&end_date=Y-m-d.
     * Sans paramètre : période par défaut. start_date/end_date présents => période personnalisée
     * (les deux sont alors obligatoires). Toute valeur invalide lève InvalidPeriodException.
     */
    public static function from(Request $request, ?CarbonInterface $now = null): self
    {
        $period = $request->input('period');
        $start = $request->input('start_date');
        $end = $request->input('end_date');

        foreach (['period' => $period, 'start_date' => $start, 'end_date' => $end] as $name => $value) {
            if ($value !== null && ! is_string($value)) {
                throw new InvalidPeriodException("Paramètre {$name} invalide.");
            }
        }

        $hasStart = $start !== null && $start !== '';
        $hasEnd = $end !== null && $end !== '';

        if ($hasStart || $hasEnd || $period === 'custom') {
            if (! $hasStart || ! $hasEnd) {
                throw new InvalidPeriodException('Une période personnalisée exige start_date et end_date.');
            }

            return self::custom($start, $end);
        }

        if ($period === null || $period === '') {
            return self::default($now);
        }

        return self::preset($period, $now);
    }

    /**
     * Période précédente de MÊME durée, accolée au début de celle-ci (ex. 30 jours => les 30 jours d'avant).
     * Null pour « Toute la période » (non bornée) : la croissance n'est alors pas calculable.
     */
    public function previous(): ?self
    {
        if (! $this->isBounded()) {
            return null;
        }

        $days = (int) round($this->start->startOfDay()->diffInDays($this->end->startOfDay())) + 1;
        $end = $this->start->subDay()->endOfDay();
        $start = $end->subDays($days - 1)->startOfDay();

        return new self('previous', $start, $end, 'Du '.$start->format('d/m/Y').' au '.$end->format('d/m/Y'));
    }

    public function isBounded(): bool
    {
        return $this->start !== null && $this->end !== null;
    }

    /** Borne de début au format SQL, fuseau d'écriture des timestamps (celui de l'application). */
    public function startForDb(): ?string
    {
        return $this->start?->setTimezone(date_default_timezone_get())->format('Y-m-d H:i:s');
    }

    /** Borne de fin (incluse) au format SQL, avec microsecondes. */
    public function endForDb(): ?string
    {
        return $this->end?->setTimezone(date_default_timezone_get())->format('Y-m-d H:i:s.u');
    }

    private static function today(?CarbonInterface $now): CarbonImmutable
    {
        $now ??= \Carbon\Carbon::now();

        return CarbonImmutable::instance($now)->setTimezone(self::TIMEZONE);
    }

    private static function day(CarbonInterface|string $value): CarbonImmutable
    {
        if ($value instanceof CarbonInterface) {
            return CarbonImmutable::instance($value)->setTimezone(self::TIMEZONE)->startOfDay();
        }

        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone(self::TIMEZONE));
        $errors = \DateTimeImmutable::getLastErrors();

        if ($parsed === false || ($errors && ($errors['warning_count'] || $errors['error_count'])) || $parsed->format('Y-m-d') !== $value) {
            throw new InvalidPeriodException("Date invalide (format AAAA-MM-JJ attendu) : {$value}.");
        }

        return CarbonImmutable::instance($parsed)->startOfDay();
    }
}
