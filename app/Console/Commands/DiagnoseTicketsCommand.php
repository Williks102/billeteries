<?php

// app/Console/Commands/DiagnoseTicketsCommand.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\Order;
use App\Services\QRCodeService;

class DiagnoseTicketsCommand extends Command
{
    protected $signature = 'tickets:diagnose {ticket_code? : Code du ticket à diagnostiquer} {--fix : Corriger automatiquement les problèmes}';
    protected $description = 'Diagnostiquer les problèmes de tickets et QR codes';

    public function handle()
    {
        $ticketCode = $this->argument('ticket_code');
        $fixMode = $this->option('fix');

        if ($ticketCode) {
            $this->diagnoseSingleTicket($ticketCode, $fixMode);
        } else {
            $this->diagnoseAllTickets($fixMode);
        }
    }

    protected function diagnoseSingleTicket($ticketCode, $fixMode = false)
    {
        $this->info("🔍 Diagnostic du ticket : {$ticketCode}");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $ticket = Ticket::where('ticket_code', $ticketCode)
            ->with(['ticketType.event', 'orderItem.order.user'])
            ->first();

        if (!$ticket) {
            $this->error("❌ Ticket non trouvé !");
            return;
        }

        // Infos de base
        $this->line("📋 Informations de base :");
        $this->line("  • ID: {$ticket->id}");
        $this->line("  • Code: {$ticket->ticket_code}");
        $this->line("  • Statut: {$ticket->status}");
        $this->line("  • Créé: {$ticket->created_at}");

        // QR Code
        $this->line("\n🔗 QR Code :");
        $this->line("  • URL stockée: " . ($ticket->qr_code ?? 'NON DÉFINIE'));
        $this->line("  • Chemin fichier: " . ($ticket->qr_code_path ?? 'NON DÉFINI'));

        // Vérifier URL de vérification
        $verifyUrl = url("/verify-ticket/{$ticket->ticket_code}");
        $this->line("  • URL vérification: {$verifyUrl}");

        // Test de génération QR
        $this->line("\n🧪 Test génération QR :");
        try {
            $qrService = app(\App\Services\QRCodeService::class);
            $qrBase64 = $qrService->generateTicketQRBase64($ticket);

            if ($qrBase64) {
                $this->line("  ✅ QR généré avec succès (" . strlen($qrBase64) . " caractères)");
                
                // Corriger automatiquement si demandé
                if ($fixMode && !$ticket->qr_code) {
                    $ticket->update(['qr_code' => $qrBase64]);
                    $this->info("  🔧 QR code sauvegardé dans la base de données");
                }
            } else {
                $this->line("  ❌ Échec génération QR");
            }
        } catch (\Exception $e) {
            $this->line("  ❌ Erreur: " . $e->getMessage());
        }

        // Validation
        $this->line("\n✅ Validation :");
        $isValid = $this->isTicketValidForUse($ticket);
        $this->line("  • Valide pour utilisation: " . ($isValid ? 'OUI' : 'NON'));

        // Info commande
        $order = $this->getMainOrder($ticket);
        if ($order) {
            $this->line("\n📦 Commande :");
            $this->line("  • Numéro: " . ($order->order_number ?? $order->id));
            $this->line("  • Statut paiement: {$order->payment_status}");
            $this->line("  • Client: {$order->user->name}");
        } else {
            $this->line("\n❌ Aucune commande associée !");
        }

        // Événement
        if ($ticket->ticketType && $ticket->ticketType->event) {
            $event = $ticket->ticketType->event;
            $this->line("\n🎭 Événement :");
            $this->line("  • Titre: {$event->title}");
            $this->line("  • Date: {$event->event_date}");
        } else {
            $this->line("\n❌ Aucun événement associé !");
        }
    }

    protected function diagnoseAllTickets($fixMode = false)
    {
        $this->info("🔍 Diagnostic global des tickets");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $stats = [
            'total' => Ticket::count(),
            'sold' => Ticket::where('status', 'sold')->count(),
            'used' => Ticket::where('status', 'used')->count(),
            'available' => Ticket::where('status', 'available')->count(),
            'cancelled' => Ticket::where('status', 'cancelled')->count(),
            'with_qr' => Ticket::whereNotNull('qr_code')->count(),
            'without_qr' => Ticket::whereNull('qr_code')->count(),
        ];

        $this->line("📊 Statistiques :");
        foreach ($stats as $key => $value) {
            $this->line("  • " . ucfirst(str_replace('_', ' ', $key)) . ": {$value}");
        }

        // Tickets problématiques
        $problemTickets = Ticket::where('status', 'sold')
            ->whereNull('qr_code')
            ->limit(10)
            ->get();

        if ($problemTickets->count() > 0) {
            $this->warn("\n⚠️ Tickets vendus sans QR code :");
            foreach ($problemTickets as $ticket) {
                $this->line("  • {$ticket->ticket_code}");
            }

            if ($fixMode || $this->confirm('Générer les QR codes manquants ?')) {
                $this->generateMissingQRCodes($problemTickets);
            }
        } else {
            $this->info("\n✅ Tous les tickets vendus ont un QR code !");
        }

        // Vérifier les doublons de codes
        $duplicates = Ticket::select('ticket_code')
            ->whereNotNull('ticket_code')
            ->groupBy('ticket_code')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicates > 0) {
            $this->warn("\n⚠️ {$duplicates} code(s) de ticket en doublon détectés !");
            if ($fixMode) {
                $this->fixDuplicateCodes();
            }
        }

        // Vérifier les billets sans code
        $withoutCode = Ticket::whereNull('ticket_code')->orWhere('ticket_code', '')->count();
        if ($withoutCode > 0) {
            $this->warn("\n⚠️ {$withoutCode} ticket(s) sans code !");
            if ($fixMode) {
                $this->fixTicketsWithoutCode();
            }
        }
    }

    protected function generateMissingQRCodes($tickets)
    {
        $this->info("🔧 Génération des QR codes manquants...");
        $qrService = app(\App\Services\QRCodeService::class);

        foreach ($tickets as $ticket) {
            try {
                $qrBase64 = $qrService->generateTicketQRBase64($ticket);
                if ($qrBase64) {
                    $ticket->update(['qr_code' => $qrBase64]);
                    $this->line("  ✅ {$ticket->ticket_code}");
                } else {
                    $this->line("  ❌ {$ticket->ticket_code}: Échec génération");
                }
            } catch (\Exception $e) {
                $this->line("  ❌ {$ticket->ticket_code}: " . $e->getMessage());
            }
        }

        $this->info("✅ Génération terminée");
    }

    protected function fixDuplicateCodes()
    {
        $duplicates = Ticket::select('ticket_code')
            ->whereNotNull('ticket_code')
            ->groupBy('ticket_code')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('ticket_code');

        $fixed = 0;
        foreach ($duplicates as $duplicateCode) {
            $tickets = Ticket::where('ticket_code', $duplicateCode)->get();
            // Garder le premier, changer les autres
            foreach ($tickets->skip(1) as $ticket) {
                $ticket->update([
                    'ticket_code' => $this->generateUniqueTicketCode()
                ]);
                $fixed++;
            }
        }

        $this->info("🔧 {$fixed} doublon(s) corrigés");
    }

    protected function fixTicketsWithoutCode()
    {
        $tickets = Ticket::whereNull('ticket_code')->orWhere('ticket_code', '')->get();

        foreach ($tickets as $ticket) {
            $ticket->update([
                'ticket_code' => $this->generateUniqueTicketCode()
            ]);
        }

        $this->info("🔧 {$tickets->count()} code(s) de billet générés");
    }

    protected function generateUniqueTicketCode()
    {
        do {
            $code = 'TKT-' . strtoupper(uniqid());
        } while (Ticket::where('ticket_code', $code)->exists());

        return $code;
    }

    protected function isTicketValidForUse($ticket)
    {
        return $ticket->status === 'sold' && 
               $ticket->ticketType && 
               $ticket->ticketType->event &&
               $ticket->ticketType->event->event_date >= now()->toDateString();
    }

    protected function getMainOrder($ticket)
    {
        $order = $ticket->linkedOrder();
        $order?->loadMissing('user');

        return $order;
    }
}