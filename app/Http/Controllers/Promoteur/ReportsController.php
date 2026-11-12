<?php

namespace App\Http\Controllers\Promoteur;

use App\Helpers\PrivacyHelper;
use App\Http\Controllers\Admin\Concerns\ResolvesStatsPeriod;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Stats\PromoterStats;
use App\Support\CsvExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReportsController extends Controller
{
    use ResolvesStatsPeriod;

    private const TYPES = ['overview', 'events', 'financial'];

    public function __construct(private readonly PromoterStats $stats) {}

    /**
     * Rapports du promoteur connecté (vue d'ensemble, par événement, financier). Un seul calcul des chiffres :
     * PromoterStats (commandes payées, GMV = somme des lignes : une commande à plusieurs lignes compte une fois).
     */
    public function index(Request $request)
    {
        $type = $request->route()?->parameter('type') ?? $request->query('type', 'overview');
        $type = in_array($type, self::TYPES, true) ? $type : 'overview';

        $period = $this->statsPeriod($request, $request->route()?->getName() ?? 'promoteur.reports.index');
        $promoterId = (int) Auth::id();

        $stats = $this->stats->overview($promoterId, $period);

        $data = match ($type) {
            'events' => ['events' => $this->stats->eventsReport($promoterId, $period)],
            'financial' => ['months' => $this->stats->monthlyFinancials($promoterId, $period)],
            default => [
                'series' => $this->stats->salesSeries($promoterId, $period),
                'recent_orders' => $this->stats->recentPaidOrders($promoterId, $period, 10),
            ],
        };

        return view('promoteur.reports.index', compact('stats', 'data', 'type', 'period'));
    }

    /**
     * Export CSV du promoteur connecté, mêmes chiffres que ses écrans (PromoterStats, même DatePeriod::from) :
     *  - type=sales (défaut, écran « Ventes ») : une ligne par commande PAYÉE de la période ; ?event=<id> restreint à
     *    un de SES événements (404 sinon) ;
     *  - type=events : un événement par ligne (écran « Rapports > Par événement ») ;
     *  - type=financial : un mois par ligne (écran « Rapports > Financier »).
     * Toujours restreint à Auth::id() ; client = nom, à défaut e-mail MASQUÉ (comme l'écran des ventes).
     */
    public function export(Request $request)
    {
        $type = $request->query('type', 'sales');
        if (! in_array($type, ['sales', 'events', 'financial'], true)) {
            throw ValidationException::withMessages(['type' => 'Type d\'export inconnu.'])->redirectTo(route('promoteur.sales.index'));
        }

        $period = $this->statsPeriod($request, 'promoteur.sales.index');
        $promoterId = (int) Auth::id();
        $file = 'export-'.$type.'-'.now()->format('Y-m-d').'.csv';

        if ($type === 'events') {
            return CsvExport::response($file,
                ['Événement', 'Statut', 'Date événement', 'Billets vendus', 'Capacité', 'Remplissage (%)', 'Commandes payées', 'Ventes (FCFA)', 'Net (FCFA)'],
                function (callable $emit) use ($promoterId, $period) {
                    foreach ($this->stats->eventsReport($promoterId, $period) as $e) {
                        $emit([$e['title'], $e['status'], $e['event_date'] ? substr((string) $e['event_date'], 0, 10) : '', $e['sold'], $e['capacity'],
                            $e['fill_rate'] ?? '', $e['orders_paid'], $e['gmv'], $e['promoter_net']]);
                    }
                });
        }

        if ($type === 'financial') {
            return CsvExport::response($file,
                ['Mois', 'Commandes payées', 'Ventes (FCFA)', 'Commission (FCFA)', 'Net (FCFA)'],
                function (callable $emit) use ($promoterId, $period) {
                    foreach ($this->stats->monthlyFinancials($promoterId, $period) as $m) {
                        $emit([$m['date'], $m['orders'], $m['gmv'], $m['commission'], $m['net']]);
                    }
                });
        }

        $eventId = null;
        if ($request->filled('event')) {
            $eventId = (int) $request->query('event');
            abort_unless(Event::query()->whereKey($eventId)->where('promoter_id', $promoterId)->exists(), 404);
        }
        $query = $this->stats->paidOrdersForExport($promoterId, $period, $eventId);

        return CsvExport::response($file,
            ['Commande', 'Date', 'Client', 'Événement', 'Billets', 'Montant (FCFA)', 'Commission (FCFA)', 'Net (FCFA)', 'Statut versement'],
            function (callable $emit) use ($query) {
                $query->chunkByIdDesc(500, function ($orders) use ($emit) {
                    foreach ($orders as $order) {
                        // Commission comptée seulement si pending/paid (held/cancelled : jamais dans les revenus)
                        $c = $order->commission && in_array($order->commission->status, ['pending', 'paid'], true) ? $order->commission : null;
                        $emit([
                            $order->order_number,
                            ($order->paid_at ?? $order->created_at)->format('d/m/Y H:i'),
                            $order->user?->name ?? PrivacyHelper::maskEmail($order->billing_email),
                            $order->event?->title ?? 'N/A',
                            (int) $order->tickets_quantity,
                            (int) round($order->total_amount),
                            (int) ($c?->commission_amount ?? 0),
                            (int) ($c?->net_amount ?? 0),
                            $c ? ($c->status === 'paid' ? 'Versé' : 'À recevoir') : '',
                        ]);
                    }
                });
            });
    }
}
