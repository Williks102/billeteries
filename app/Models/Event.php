<?php
// app/Models/Event.php - VERSION HARMONISÉE

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'promoter_id',  // ✅ CHANGÉ: promoteur_id → promoter_id
        'category_id',
        'title',
        'description',
        'venue',
        'address',
        'event_date',
        'event_time',
        'end_time',
        'status',
        'image',
        'terms_conditions'
    ];

    protected $casts = [
        'event_date' => 'date',
        'event_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Relations
     */
    public function promoter()  // ✅ CHANGÉ: Nouvelle relation principale
    {
        return $this->belongsTo(User::class, 'promoter_id');
    }
    
    public function promoteur()
{
    return $this->belongsTo(User::class, 'promoter_id'); // ✅ Votre DB utilise promoter_id
}



    public function category()
    {
        return $this->belongsTo(EventCategory::class, 'category_id');
    }

    public function ticketTypes()
    {
        return $this->hasMany(TicketType::class);
    }

    public function tickets()
    {
        return $this->hasManyThrough(Ticket::class, TicketType::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function commissions()  // ✅ CHANGÉ: Utilise promoter_id
    {
        return $this->hasMany(Commission::class, 'promoter_id', 'promoter_id');
    }

    /**
     * Scopes
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now()->toDateString());
    }

    public function scopePast($query)
    {
        return $query->where('event_date', '<', now()->toDateString());
    }

    public function scopeForPromoter($query, $promoterId)  // ✅ NOUVEAU: Scope pratique
    {
        return $query->where('promoter_id', $promoterId);
    }

    /**
     * Début de l'événement (date + heure de début).
     */
    public function startsAt(): ?Carbon
    {
        if (! $this->event_date) {
            return null;
        }

        return \App\Support\Events\SalePeriod::eventStart(
            $this->event_date->format('Y-m-d'),
            $this->event_time ? $this->event_time->format('H:i') : null
        );
    }

    /**
     * Checklist de publication (source unique : PublishEventAction, page de l'événement, création).
     * `blocking` : conditions obligatoires (code => message) ; `warnings` : recommandations non bloquantes.
     *
     * @return array{blocking: array<string,string>, warnings: array<string,string>, can_publish: bool}
     */
    public function publicationChecklist(): array
    {
        $blocking = [];
        $warnings = [];
        $now = now();
        $start = $this->startsAt();
        $active = $this->ticketTypes()->where('is_active', true)->get();

        if ($active->isEmpty()) {
            $blocking['tickets'] = 'Ajoutez au moins un billet actif.';
        }

        if ($start === null || $start->lessThanOrEqualTo($now)) {
            $blocking['date'] = "La date et l'heure de l'événement doivent être dans le futur.";
        }

        if ($active->isNotEmpty()) {
            $reasons = [];
            $hasValid = false;
            foreach ($active as $type) {
                $from = $type->sale_start_date;
                $to = $type->sale_end_date;
                if ($from === null || $to === null || $from->greaterThan($to)) {
                    $reasons['order'] = 'le début de vente est après la fin';
                } elseif ($start !== null && $to->greaterThan($start)) {
                    $reasons['after_event'] = "la fin de vente est après le début de l'événement";
                } elseif ($to->lessThan($now)) {
                    $reasons['closed'] = 'la période de vente est terminée';
                } else {
                    $hasValid = true;
                }
            }
            if (! $hasValid) {
                $blocking['sale_period'] = 'Aucun billet n\'a une période de vente valide ('.implode(' ; ', $reasons).').';
            }
        }

        if (empty($this->image)) {
            $blocking['image'] = "Ajoutez une image de l'événement.";
        }

        if (trim((string) $this->description) === '') {
            $warnings['description'] = 'La description est vide : elle aide les acheteurs à se décider.';
        }
        if ($active->count() === 1) {
            $warnings['single_ticket'] = 'Un seul type de billet : pensez à une offre complémentaire (VIP, gratuit...).';
        }

        return ['blocking' => $blocking, 'warnings' => $warnings, 'can_publish' => $blocking === []];
    }

    /**
     * Accessors
     */
    public function getFormattedEventDateAttribute()
    {
        return $this->event_date->format('d/m/Y');
    }

    public function getFormattedEventTimeAttribute()
    {
        return $this->event_time->format('H:i');
    }

    /** Date lisible en français (« Dimanche 15 novembre 2026 »), fuseau Africa/Abidjan. */
    public function getLongDateLabelAttribute(): string
    {
        if ($this->event_date === null) {
            return '';
        }

        return ucfirst($this->event_date->copy()->locale('fr')->translatedFormat('l j F Y'));
    }

    /** Horaire « 12h00 » ou « 12h00 – 15h00 » (heure de fin si elle existe) ; chaîne vide sans heure de début. */
    public function getTimeRangeLabelAttribute(): string
    {
        if ($this->event_time === null) {
            return '';
        }

        $format = fn ($t) => $t->copy()->setTimezone('Africa/Abidjan')->format('H\hi');
        $label = $format($this->event_time);

        return $this->end_time ? $label . ' – ' . $format($this->end_time) : $label;
    }

    /** Date et horaire sur une ligne : « Dimanche 15 novembre 2026 · 12h00 – 15h00 ». */
    public function getDateTimeLabelAttribute(): string
    {
        return trim($this->long_date_label . ($this->time_range_label !== '' ? ' · ' . $this->time_range_label : ''), ' ·');
    }

    /** Seuil sous lequel le nombre exact de places restantes est affiché. */
    public const LOW_PLACES_THRESHOLD = 20;

    /** Libellé public des places : le compte n'apparaît que s'il en reste peu. */
    public function placesLabel(): string
    {
        $left = $this->remainingPlaces();

        return match (true) {
            $left <= 0 => 'Complet',
            $left === 1 => '1 place restante',
            $left <= self::LOW_PLACES_THRESHOLD => $left . ' places restantes',
            default => 'Places disponibles',
        };
    }

    /** Prix d'appel affiché (« À partir de 5 000 FCFA » ou « Gratuit »), null sans billet chargé. */
    public function startingPriceLabel(): ?string
    {
        if ($this->ticketTypes->isEmpty()) {
            return null;
        }
        $min = $this->ticketTypes->min('price');

        return ((int) $min === 0 ? '' : 'À partir de ') . \App\Helpers\CurrencyHelper::formatPrice($min);
    }

    /** La barre d'achat n'apparaît que si l'événement accepte des ventes et qu'un billet est en vente. */
    public function showsBuyBar(): bool
    {
        return $this->ticketTypes->isNotEmpty() && $this->ticketTypes->first()->setRelation('event', $this)->eventAcceptsSales();
    }

    public function getIsUpcomingAttribute()
    {
        return $this->event_date >= now()->toDateString();
    }

    public function getIsPastAttribute()
    {
        return $this->event_date < now()->toDateString();
    }

    /*
     * Statistiques : délèguent aux services App\Services\Stats (définitions uniques, voir docs/agents/decisions.md).
     * Signatures publiques conservées (appelées par les vues admin).
     */

    /** Ventes (GMV, hors frais) de l'événement, toutes dates : somme des lignes des commandes payées. */
    public function totalRevenue()
    {
        return app(\App\Services\Stats\SalesStats::class)->gmv(\App\Services\Stats\Scope::event($this->id), \App\Support\Stats\DatePeriod::all());
    }

    /**
     * Places restantes AFFICHÉES AU PUBLIC (D6) : capacité - quantity_sold, réservations non payées déduites
     * (anti-survente), sur les types de billets chargés. Distinct de availableTicketsCount() (écrans internes).
     */
    public function remainingPlaces(): int
    {
        return app(\App\Services\Stats\PublicStats::class)->remaining($this)['total'];
    }

    /** Nombre de types de billets chargés (affichage de la fiche publique). */
    public function ticketTypesCount(): int
    {
        return $this->ticketTypes->count();
    }

    /** Billets vendus = sold + used d'une commande payée. */
    public function getTicketsSoldCount()
    {
        return app(\App\Services\Stats\TicketStats::class)->sold(\App\Services\Stats\Scope::event($this->id));
    }

    /** Places disponibles = capacité - vendus (les réservations non payées restent disponibles). */
    public function availableTicketsCount()
    {
        return app(\App\Services\Stats\TicketStats::class)->available(\App\Services\Stats\Scope::event($this->id)) ?? 0;
    }

    /** Commandes PAYÉES de l'événement. */
    public function getOrdersCount()
    {
        return app(\App\Services\Stats\SalesStats::class)->paidOrdersCount(\App\Services\Stats\Scope::event($this->id), \App\Support\Stats\DatePeriod::all());
    }

    /** Capacité = somme des quantités mises en vente. */
    public function totalTicketsAvailable()
    {
        return app(\App\Services\Stats\TicketStats::class)->capacity(\App\Services\Stats\Scope::event($this->id)) ?? 0;
    }

    /** Taux de remplissage (%) = vendus / capacité, 1 décimale (0 sans capacité). */
    public function getProgressPercentage()
    {
        return app(\App\Services\Stats\TicketStats::class)->fillRate(\App\Services\Stats\Scope::event($this->id)) ?? 0;
    }

    /** Commission de la plateforme sur les commandes payées de l'événement, toutes dates. */
    public function getCommissionEarned()
    {
        return app(\App\Services\Stats\CommissionStats::class)->platformRevenue(\App\Services\Stats\Scope::event($this->id), \App\Support\Stats\DatePeriod::all());
    }
}