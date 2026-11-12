@extends('layouts.admin')

@section('title', 'Billets')

@section('breadcrumb')
    <li class="breadcrumb-item active">Billets</li>
@endsection

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Billets" subtitle="Billets vendus, utilisés, réservés et annulés de la plateforme">
        <a href="{{ route('admin.tickets.export', request()->query()) }}" class="btn btn-outline-secondary">
            <i class="fas fa-download" aria-hidden="true"></i> Exporter (CSV)
        </a>
    </x-admin.page-header>

    <x-period-filter :action="route('admin.tickets.index')" :period="$period" :keep="request()->only(['status', 'event', 'search'])" />
    <p class="text-muted small">La période s'applique à la date de vente de la commande ; elle ne s'applique pas aux billets « disponibles » (sans commande).</p>

    <x-stat-grid label="Chiffres des billets">
        <x-stat-card label="Billets vendus" :value="$summary['sold']" hint="Vendus et utilisés, commandes payées de la période" />
        <x-stat-card label="Billets scannés" :value="$summary['scanned']" hint="Parmi les billets vendus de la période" />
        <x-stat-card label="Taux d'utilisation" unit="%" :value="$summary['usage_rate']" hint="Scannés / vendus" />
        <x-stat-card label="Billets réservés" :value="$summary['reserved']" hint="Commandes en attente de paiement (instantané)" />
    </x-stat-grid>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.tickets.index') }}" novalidate>
                @foreach(request()->only(['period', 'start_date', 'end_date']) as $k => $v)
                    @if(is_scalar($v) && $v !== '')
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endif
                @endforeach
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="search">Code du billet</label>
                        <input id="search" type="text" name="search" maxlength="100" class="form-control @error('search') is-invalid @enderror"
                               placeholder="TKT-..." value="{{ request('search') }}">
                        <x-field-error name="search" />
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label" for="status">Statut</label>
                        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="">Tous les statuts</option>
                            @foreach($statuses as $st)
                                <option value="{{ $st }}" @selected(request('status') === $st)>{{ ['available' => 'Disponible', 'reserved' => 'Réservé', 'sold' => 'Vendu', 'used' => 'Utilisé', 'cancelled' => 'Annulé'][$st] }}</option>
                            @endforeach
                        </select>
                        <x-field-error name="status" />
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label" for="event">Événement</label>
                        <select id="event" name="event" class="form-select @error('event') is-invalid @enderror">
                            <option value="">Tous les événements</option>
                            @foreach($events as $ev)
                                <option value="{{ $ev->id }}" @selected((string) request('event') === (string) $ev->id)>{{ \Illuminate\Support\Str::limit($ev->title, 40) }}</option>
                            @endforeach
                        </select>
                        <x-field-error name="event" />
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search" aria-hidden="true"></i> Filtrer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h2 class="h5 mb-0">Liste des billets</h2>
            <span class="text-muted small">{{ $tickets->total() }} résultat(s) · ventes : {{ $period->label }}</span>
        </div>
        <div class="card-body p-0">
            @if($tickets->isNotEmpty())
                <div class="table-responsive">
                <table class="table table-hover table-cards mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Code</th>
                            <th scope="col">Événement / type</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Commande / acheteur</th>
                            <th scope="col">Vendu le</th>
                            <th scope="col">Scanné le</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tickets as $ticket)
                            @php
                                $order = $ticket->linkedOrder();
                                $sale = $order ? ($order->paid_at ?? $order->created_at) : null;
                            @endphp
                            <tr>
                                <td data-label="Code"><span class="fw-semibold font-monospace">{{ $ticket->ticket_code }}</span></td>
                                <td data-label="Événement">
                                    <div>{{ \Illuminate\Support\Str::limit($ticket->ticketType?->event?->title ?? 'Événement supprimé', 40) }}</div>
                                    <small class="text-muted">{{ $ticket->ticketType?->name ?? '—' }}</small>
                                </td>
                                <td data-label="Statut"><x-ticket-status-badge :status="$ticket->status" /></td>
                                <td data-label="Commande">
                                    @if($order)
                                        <a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number ?? $order->id }}</a><br>
                                        <small class="text-muted">{{ $order->user?->name ?? 'Invité' }} · {{ \App\Helpers\PrivacyHelper::maskEmail($order->user?->email ?? $order->billing_email) }}</small>
                                    @else
                                        <span class="text-muted">Aucune commande</span>
                                    @endif
                                </td>
                                <td data-label="Vendu le">
                                    @if($order && $order->payment_status === 'paid' && $sale)
                                        {{ $sale->format('d/m/Y H:i') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="Scanné le">
                                    @if($ticket->used_at)
                                        {{ $ticket->used_at->format('d/m/Y H:i') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="Actions">
                                    <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn btn-outline-primary btn-sm" style="min-height:44px;min-width:44px;display:inline-flex;align-items:center;justify-content:center;gap:.4rem" aria-label="Voir le billet {{ $ticket->ticket_code }}">
                                        <i class="fas fa-eye" aria-hidden="true"></i><span class="d-md-none">Voir</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            @else
                <p class="text-center text-muted py-5 mb-0"><i class="fas fa-ticket-alt me-2" aria-hidden="true"></i>Aucun billet ne correspond à ces filtres.</p>
            @endif
        </div>
        @if($tickets->hasPages())
            <div class="card-footer">{{ $tickets->links() }}</div>
        @endif
    </div>
</div>
@endsection
