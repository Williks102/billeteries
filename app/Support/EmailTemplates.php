<?php

namespace App\Support;

/**
 * Modèles d'e-mails réels de l'application : un par classe Mailable de app/Mail.
 * Liste tenue à la main ; tests/Feature/AdminSettingsTabsTest la compare aux fichiers de app/Mail
 * pour qu'elle ne dérive plus (ajouter un Mailable sans l'ajouter ici fait échouer le test).
 */
final class EmailTemplates
{
    /** @var array<class-string, array{label: string, audience: string}> */
    private const TEMPLATES = [
        \App\Mail\OrderConfirmation::class => ['label' => 'Confirmation de commande', 'audience' => 'Acheteur'],
        \App\Mail\ManualPaymentInstructions::class => ['label' => 'Instructions de paiement manuel', 'audience' => 'Acheteur'],
        \App\Mail\PaymentConfirmation::class => ['label' => 'Confirmation de paiement et billets', 'audience' => 'Acheteur'],
        \App\Mail\WelcomeEmail::class => ['label' => 'Bienvenue (compte activé)', 'audience' => 'Nouvel utilisateur'],
        \App\Mail\ResetPasswordMail::class => ['label' => 'Réinitialisation du mot de passe', 'audience' => 'Utilisateur'],
        \App\Mail\PromoteurNewSale::class => ['label' => 'Nouvelle vente', 'audience' => 'Promoteur'],
        \App\Mail\AdminNewOrder::class => ['label' => 'Nouvelle commande', 'audience' => 'Administrateurs'],
    ];

    /** @return array<class-string, array{label: string, audience: string}> */
    public static function all(): array
    {
        return self::TEMPLATES;
    }
}
