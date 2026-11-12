{{-- Chiffres de l'acheteur (BuyerStats, Scope::buyer). Aucun calcul ici : tout vient du service. Valeur absente => « — ». --}}
@php $stats = $stats ?? []; @endphp
<p class="text-muted small mb-2" id="acheteur-stats-scope">Chiffres cumulés depuis l'ouverture de votre compte (toutes dates).</p>
<x-stat-grid label="Mes chiffres" class="mb-4">
    <x-stat-card label="Commandes" :value="$stats['orders_total'] ?? null" :hint="isset($stats['orders_failed']) ? 'Dont '.$stats['orders_failed'].' échouée(s)' : null" />
    <x-stat-card label="Commandes payées" :value="$stats['orders_paid'] ?? null" hint="Billets émis" />
    <x-stat-card label="En attente de paiement" :value="$stats['orders_awaiting'] ?? null" hint="Pas encore confirmées" />
    <x-stat-card label="Remboursées" :value="$stats['orders_refunded'] ?? null" hint="Exclues du montant dépensé" />
    <x-stat-card label="Billets achetés" :value="$stats['tickets'] ?? null" hint="Payés ou utilisés" />
    <x-stat-card label="Dépensé" unit="FCFA" :value="$stats['spent'] ?? null" hint="Commandes payées, hors remboursements" />
    <x-stat-card label="Événements à venir" :value="$stats['events_upcoming'] ?? null" hint="Aujourd'hui compris" />
    <x-stat-card label="Événements passés" :value="$stats['events_past'] ?? null" hint="Commandes payées" />
</x-stat-grid>
