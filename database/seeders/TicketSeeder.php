<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TicketType;
use App\Models\Ticket;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer tous les types de billets
        $ticketTypes = TicketType::all();

        foreach ($ticketTypes as $ticketType) {
            // Générer des billets pour chaque type
            for ($i = 1; $i <= $ticketType->quantity_available; $i++) {
                $ticket = Ticket::create([
                    'ticket_type_id' => $ticketType->id,
                    'ticket_code' => Ticket::generateTicketCode(),
                    'status' => $i <= $ticketType->quantity_sold ? 'sold' : 'available',
                    'seat_number' => $this->generateSeatNumber($ticketType, $i),
                ]);

                // Générer le QR code pour ce billet
                try {
                    $ticket->generateQRCode();
                } catch (\Exception $e) {
                    // Si la génération du QR code échoue, on continue sans QR code
                    echo "Erreur génération QR code pour billet {$ticket->ticket_code}: " . $e->getMessage() . "\n";
                }
            }
        }

        echo "Billets générés avec succès !\n";
        echo "Total billets créés: " . Ticket::count() . "\n";
        echo "Billets disponibles: " . Ticket::where('status', 'available')->count() . "\n";
        echo "Billets vendus: " . Ticket::where('status', 'sold')->count() . "\n";
    }

    /**
     * Générer un numéro de siège selon le type de billet
     */
    private function generateSeatNumber($ticketType, $position)
    {
        $eventTitle = $ticketType->event->title ?? 'Event';
        $typeName = $ticketType->name;

        // Générer des numéros de siège selon le type
        switch (strtolower($typeName)) {
            case 'vip gold':
            case 'vip rastafari':
            case 'vip all access':
                return 'VIP-' . str_pad($position, 3, '0', STR_PAD_LEFT);

            case 'vip standard':
                return 'VIPS-' . str_pad($position, 3, '0', STR_PAD_LEFT);

            case 'tribune présidentielle':
            case 'orchestre':
                return 'T' . chr(65 + floor(($position - 1) / 20)) . '-' . str_pad((($position - 1) % 20) + 1, 2, '0', STR_PAD_LEFT);

            case 'tribune':
            case 'tribune latérale':
            case 'balcon':
                return 'TR' . chr(65 + floor(($position - 1) / 25)) . '-' . str_pad((($position - 1) % 25) + 1, 2, '0', STR_PAD_LEFT);

            case 'gradin':
            case 'fosse':
            case 'carré or':
                return 'GR' . str_pad(ceil($position / 50), 2, '0', STR_PAD_LEFT) . '-' . str_pad($position, 3, '0', STR_PAD_LEFT);

            case 'pelouse':
            case 'virage populaire':
                // Pas de siège numéroté pour les zones debout
                return null;

            case 'étudiant':
                return 'ETU-' . str_pad($position, 3, '0', STR_PAD_LEFT);

            case 'standard':
                return 'STD-' . str_pad($position, 3, '0', STR_PAD_LEFT);

            case 'paradis':
                return 'PAR' . chr(65 + floor(($position - 1) / 30)) . '-' . str_pad((($position - 1) % 30) + 1, 2, '0', STR_PAD_LEFT);

            default:
                return 'GEN-' . str_pad($position, 4, '0', STR_PAD_LEFT);
        }
    }

    /**
     * Créer quelques commandes de test avec des billets vendus
     */
    private function createSampleOrders()
    {
        // Cette méthode pourrait être utilisée pour créer des commandes de test
        // mais on va la garder pour plus tard quand on aura le système de commande complet
    }
}