@extends('emails.layout')

@section('title', 'Réinitialisation de votre mot de passe')

@section('header-title', '🔐 Réinitialisation de mot de passe')
@section('header-subtitle', 'Demande de changement de mot de passe')

@section('content')
    <h2>Bonjour,</h2>
    
    <p>Vous recevez cet email car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte ClicBillet CI.</p>

    <div class="reset-notice" style="background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center;">
        <h3 style="color: #856404; margin: 0 0 15px 0;">⏰ Lien temporaire</h3>
        <p style="margin: 0 0 10px 0; color: #856404;">
            Ce lien est valable pendant <strong>{{ $expireTime }} minutes</strong> seulement.
        </p>
        <p style="margin: 0; color: #856404;">
            Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.
        </p>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $resetUrl }}" class="btn" style="display: inline-block; background: #dc3545; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: bold;">
            🔐 Réinitialiser mon mot de passe
        </a>
    </div>

    <div class="security-info" style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h4>🛡️ Conseils de sécurité</h4>
        <ul style="margin: 0; padding-left: 20px;">
            <li><strong>Choisissez un mot de passe fort</strong> (8+ caractères, lettres et chiffres)</li>
            <li><strong>Ne partagez jamais</strong> votre mot de passe</li>
            <li><strong>Utilisez un mot de passe unique</strong> pour ce compte</li>
            <li><strong>Activez la validation en 2 étapes</strong> si disponible</li>
        </ul>
    </div>

    <div class="copy-link" style="background: #e9ecef; border: 1px solid #ced4da; padding: 15px; border-radius: 6px; margin: 20px 0;">
        <p style="margin: 0 0 10px 0;"><strong>Si le bouton ne fonctionne pas, copiez ce lien :</strong></p>
        <code style="word-break: break-all; background: white; padding: 5px; border-radius: 3px;">{{ $resetUrl }}</code>
    </div>

    <p>Si vous n'avez pas demandé cette réinitialisation, aucune action n'est requise de votre part.</p>
    
    <p><strong>L'équipe ClicBillet CI</strong></p>

    <hr style="border: 1px dashed #ddd; margin: 20px 0;">
    <p style="font-size: 12px; color: #666; text-align: center;">
        <em>Demande effectuée le {{ now()->format('d/m/Y à H:i') }} • Lien valable {{ $expireTime }} minutes</em>
    </p>
@endsection
