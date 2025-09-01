<?php
namespace App\Mail;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $token;
    public string $email;

    public function __construct(string $token, string $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔐 Réinitialisation de votre mot de passe - ClicBillet CI',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-password',
            with: [
                'token' => $this->token,
                'email' => $this->email,
                'resetUrl' => url('password/reset', $this->token) . '?email=' . urlencode($this->email),
                'expireTime' => config('auth.passwords.users.expire', 60) // minutes
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
