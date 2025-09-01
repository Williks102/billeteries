{{-- resources/views/emails/welcome.blade.php --}}

@extends('emails.layout')

@section('title', 'Bienvenue sur ClicBillet CI !')

@section('header-title', '🎉 Bienvenue sur ClicBillet CI !')
@section('header-subtitle', 'Votre compte ' . ucfirst($userRole) . ' a été créé avec succès')

@section('content')
    <h2>Bonjour {{ $userName }} !</h2>
    
    <p>Félicitations ! Votre compte <strong>{{ ucfirst($userRole) }}</strong> sur ClicBillet CI a été créé avec succès.</p>

    @if($isPromoteur)
        {{-- Message spécifique aux promoteurs --}}
        <div class="role-welcome" style="background: #e8f5e8; border: 2px solid #28a745; padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3 style="color: #28a745; margin: 0 0 15px 0;">🎭 Espace Promoteur activé</h3>
            <p style="margin: 0 0 10px 0;"><strong>Vous pouvez maintenant :</strong></p>
            <ul style="margin: 0; padding-left: 20px;">
                <li>🎪 <strong>Créer vos événements</strong> en quelques clics</li>
                <li>🎫 <strong>Configurer vos billets</strong> avec différents types et prix</li>
                <li>💰 <strong>Vendre en ligne</strong> et gérer les commandes</li>
                <li>📱 <strong>Scanner les QR codes</strong> à l'entrée</li>
                <li>📊 <strong>Suivre vos statistiques</strong> de vente en temps réel</li>
                <li>💳 <strong>Recevoir vos paiements</strong> après commission</li>
            </ul>
        </div>

        <div class="quick-actions" style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <h4 style="color: #856404; margin: 0 0 10px 0;">🚀 Premiers pas recommandés</h4>
            <ol style="color: #856404; margin: 0; padding-left: 20px;">
                <li><strong>Complétez votre profil</strong> avec photo et description</li>
                <li><strong>Créez votre premier événement</strong> test</li>
                <li><strong>Configurez vos modes de paiement</strong></li>
                <li><strong>Testez le processus</strong> d'achat de bout en bout</li>
            </ol>
        </div>

    @elseif($isAcheteur)
        {{-- Message spécifique aux acheteurs --}}
        <div class="role-welcome" style="background: #e3f2fd; border: 2px solid #2196f3; padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3 style="color: #2196f3; margin: 0 0 15px 0;">🎫 Espace Acheteur activé</h3>
            <p style="margin: 0 0 10px 0;"><strong>Vous pouvez maintenant :</strong></p>
            <ul style="margin: 0; padding-left: 20px;">
                <li>🔍 <strong>Découvrir les événements</strong> près de chez vous</li>
                <li>🛒 <strong>Acheter vos billets</strong> en ligne facilement</li>
                <li>📱 <strong>Recevoir vos billets PDF</strong> par email</li>
                <li>💳 <strong>Payer en sécurité</strong> par Orange Money, MTN, etc.</li>
                <li>📊 <strong>Gérer vos commandes</strong> dans votre espace</li>
                <li>⭐ <strong>Suivre vos événements favoris</strong></li>
            </ul>
        </div>

        <div class="quick-actions" style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <h4 style="color: #495057; margin: 0 0 10px 0;">🎯 Découvrez dès maintenant</h4>
            <p style="margin: 0; color: #6c757d;">
                Parcourez notre catalogue d'événements et trouvez votre prochain concert, 
                spectacle ou conférence à ne pas manquer !
            </p>
        </div>
    @endif

    <div class="account-details" style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3>📋 Détails de votre compte</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #dee2e6;">
                <td style="padding: 8px 0; font-weight: bold;">Email :</td>
                <td style="padding: 8px 0;">{{ $user->email }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #dee2e6;">
                <td style="padding: 8px 0; font-weight: bold;">Type de compte :</td>
                <td style="padding: 8px 0;">{{ ucfirst($userRole) }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #dee2e6;">
                <td style="padding: 8px 0; font-weight: bold;">Date d'inscription :</td>
                <td style="padding: 8px 0;">{{ $user->created_at->format('d/m/Y à H:i') }}</td>
            </tr>
        </table>
    </div>

    {{-- Accès dashboard --}}
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $dashboardUrl }}" class="btn" style="display: inline-block; background: #FF6B35; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: bold;">
            @if($isPromoteur)
                🎭 Accéder à mon Espace Promoteur
            @elseif($isAcheteur)
                🎫 Accéder à mon Espace Personnel
            @else
                📊 Accéder à mon Dashboard
            @endif
        </a>
    </div>

    {{-- Informations importantes --}}
    <div class="important-info" style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; margin: 20px 0;">
        <h4 style="color: #856404; margin: 0 0 10px 0;">ℹ️ Informations importantes</h4>
        <ul style="color: #856404; margin: 0; padding-left: 20px;">
            <li><strong>Sécurité :</strong> Ne partagez jamais votre mot de passe</li>
            <li><strong>Support :</strong> Notre équipe est disponible pour vous aider</li>
            <li><strong>Updates :</strong> Nous vous tiendrons au courant des nouveautés</li>
        </ul>
    </div>

    {{-- Support --}}
    <div style="text-align: center; margin: 20px 0;">
        <h4>Besoin d'aide ?</h4>
        <p>Notre équipe support est là pour vous accompagner</p>
        <p>
            📧 <a href="mailto:support@clicbillet.com">support@clicbillet.com</a> • 
            📞 +225 07 02 49 02 77 • 
            🌐 <a href="https://www.clicbillet.com">www.clicbillet.com</a>
        </p>
    </div>

    <p>Bienvenue dans la famille ClicBillet CI !</p>
    
    <p><strong>L'équipe ClicBillet CI</strong></p>

    <hr style="border: 1px dashed #ddd; margin: 20px 0;">
    <p style="font-size: 12px; color: #666; text-align: center;">
        <em>Email envoyé automatiquement le {{ now()->format('d/m/Y à H:i') }}</em>
    </p>
@endsection