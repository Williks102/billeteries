@extends('layouts.app')

@section('title', 'Mentions légales')

@section('content')
<div class="container py-4" style="max-width:800px;">
    <h1 class="h3 mb-4">Mentions légales</h1>
    <div class="alert alert-warning" role="note">Cette page est un modèle : les informations ci-dessous doivent être complétées par l'éditeur du site.</div>

    <h2 class="h5 mt-4">Éditeur du site</h2>
    <ul>
        <li>Dénomination sociale : [À COMPLÉTER]</li>
        <li>Forme juridique et capital : [À COMPLÉTER]</li>
        <li>Numéro d'immatriculation (RCCM) : [À COMPLÉTER]</li>
        <li>Siège social : [À COMPLÉTER]</li>
        <li>Responsable de la publication : [À COMPLÉTER]</li>
    </ul>

    <h2 class="h5 mt-4">Contact</h2>
    <p>Pour toute question, utilisez la <a href="{{ route('pages.contact') }}">page de contact</a>.</p>

    <h2 class="h5 mt-4">Hébergement</h2>
    <p>Hébergeur : [À COMPLÉTER]</p>

    <h2 class="h5 mt-4">Documents associés</h2>
    <ul>
        <li><a href="{{ route('pages.terms') }}">Conditions générales de vente</a></li>
        <li><a href="{{ route('pages.privacy') }}">Politique de confidentialité</a></li>
    </ul>
</div>
@endsection
