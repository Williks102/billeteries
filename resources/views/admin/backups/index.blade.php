@extends('layouts.admin')

@section('title', 'Sauvegardes')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Sauvegardes</li>
@endsection

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Sauvegardes" subtitle="Où sont vos données et comment les protéger (page d'information)" />

    <section class="mb-4" aria-labelledby="bk-db">
        <h2 class="h5 mb-3" id="bk-db">Base de données</h2>
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4"><x-admin.stat-card label="Moteur" :value="$database['engine']" /></div>
            <div class="col-6 col-md-4"><x-admin.stat-card label="Version" :value="$database['version']" /></div>
            <div class="col-6 col-md-4"><x-admin.stat-card label="Taille" :value="$database['size']" /></div>
        </div>
        <div class="alert alert-info mb-0" role="note">
            <strong>La sauvegarde de la base est assurée par l'hébergeur de la base (TiDB Cloud), pas par cette application.</strong>
            Connectez-vous à la console de l'hébergeur pour vérifier la fréquence des sauvegardes et la durée de conservation
            prévues par votre offre, et pour savoir comment restaurer. Cette application ne crée ni ne restaure aucune sauvegarde.
        </div>
    </section>

    <section class="mb-4" aria-labelledby="bk-media">
        <h2 class="h5 mb-3" id="bk-media">Images</h2>
        <div class="card">
            <div class="card-body">
                <p class="mb-2">Disque actif pour les images : <strong>{{ $mediaDisk }}</strong>.</p>
                @if($mediaOffServer)
                    <p class="mb-0">Les images sont conservées sur le stockage distant (Cloudflare R2), hors du serveur de l'application : elles ne disparaissent pas à un redéploiement.</p>
                @else
                    <p class="mb-0">Les images sont stockées sur le serveur de l'application. Sur un hébergement à disque éphémère, elles peuvent être perdues à chaque déploiement : voir le guide de stockage des images pour activer le stockage distant.</p>
                @endif
            </div>
        </div>
    </section>

    <section class="mb-4" aria-labelledby="bk-exports">
        <h2 class="h5 mb-3" id="bk-exports">Exports de données (CSV)</h2>
        <div class="alert alert-warning" role="note">
            Ce sont des exports de consultation (fichiers CSV filtrés, mois en cours par défaut) : ils ne constituent
            <strong>ni une sauvegarde complète ni un moyen de restauration</strong>. Ils contiennent des données personnelles : conservez-les avec soin.
        </div>
        <div class="row g-2">
            @foreach($exports as $export)
                <div class="col-12 col-sm-6 col-lg-3">
                    <a href="{{ route($export['route']) }}" class="btn btn-outline-primary w-100">
                        <i class="fas {{ $export['icon'] }}" aria-hidden="true"></i> {{ $export['label'] }}
                    </a>
                </div>
            @endforeach
        </div>
    </section>

    <section aria-labelledby="bk-log">
        <h2 class="h5 mb-3" id="bk-log">Traçabilité</h2>
        <a href="{{ route('admin.activity.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-clipboard-list" aria-hidden="true"></i> Journal d'activité
        </a>
    </section>
</div>
@endsection
