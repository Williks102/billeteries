<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\ProjectCleanupCommand;

// Commande inspire existante
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ========== COMMANDES DE NETTOYAGE DU PROJET ==========

// Commande principale de nettoyage
Artisan::command('project:cleanup {--dry-run : Simulation sans modifications} {--tickets : Nettoyer seulement les tickets} {--qr : Nettoyer seulement les QR codes} {--cache : Nettoyer seulement le cache}', function () {
    $dryRun = $this->option('dry-run');
    $ticketsOnly = $this->option('tickets');
    $qrOnly = $this->option('qr');
    $cacheOnly = $this->option('cache');
    
    $this->info('🧹 Nettoyage du projet - ' . ($dryRun ? 'MODE SIMULATION' : 'MODE RÉEL'));
    $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    
    $stats = [
        'tickets_fixed' => 0,
        'qr_generated' => 0,
        'qr_cleaned' => 0,
        'cache_cleared' => 0,
        'duplicates_found' => 0
    ];
    
    // Nettoyage des tickets
    if (!$qrOnly && !$cacheOnly) {
        $this->info("\n🎫 Nettoyage des tickets");
        $this->line("─────────────────────────────────────────");
        
        // Tickets avec des statuts incohérents
        $problematicTickets = \App\Models\Ticket::where('status', 'sold')
            ->whereNull('ticket_code')
            ->orWhere('ticket_code', '')
            ->get();
            
        if ($problematicTickets->count() > 0) {
            $this->warn("⚠️ {$problematicTickets->count()} tickets vendus sans code détectés");
            
            if (!$dryRun) {
                foreach ($problematicTickets as $ticket) {
                    $ticket->update([
                        'ticket_code' => 'TIK-' . strtoupper(uniqid()),
                        'status' => 'available'
                    ]);
                    $stats['tickets_fixed']++;
                }
                $this->info("✅ {$stats['tickets_fixed']} tickets corrigés");
            } else {
                $this->info("📋 {$problematicTickets->count()} tickets seraient corrigés");
            }
        } else {
            $this->info("✅ Aucun ticket problématique trouvé");
        }
        
        // Recherche de doublons
        $duplicates = \Illuminate\Support\Facades\DB::table('tickets')
            ->select('ticket_code', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
            ->whereNotNull('ticket_code')
            ->where('ticket_code', '!=', '')
            ->groupBy('ticket_code')
            ->having('count', '>', 1)
            ->get();
            
        if ($duplicates->count() > 0) {
            $this->warn("⚠️ {$duplicates->count()} codes de tickets en doublon détectés");
            $stats['duplicates_found'] = $duplicates->count();
            
            if (!$dryRun) {
                foreach ($duplicates as $duplicate) {
                    $tickets = \App\Models\Ticket::where('ticket_code', $duplicate->ticket_code)
                        ->orderBy('created_at')
                        ->get();
                        
                    $tickets->skip(1)->each(function ($ticket) {
                        $ticket->update([
                            'ticket_code' => 'TIK-' . strtoupper(uniqid())
                        ]);
                    });
                }
                $this->info("✅ Doublons corrigés");
            }
        } else {
            $this->info("✅ Aucun doublon trouvé");
        }
        
        // Génération QR codes manquants
        $ticketsWithoutQR = \App\Models\Ticket::where('status', 'sold')
            ->whereNull('qr_code')
            ->count();
            
        if ($ticketsWithoutQR > 0) {
            $this->warn("⚠️ {$ticketsWithoutQR} tickets vendus sans QR code");
            
            if (!$dryRun) {
                $qrService = app(\App\Services\QRCodeService::class);
                
                \App\Models\Ticket::where('status', 'sold')
                    ->whereNull('qr_code')
                    ->chunk(50, function ($tickets) use ($qrService, &$stats) {
                        foreach ($tickets as $ticket) {
                            $qr = $qrService->generateTicketQRBase64($ticket);
                            if ($qr) {
                                $ticket->update(['qr_code' => $qr]);
                                $stats['qr_generated']++;
                            }
                        }
                    });
                    
                $this->info("✅ {$stats['qr_generated']} QR codes générés");
            } else {
                $this->info("📋 {$ticketsWithoutQR} QR codes seraient générés");
            }
        } else {
            $this->info("✅ Tous les tickets vendus ont un QR code");
        }
    }
    
    // Nettoyage QR codes
    if (!$ticketsOnly && !$cacheOnly) {
        $this->info("\n🖼️ Nettoyage des QR codes");
        $this->line("─────────────────────────────────────────");
        
        // Les QR sont des data-URI en base (aucun fichier à nettoyer).
        $this->info("✅ Aucun fichier QR à nettoyer (QR stockés en base)");
    }
    
    // Nettoyage cache
    if (!$ticketsOnly && !$qrOnly) {
        $this->info("\n🗄️ Nettoyage du cache");
        $this->line("─────────────────────────────────────────");
        
        if (!$dryRun) {
            \Illuminate\Support\Facades\Cache::flush();
            $this->call('config:clear');
            $this->call('route:clear');
            $this->call('view:clear');
            $stats['cache_cleared'] = 1;
            $this->info("✅ Cache système nettoyé");
        } else {
            $this->info("📋 Cache système serait nettoyé");
        }
    }
    
    // Résumé
    $this->info("\n📊 RÉSUMÉ DU NETTOYAGE");
    $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    
    $mode = $dryRun ? 'SIMULATION' : 'RÉEL';
    $this->line("Mode : {$mode}");
    $this->line("");
    
    if ($stats['tickets_fixed'] > 0) {
        $this->line("🎫 Tickets corrigés : {$stats['tickets_fixed']}");
    }
    
    if ($stats['duplicates_found'] > 0) {
        $this->line("🔍 Doublons trouvés : {$stats['duplicates_found']}");
    }
    
    if ($stats['qr_generated'] > 0) {
        $this->line("📱 QR codes générés : {$stats['qr_generated']}");
    }
    
    if ($stats['qr_cleaned'] > 0) {
        $this->line("🖼️ QR codes nettoyés : {$stats['qr_cleaned']}");
    }
    
    if ($stats['cache_cleared'] > 0) {
        $this->line("🗄️ Cache nettoyé : Oui");
    }
    
    $this->line("");
    
    if ($dryRun) {
        $this->warn("⚠️ Aucune modification n'a été effectuée (mode simulation)");
        $this->info("💡 Exécutez sans --dry-run pour appliquer les changements");
    } else {
        $this->info("✅ Nettoyage terminé avec succès !");
    }
})->purpose('Nettoie et optimise le projet : doublons, QR codes, cache');

// ========== COMMANDES DE DIAGNOSTIC ==========

// Diagnostic des tickets
Artisan::command('tickets:diagnose {ticket_code? : Code du ticket à diagnostiquer} {--fix : Corriger automatiquement les problèmes}', function () {
    $ticketCode = $this->argument('ticket_code');
    $fixMode = $this->option('fix');
    
    if ($ticketCode) {
        $this->info("🔍 Diagnostic du ticket : {$ticketCode}");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        
        $ticket = \App\Models\Ticket::where('ticket_code', $ticketCode)
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
        $this->line("  • Présent: " . ($ticket->qr_code ? 'OUI' : 'NON'));
        
        if ($fixMode && !$ticket->qr_code) {
            $qrService = app(\App\Services\QRCodeService::class);
            $qr = $qrService->generateTicketQRBase64($ticket);
            if ($qr) {
                $ticket->update(['qr_code' => $qr]);
                $this->info("  🔧 QR code généré et sauvegardé");
            }
        }
        
    } else {
        // Diagnostic global
        $this->info("🔍 Diagnostic global des tickets");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        
        $stats = [
            'total' => \App\Models\Ticket::count(),
            'sold' => \App\Models\Ticket::where('status', 'sold')->count(),
            'used' => \App\Models\Ticket::where('status', 'used')->count(),
            'available' => \App\Models\Ticket::where('status', 'available')->count(),
            'cancelled' => \App\Models\Ticket::where('status', 'cancelled')->count(),
            'with_qr' => \App\Models\Ticket::whereNotNull('qr_code')->count(),
            'without_qr' => \App\Models\Ticket::whereNull('qr_code')->count(),
        ];
        
        $this->line("📊 Statistiques :");
        foreach ($stats as $key => $value) {
            $this->line("  • " . ucfirst(str_replace('_', ' ', $key)) . ": {$value}");
        }
    }
})->purpose('Diagnostiquer les problèmes de tickets et QR codes');

// Test du service QR
Artisan::command('qr:test', function () {
    $this->info("🧪 Test du service QR Code");
    $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    
    try {
        $qrService = app(\App\Services\QRCodeService::class);
        $results = $qrService->testAllMethods();
        
        if ($results['success']) {
            $this->info("✅ Service QR Code fonctionne correctement");
            
            foreach ($results['tests'] as $test => $result) {
                if ($result['success']) {
                    $this->line("  ✅ {$test}: OK");
                } else {
                    $this->error("  ❌ {$test}: " . ($result['error'] ?? 'Échec'));
                }
            }
        } else {
            $this->error("❌ Problèmes détectés avec le service QR Code");
        }
        
    } catch (\Exception $e) {
        $this->error("❌ Erreur lors du test: " . $e->getMessage());
    }
})->purpose('Tester le service de génération de QR codes');