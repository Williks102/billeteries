{{-- Bandeau « mode démo » : affiché seulement si $demoMode (variable partagée par AppServiceProvider).
     Fixé en bas de l'écran pour ne jamais masquer la navigation ; ne capte aucun clic. --}}
@if(!empty($demoMode))
    <style>
        body { padding-bottom: 44px; }
        .demo-banner {
            position: fixed; left: 0; right: 0; bottom: 0; z-index: 2000;
            padding: 8px 12px; min-height: 36px;
            background: #1f2937; color: #fff; text-align: center;
            font: 600 14px/1.3 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            border-top: 3px solid #f59e0b; pointer-events: none;
        }
    </style>
    <div class="demo-banner" role="status" aria-live="polite" data-demo-banner>
        MODE DÉMO : paiement simulé — aucun argent réel n'est débité
    </div>
@endif
