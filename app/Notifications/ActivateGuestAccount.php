<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail d'activation d'un compte créé par un achat sans compte (invité).
 * Le lien est le lien de réinitialisation du broker (jeton à usage unique, même expiration) : l'ouvrir prouve la
 * possession de l'adresse. Aucune autre donnée (ni billets, ni montants) dans le message.
 */
class ActivateGuestAccount extends Notification
{
    public function __construct(public string $token)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        // Toujours sur APP_URL (jamais sur le Host de la requête : voir App\Support\AuthLinks)
        $url = \App\Support\AuthLinks::resetUrl($this->token, $notifiable->getEmailForPasswordReset());
        $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject('Activez votre compte pour retrouver vos billets')
            ->greeting('Bonjour,')
            ->line('Vous avez déjà acheté sans créer de compte avec cette adresse e-mail. Activez votre compte pour retrouver vos billets : vous choisirez votre mot de passe sur la page qui s\'ouvre.')
            ->action('Activer mon compte', $url)
            ->line("Ce lien est valable {$minutes} minutes et ne peut être utilisé qu'une seule fois.")
            ->line("Si ce n'est pas vous, ignorez ce message : rien ne sera modifié.");
    }
}
