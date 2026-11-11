<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Event, Order, Ticket, User};
use Illuminate\Http\Request;

/**
 * Endpoints JSON d'analytics admin (extraits de routes/web.php, corps inchangés).
 * NB : cart-abandonment et predictions renvoient des valeurs simulées (en dur).
 */
class AnalyticsController extends Controller
{
    public function realtime()
    {
            $realTimeStats = [
                'active_users' => User::where('last_activity', '>=', now()->subMinutes(5))->count(),
                'orders_today' => Order::whereDate('created_at', today())->count(),
                'revenue_today' => Order::where('payment_status', 'paid')
                    ->whereDate('created_at', today())->sum('total_amount'),
                'tickets_sold_today' => Ticket::where('status', 'sold')
                    ->whereDate('created_at', today())->count(),
                'conversion_rate_today' => 3.2, // À calculer selon vos métriques
                'top_selling_event' => Event::withCount(['tickets as sold_today' => function($query) {
                        $query->where('status', 'sold')->whereDate('created_at', today());
                    }])
                    ->orderBy('sold_today', 'desc')
                    ->first(),
            ];
            
            return response()->json($realTimeStats);
    }

    public function event(Event $event)
    {
            $eventAnalytics = [
                'total_revenue' => $event->orders()->where('payment_status', 'paid')->sum('total_amount'),
                'tickets_sold' => $event->tickets()->where('status', 'sold')->count(),
                'tickets_available' => $event->ticketTypes()->sum('quantity_available'),
                'attendance_rate' => $event->tickets()->where('status', 'used')->count(),
                'daily_sales' => $event->orders()
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as revenue')
                    ->where('payment_status', 'paid')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get(),
                'ticket_types_performance' => $event->ticketTypes()
                    ->withCount(['tickets as sold' => function($query) {
                        $query->where('status', 'sold');
                    }])
                    ->withSum(['tickets as revenue' => function($query) {
                        $query->where('status', 'sold');
                    }], 'price')
                    ->get(),
                'buyer_demographics' => $event->orders()
                    ->with('user')
                    ->where('payment_status', 'paid')
                    ->get()
                    ->groupBy('user.role')
                    ->map(function($group) {
                        return $group->count();
                    }),
            ];
            
            return response()->json($eventAnalytics);
    }

    public function promoter(User $promoter)
    {
            if (!$promoter->isPromoter()) {
                return response()->json(['error' => 'Utilisateur non promoteur'], 400);
            }
            
            $promoterAnalytics = [
                'total_events' => $promoter->events()->count(),
                'published_events' => $promoter->events()->where('status', 'published')->count(),
                'total_revenue' => $promoter->events()
                    ->join('orders', 'events.id', '=', 'orders.event_id')
                    ->where('orders.payment_status', 'paid')
                    ->sum('orders.total_amount'),
                'total_tickets_sold' => $promoter->events()
                    ->join('tickets', 'events.id', '=', 'tickets.event_id')
                    ->where('tickets.status', 'sold')
                    ->count(),
                'commission_earned' => $promoter->commissions()->sum('commission_amount'),
                'events_performance' => $promoter->events()
                    ->withCount(['tickets as sold' => function($query) {
                        $query->where('status', 'sold');
                    }])
                    ->withSum(['orders as revenue' => function($query) {
                        $query->where('payment_status', 'paid');
                    }], 'total_amount')
                    ->orderBy('revenue', 'desc')
                    ->take(10)
                    ->get(),
                'monthly_performance' => collect(range(0, 11))->map(function($i) use ($promoter) {
                    $date = now()->subMonths($i);
                    return [
                        'month' => $date->format('M Y'),
                        'revenue' => $promoter->events()
                            ->join('orders', 'events.id', '=', 'orders.event_id')
                            ->where('orders.payment_status', 'paid')
                            ->whereYear('orders.created_at', $date->year)
                            ->whereMonth('orders.created_at', $date->month)
                            ->sum('orders.total_amount'),
                        'tickets_sold' => $promoter->events()
                            ->join('tickets', 'events.id', '=', 'tickets.event_id')
                            ->where('tickets.status', 'sold')
                            ->whereYear('tickets.created_at', $date->year)
                            ->whereMonth('tickets.created_at', $date->month)
                            ->count(),
                    ];
                })->reverse()->values(),
            ];
            
            return response()->json($promoterAnalytics);
    }

    public function compare(Request $request)
    {
            $request->validate([
                'period1_start' => 'required|date',
                'period1_end' => 'required|date',
                'period2_start' => 'required|date',
                'period2_end' => 'required|date',
            ]);
            
            $period1 = [$request->period1_start, $request->period1_end];
            $period2 = [$request->period2_start, $request->period2_end];
            
            $comparison = [
                'period1' => [
                    'revenue' => Order::where('payment_status', 'paid')
                        ->whereBetween('created_at', $period1)->sum('total_amount'),
                    'orders' => Order::whereBetween('created_at', $period1)->count(),
                    'users' => User::whereBetween('created_at', $period1)->count(),
                    'events' => Event::whereBetween('created_at', $period1)->count(),
                ],
                'period2' => [
                    'revenue' => Order::where('payment_status', 'paid')
                        ->whereBetween('created_at', $period2)->sum('total_amount'),
                    'orders' => Order::whereBetween('created_at', $period2)->count(),
                    'users' => User::whereBetween('created_at', $period2)->count(),
                    'events' => Event::whereBetween('created_at', $period2)->count(),
                ]
            ];
            
            // Calculer les pourcentages de variation
            $comparison['changes'] = [];
            foreach ($comparison['period1'] as $key => $value1) {
                $value2 = $comparison['period2'][$key];
                $change = $value2 > 0 ? (($value1 - $value2) / $value2) * 100 : 0;
                $comparison['changes'][$key] = round($change, 2);
            }
            
            return response()->json($comparison);
    }

    public function cartAbandonment()
    {
            // Simulation des données d'abandon de panier
            // À adapter selon votre système de tracking de panier
            $cartAnalytics = [
                'total_carts_created' => 1250, // Sessions avec ajout au panier
                'completed_orders' => 890, // Commandes finalisées
                'abandonment_rate' => 28.8, // (1250 - 890) / 1250 * 100
                'abandoned_value' => 850000, // Valeur des paniers abandonnés
                'abandonment_stages' => [
                    'cart' => 15, // Abandon au panier
                    'checkout' => 8, // Abandon au checkout
                    'payment' => 5.8, // Abandon au paiement
                ],
                'recovery_opportunities' => [
                    'email_campaigns' => 35, // % récupérable par email
                    'sms_campaigns' => 20, // % récupérable par SMS
                    'retargeting' => 25, // % récupérable par pub
                ],
                'top_abandoned_events' => Event::withCount(['orders as abandoned' => function($query) {
                        $query->where('payment_status', 'pending')
                              ->where('created_at', '<', now()->subHours(24));
                    }])
                    ->orderBy('abandoned', 'desc')
                    ->take(10)
                    ->get(['id', 'title', 'abandoned'])
            ];
            
            return response()->json($cartAnalytics);
    }

    public function salesHeatmap()
    {
            $heatmapData = collect(range(0, 23))->map(function($hour) {
                return [
                    'hour' => $hour,
                    'sales' => Order::where('payment_status', 'paid')
                        ->whereRaw('HOUR(created_at) = ?', [$hour])
                        ->whereBetween('created_at', [now()->subDays(30), now()])
                        ->count(),
                    'revenue' => Order::where('payment_status', 'paid')
                        ->whereRaw('HOUR(created_at) = ?', [$hour])
                        ->whereBetween('created_at', [now()->subDays(30), now()])
                        ->sum('total_amount')
                ];
            });
            
            return response()->json($heatmapData);
    }

    public function cohort()
    {
            $cohortData = collect();
            
            // Analyser les cohortes des 12 derniers mois
            for ($i = 11; $i >= 0; $i--) {
                $cohortMonth = now()->subMonths($i);
                $newUsers = User::whereYear('created_at', $cohortMonth->year)
                    ->whereMonth('created_at', $cohortMonth->month)
                    ->pluck('id');
                
                $cohortAnalysis = [
                    'month' => $cohortMonth->format('M Y'),
                    'new_users' => $newUsers->count(),
                    'retention' => []
                ];
                
                // Calculer la rétention pour chaque mois suivant
                for ($retentionMonth = 0; $retentionMonth <= min(6, now()->diffInMonths($cohortMonth)); $retentionMonth++) {
                    $targetMonth = $cohortMonth->copy()->addMonths($retentionMonth);
                    $activeUsers = Order::whereIn('user_id', $newUsers)
                        ->whereYear('created_at', $targetMonth->year)
                        ->whereMonth('created_at', $targetMonth->month)
                        ->distinct('user_id')
                        ->count();
                    
                    $retentionRate = $newUsers->count() > 0 ? ($activeUsers / $newUsers->count()) * 100 : 0;
                    $cohortAnalysis['retention'][$retentionMonth] = round($retentionRate, 1);
                }
                
                $cohortData->push($cohortAnalysis);
            }
            
            return response()->json($cohortData);
    }

    public function predictions()
    {
            // Simulation de prédictions ML
            // En production, vous intégreriez un vrai modèle ML
            $predictions = [
                'next_week' => [
                    'revenue' => Order::where('payment_status', 'paid')
                        ->whereBetween('created_at', [now()->subWeek(), now()])
                        ->sum('total_amount') * 1.08, // +8% prévu
                    'orders' => Order::whereBetween('created_at', [now()->subWeek(), now()])
                        ->count() * 1.05, // +5% prévu
                    'confidence' => 85 // % de confiance
                ],
                'next_month' => [
                    'revenue' => Order::where('payment_status', 'paid')
                        ->whereBetween('created_at', [now()->subMonth(), now()])
                        ->sum('total_amount') * 1.15, // +15% prévu
                    'orders' => Order::whereBetween('created_at', [now()->subMonth(), now()])
                        ->count() * 1.12, // +12% prévu
                    'confidence' => 72 // % de confiance
                ],
                'seasonal_trends' => [
                    'peak_months' => ['Décembre', 'Juillet', 'Août'],
                    'low_months' => ['Janvier', 'Février', 'Septembre'],
                    'growth_trend' => 'positive', // positive, negative, stable
                    'growth_rate' => 12.5 // % annuel
                ],
                'recommendations' => [
                    'Augmenter l\'inventaire pour les événements musicaux en décembre',
                    'Prévoir des promotions en janvier et février',
                    'Cibler les jeunes adultes (25-35 ans) pour augmenter les ventes'
                ]
            ];
            
            return response()->json($predictions);
    }
}
