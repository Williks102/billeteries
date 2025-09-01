<?php

// ============================================
// 1. Créer app/Mail/WelcomeEmail.php
// ============================================

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎉 Bienvenue sur ClicBillet CI - Votre compte est activé !',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: [
                'user' => $this->user,
                'userName' => $this->user->name,
                'userRole' => $this->user->role,
                'dashboardUrl' => $this->getDashboardUrl(),
                'isPromoteur' => $this->user->role === 'promoteur',
                'isAcheteur' => $this->user->role === 'acheteur'
            ]
        );
    }

    private function getDashboardUrl(): string
    {
        return match($this->user->role) {
            'admin' => route('admin.dashboard'),
            'promoteur' => route('promoteur.dashboard'),
            'acheteur' => route('acheteur.dashboard'),
            default => route('home')
        };
    }

    public function attachments(): array
    {
        return [];
    }
}
