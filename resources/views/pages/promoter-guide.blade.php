@extends('layouts.app')

@section('title', 'Guide du promoteur')

@section('content')
<div class="container py-4" style="max-width:800px;">
    <h1 class="h3 mb-4">Guide du promoteur</h1>
    <p>Ce guide résume les étapes pour vendre des billets pour votre événement.</p>

    <h2 class="h5 mt-4">1. Créer votre compte</h2>
    <p>Inscrivez-vous puis complétez les informations de votre espace promoteur.</p>

    <h2 class="h5 mt-4">2. Publier un événement</h2>
    <p>Renseignez le titre, la date, le lieu et une image, puis publiez l'événement.</p>

    <h2 class="h5 mt-4">3. Configurer les billets</h2>
    <p>Créez un ou plusieurs types de billets avec leur prix en FCFA, la quantité disponible et le nombre maximum par commande.</p>

    <h2 class="h5 mt-4">4. Contrôler les billets à l'entrée</h2>
    <p>Utilisez le scanner de votre espace promoteur pour vérifier les QR codes des billets.</p>

    <h2 class="h5 mt-4">Commission</h2>
    <p>La commission de la plateforme est de {{ rtrim(rtrim(number_format(\App\Services\CommissionRate::default(), 2, ',', ' '), '0'), ',') }} % du montant des ventes.</p>

    <p class="mt-4">Une question ? <a href="{{ route('pages.support') }}">Contactez le support</a>.</p>
</div>
@endsection
