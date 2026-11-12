@props(['guest' => false])
{{-- Délai de réservation annoncé avant la validation (valeur configurée, jamais en dur). Le stock n'est réservé
     qu'à la validation de la commande : aucun décompte n'est affiché avant. --}}
<div class="reservation-notice" role="note">
    <i class="fas fa-clock" aria-hidden="true"></i>
    <p>Après validation, vos places sont réservées pendant <strong>{{ \App\Helpers\DurationHelper::reservationLabel($guest) }}</strong>. Sans paiement avant cette échéance, la réservation est libérée.</p>
</div>
