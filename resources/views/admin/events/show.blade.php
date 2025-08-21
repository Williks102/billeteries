@extends('layouts.admin')

@section('content')
<div class="container">
    <h1 class="mb-4">Détail de l'Événement : {{ $event->title }}</h1>

    <div class="card p-4 mb-4">
        <h5 class="mb-3">Informations générales</h5>
        <ul class="list-group list-group-flush">
            <li class="list-group-item"><strong>Promoteur :</strong> {{ $event->promoteur->name }}</li>
            <li class="list-group-item"><strong>Date :</strong> {{ $event->start_date->format('d/m/Y') }}</li>
            <li class="list-group-item"><strong>Lieu :</strong> {{ $event->location }}</li>
            <li class="list-group-item"><strong>Statut :</strong> {{ ucfirst($event->status) }}</li>
        </ul>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Revenus générés</h5>
                    <p class="card-text fs-4">{{ \App\Helpers\CurrencyHelper::formatFCFA($totalRevenue) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Billets vendus</h5>
                    <p class="card-text fs-4">{{ $totalTickets }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Commandes</h5>
                    <p class="card-text fs-4">{{ $orders->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <h4>Commandes associées</h4>
    <div class="card p-3">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Email</th>
                    <th>Total</th>
                    <th>Billets</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                <tr>
                    <td>{{ $order->user->name ?? 'N/A' }}</td>
                    <td>{{ $order->user->email ?? '-' }}</td>
                    <td>{{ \App\Helpers\CurrencyHelper::formatFCFA($order->total_amount) }}</td>
                    <td>{{ $order->tickets->count() }}</td>
                    <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
