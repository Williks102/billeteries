@extends('layouts.admin')

@section('title', 'Modifier le billet ' . $ticket->ticket_code)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.tickets.index') }}">Billets</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.tickets.show', $ticket) }}">{{ $ticket->ticket_code }}</a></li>
    <li class="breadcrumb-item active">Modifier</li>
@endsection

@section('content')
@php
    $labels = ['available' => 'Disponible', 'reserved' => 'Réservé', 'sold' => 'Vendu', 'used' => 'Utilisé', 'cancelled' => 'Annulé'];
    $options = array_merge([$ticket->status], $transitions);
@endphp
<div class="admin-page">
    <x-admin.page-header :title="'Billet ' . $ticket->ticket_code" :back="route('admin.tickets.show', $ticket)" backLabel="Voir le billet" />

    @if($errors->any())
        <div class="alert alert-danger" role="alert">Le formulaire contient des erreurs : corrigez les champs signalés.</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h2 class="h5 mb-0">Résumé</h2></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Événement</dt><dd class="col-7">{{ $ticket->ticketType?->event?->title ?? '—' }}</dd>
                        <dt class="col-5">Type</dt><dd class="col-7">{{ $ticket->ticketType?->name ?? '—' }}</dd>
                        <dt class="col-5">Statut actuel</dt><dd class="col-7"><x-ticket-status-badge :status="$ticket->status" /></dd>
                        <dt class="col-5">Commande</dt><dd class="col-7">{{ $order ? ($order->order_number ?? $order->id) : '—' }}</dd>
                        <dt class="col-5">Paiement</dt><dd class="col-7">@if($order)<x-order-status-badge :order="$order" />@else — @endif</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h2 class="h5 mb-0">Statut du billet</h2></div>
                <div class="card-body">
                    @if($transitions === [])
                        <p class="mb-3">Aucun changement de statut possible : un billet utilisé est figé, et un billet annulé d'une commande non payée/remboursée ne peut pas être réactivé.</p>
                        <div class="action-bar mt-0">
                            <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn btn-primary">Retour au billet</a>
                        </div>
                    @else
                        <form action="{{ route('admin.tickets.update', $ticket) }}" method="POST" novalidate>
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label for="status" class="form-label">Statut</label>
                                <select id="status" name="status" required
                                        class="form-select @error('status') is-invalid @enderror"
                                        @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>
                                    @foreach($options as $value)
                                        <option value="{{ $value }}" @selected(old('status', $ticket->status) === $value)>{{ $labels[$value] ?? $value }}</option>
                                    @endforeach
                                </select>
                                <x-field-error name="status" />
                                <div class="form-text">« Utilisé » exige une commande payée. Un billet utilisé ne peut plus être annulé ni modifié.</div>
                            </div>
                            <div class="action-bar mt-0">
                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                                <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn btn-outline-secondary action-bar__cancel">Annuler</a>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
