<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic QR Code</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .diagnostic-card {
            margin-bottom: 20px;
        }
        .status-ok {
            color: #28a745;
            font-weight: bold;
        }
        .status-error {
            color: #dc3545;
            font-weight: bold;
        }
        .qr-preview {
            max-width: 200px;
            border: 2px solid #dee2e6;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">Diagnostic QR Code</h1>
        
        <!-- Extensions PHP -->
        <div class="card diagnostic-card">
            <div class="card-header">
                <h3>Extensions PHP</h3>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Extension</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results['extensions'] as $ext => $status)
                        <tr>
                            <td>{{ $ext }}</td>
                            <td class="{{ $status ? 'status-ok' : 'status-error' }}">
                                {{ $status ? '✅ Installée' : '❌ Non installée' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Packages -->
        <div class="card diagnostic-card">
            <div class="card-header">
                <h3>Packages Composer</h3>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Package</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results['packages'] as $package => $status)
                        <tr>
                            <td>{{ $package }}</td>
                            <td class="{{ $status ? 'status-ok' : 'status-error' }}">
                                {{ $status ? '✅ Installé' : '❌ Non installé' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Test du service -->
        <div class="card diagnostic-card">
            <div class="card-header">
                <h3>Test du Service QRCodeService</h3>
            </div>
            <div class="card-body">
                @if(isset($results['service_test']['success']))
                    <p class="{{ $results['service_test']['success'] ? 'status-ok' : 'status-error' }}">
                        {{ $results['service_test']['success'] ? '✅ Service fonctionnel' : '❌ Service non fonctionnel' }}
                    </p>
                    
                    @if(isset($results['service_test']['methods']))
                    <h5>Méthodes testées:</h5>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Méthode</th>
                                <th>Résultat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results['service_test']['methods'] as $method => $works)
                            <tr>
                                <td>{{ $method }}</td>
                                <td class="{{ $works ? 'status-ok' : 'status-error' }}">
                                    {{ $works ? '✅ Fonctionne' : '❌ Échoue' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    
                    <p><strong>Méthodes fonctionnelles:</strong> {{ $results['service_test']['working_count'] }}</p>
                    @endif
                @else
                    <p class="status-error">❌ Erreur: {{ $results['service_test']['error'] ?? 'Erreur inconnue' }}</p>
                @endif
            </div>
        </div>
        
        <!-- Test avec un vrai ticket -->
        @if(isset($results['real_ticket_test']))
        <div class="card diagnostic-card">
            <div class="card-header">
                <h3>Test avec un Ticket Réel</h3>
            </div>
            <div class="card-body">
                @if(isset($results['real_ticket_test']['error']))
                    <p class="status-error">❌ Erreur: {{ $results['real_ticket_test']['error'] }}</p>
                @else
                    <p><strong>Code ticket:</strong> {{ $results['real_ticket_test']['ticket_code'] }}</p>
                    <p class="{{ $results['real_ticket_test']['qr_generated'] ? 'status-ok' : 'status-error' }}">
                        <strong>QR généré:</strong> {{ $results['real_ticket_test']['qr_generated'] ? '✅ Oui' : '❌ Non' }}
                    </p>
                    @if($results['real_ticket_test']['qr_generated'])
                        <p><strong>Taille données:</strong> {{ $results['real_ticket_test']['qr_length'] }} caractères</p>
                        <p><strong>Aperçu:</strong></p>
                        <div class="qr-preview">
                            <img src="{{ $results['real_ticket_test']['qr_preview'] }}" alt="QR Code Test" style="max-width: 100%;">
                        </div>
                    @endif
                @endif
            </div>
        </div>
        @endif
        
        <!-- Test de stockage -->
        <div class="card diagnostic-card">
            <div class="card-header">
                <h3>Test de Stockage</h3>
            </div>
            <div class="card-body">
                <p class="{{ $results['storage_test']['writable'] ? 'status-ok' : 'status-error' }}">
                    <strong>Écriture possible:</strong> {{ $results['storage_test']['writable'] ? '✅ Oui' : '❌ Non' }}
                </p>
                @if(isset($results['storage_test']['path']))
                    <p><strong>Chemin:</strong> <code>{{ $results['storage_test']['path'] }}</code></p>
                @endif
                @if(isset($results['storage_test']['error']))
                    <p class="status-error">❌ Erreur: {{ $results['storage_test']['error'] }}</p>
                @endif
            </div>
        </div>
        
        <!-- Connectivité API -->
        <div class="card diagnostic-card">
            <div class="card-header">
                <h3>Connectivité APIs Externes</h3>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>API</th>
                            <th>Accessible</th>
                            <th>Status HTTP</th>
                            <th>Taille réponse</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results['api_connectivity'] as $api => $data)
                        <tr>
                            <td>{{ $api }}</td>
                            <td class="{{ $data['accessible'] ? 'status-ok' : 'status-error' }}">
                                {{ $data['accessible'] ? '✅ Oui' : '❌ Non' }}
                            </td>
                            <td>{{ $data['status'] ?? 'N/A' }}</td>
                            <td>{{ isset($data['size']) ? $data['size'] . ' bytes' : 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recommandations -->
        <div class="card diagnostic-card">
            <div class="card-header bg-info text-white">
                <h3>Recommandations</h3>
            </div>
            <div class="card-body">
                <h5>Pour résoudre les problèmes de QR Code:</h5>
                <ol>
                    <li><strong>Installer SimpleSoftwareIO/QrCode:</strong>
                        <pre><code>composer require simplesoftwareio/simple-qrcode</code></pre>
                    </li>
                    <li><strong>Vérifier les permissions du dossier storage:</strong>
                        <pre><code>chmod -R 775 storage/app/public
php artisan storage:link</code></pre>
                    </li>
                    <li><strong>Si derrière un proxy, configurer HTTP client dans .env:</strong>
                        <pre><code>HTTP_PROXY=http://your-proxy:port
HTTPS_PROXY=http://your-proxy:port</code></pre>
                    </li>
                    <li><strong>Vider les caches:</strong>
                        <pre><code>php artisan cache:clear
php artisan config:clear
php artisan view:clear</code></pre>
                    </li>
                </ol>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="{{ route('acheteur.dashboard') }}" class="btn btn-primary">Retour au Dashboard</a>
        </div>
    </div>
</body>
</html>