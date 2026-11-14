@extends('layouts.admin')

@section('title', 'Modifier la commande ' . $order->order_number)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.orders.index') }}">Commandes</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Modifier</li>
@endsection

@section('content')
@php
    $labels = [
        'pending' => 'En attente de paiement',
        'processing' => 'En cours de traitement',
        'failed' => 'Échec / annulée',
        'refunded' => 'Remboursée',
    ];
    // Statuts proposés : le statut actuel (s'il est accepté par le contrôleur) + les transitions autorisées,
    // sans "paid" : le paiement se confirme uniquement via le formulaire dédié (OrderPaymentService::confirmPayment).
    $accepted = ['pending', 'failed', 'refunded'];
    $options = [];
    if (in_array($order->payment_status, $accepted, true)) {
        $options[] = $order->payment_status;
    }
    foreach (\App\Models\Order::STATUS_TRANSITIONS[$order->payment_status] ?? [] as $to) {
        if ($to !== 'paid' && in_array($to, $accepted, true) && ! in_array($to, $options, true)) {
            $options[] = $to;
        }
    }
    $canConfirm = $order->canTransitionTo('paid');
@endphp
<div class="admin-page">
    <x-admin.page-header :title="'Commande ' . $order->order_number"
        :back="route('admin.orders.show', $order)" backLabel="Voir la commande" />

    @if($errors->any())
        <div class="alert alert-danger" role="alert">Le formulaire contient des erreurs : corrigez les champs signalés.</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h2 class="h5 mb-0">Résumé</h2></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Client</dt><dd class="col-7">{{ $order->user?->name ?? '-' }}</dd>
                        <dt class="col-5">Événement</dt><dd class="col-7">{{ $order->event?->title ?? '-' }}</dd>
                        <dt class="col-5">Montant</dt><dd class="col-7">{{ \App\Helpers\CurrencyHelper::formatFCFA($order->total_amount) }}</dd>
                        <dt class="col-5">Statut</dt><dd class="col-7"><x-order-status-badge :order="$order" /></dd>
                        <dt class="col-5">Créée le</dt><dd class="col-7">{{ $order->created_at->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header"><h2 class="h5 mb-0">Statut et notes</h2></div>
                <div class="card-body">
                    <form action="{{ route('admin.orders.update', $order) }}" method="POST" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="payment_status" class="form-label">Statut du paiement</label>
                            <select id="payment_status" name="payment_status" required
                                    class="form-select @error('payment_status') is-invalid @enderror"
                                    @error('payment_status') aria-invalid="true" aria-describedby="payment_status-error" @enderror>
                                @foreach($options as $value)
                                    <option value="{{ $value }}" @selected(old('payment_status', $order->payment_status) === $value)>{{ $labels[$value] ?? $value }}</option>
                                @endforeach
                            </select>
                            @error('payment_status') <div class="invalid-feedback" id="payment_status-error">{{ $message }}</div> @enderror
                            <div class="form-text">Passer en « échec » libère le stock réservé. Pour marquer la commande payée, utilisez la confirmation de paiement ci-dessous.</div>
                        </div>

                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">Notes internes</label>
                            <textarea id="admin_notes" name="admin_notes" rows="4" maxlength="1000"
                                      class="form-control @error('admin_notes') is-invalid @enderror">{{ old('admin_notes', $order->admin_notes) }}</textarea>
                            @error('admin_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="action-bar mt-0">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>

            @if($canConfirm)
                <div class="card">
                    <div class="card-header"><h2 class="h5 mb-0">Confirmer le paiement</h2></div>
                    <div class="card-body">
                        <p class="small text-muted">À utiliser quand le paiement manuel (virement, Mobile Money, espèces) a été reçu. Les billets sont alors émis.</p>
                        <form action="{{ route('admin.orders.confirm-payment', $order) }}" method="POST" novalidate>
                            @csrf
                            <div class="mb-3">
                                <label for="payment_reference" class="form-label">Référence du paiement (facultatif)</label>
                                <input type="text" id="payment_reference" name="payment_reference" maxlength="100"
                                       class="form-control @error('payment_reference') is-invalid @enderror"
                                       value="{{ old('payment_reference') }}">
                                @error('payment_reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="action-bar mt-0">
                                <button type="submit" class="btn btn-success"
                                        data-confirm="Confirmer la réception du paiement de {{ \App\Helpers\CurrencyHelper::formatFCFA($order->total_amount) }} ?">
                                    <i class="fas fa-check-circle" aria-hidden="true"></i> Confirmer le paiement
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
