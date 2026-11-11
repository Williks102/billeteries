@extends('layouts.admin')

@section('title', 'Événements à surveiller')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.events.index') }}">Événements</a></li>
    <li class="breadcrumb-item active">À surveiller</li>
@endsection

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Événements à surveiller"
        subtitle="Publiés sans billets, ou proches sans billet en vente"
        :back="route('admin.events.index')" />

    @if($eventsNeedingIntervention->isEmpty())
        <div class="alert alert-success">Aucun événement ne nécessite d'intervention.</div>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <caption class="visually-hidden">Événements nécessitant une intervention</caption>
                    <thead>
                        <tr>
                            <th scope="col">Événement</th>
                            <th scope="col" class="d-none d-md-table-cell">Promoteur</th>
                            <th scope="col">Date</th>
                            <th scope="col">Statut</th>
                            <th scope="col" class="text-end">Billets</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($eventsNeedingIntervention as $event)
                            <tr>
                                <td><a href="{{ route('admin.events.show', $event) }}">{{ $event->title }}</a></td>
                                <td class="d-none d-md-table-cell">{{ $event->promoteur?->name ?? '-' }}</td>
                                <td>{{ $event->event_date?->format('d/m/Y') ?? '-' }}</td>
                                <td><span class="badge bg-secondary">{{ $event->status }}</span></td>
                                <td class="text-end">{{ $event->ticketTypes->count() }} type(s)</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $eventsNeedingIntervention->links('pagination::bootstrap-5') }}</div>
    @endif
</div>
@endsection
