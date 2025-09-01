<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Mail\OrderConfirmation;
use App\Mail\PaymentConfirmation;
use App\Mail\PromoteurNewSale;
use App\Mail\AdminNewOrder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    /**
     * Envoyer email de confirmation de commande
     */
    public function sendOrderConfirmation(Order $order)
    {
        try {
            Mail::to($order->user->email)
                ->send(new OrderConfirmation($order));

            Log::info("Email confirmation commande envoyé", [
                'order_id' => $order->id,
                'email' => $order->user->email
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Erreur envoi email confirmation commande", [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoyer email de confirmation de paiement avec billets
     */
    public function sendPaymentConfirmation(Order $order)
{
    try {
        Mail::to($order->user->email)
            ->send(new PaymentConfirmation($order));

        // Nettoyer le PDF temporaire après envoi
        $this->cleanupTempPDF($order->order_number);

        Log::info("Email confirmation paiement avec PDF envoyé", [
            'order_id' => $order->id,
            'email' => $order->user->email,
            'is_guest' => $order->user->is_guest,
            'pdf_attached' => true
        ]);

        return true;
    } catch (\Exception $e) {
        Log::error("Erreur envoi email confirmation paiement", [
            'order_id' => $order->id,
            'error' => $e->getMessage()
        ]);
        
        // Nettoyer quand même en cas d'erreur
        $this->cleanupTempPDF($order->order_number);
        return false;
    }
}



    /**
     * Notifier le promoteur d'une nouvelle vente
     */
    public function notifyPromoteurNewSale(Order $order)
    {
        try {
            $promoteur = $order->event->promoteur;

            Mail::to($promoteur->email)
                ->send(new PromoteurNewSale($order));

            Log::info("Email promoteur nouvelle vente envoyé", [
                'order_id' => $order->id,
                'promoteur_email' => $promoteur->email
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Erreur envoi email promoteur", [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Notifier les admins d'une nouvelle commande
     */
    public function notifyAdminNewOrder(Order $order)
    {
        try {
            $admins = User::where('role', 'admin')->get();

            foreach ($admins as $admin) {
                Mail::to($admin->email)
                    ->send(new AdminNewOrder($order));
            }

            Log::info("Email admin nouvelle commande envoyé", [
                'order_id' => $order->id,
                'admin_count' => $admins->count()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Erreur envoi email admin", [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoyer tous les emails pour une nouvelle commande
     */
    
    public function sendAllOrderEmails(Order $order)
{
    Log::info("Début sendAllOrderEmails", [
        'order_id' => $order->id,
        'payment_status' => $order->payment_status,
        'is_guest' => $order->user->is_guest ?? false,
        'email' => $order->user->email
    ]);

    // 1. Email confirmation au client
    $this->sendOrderConfirmation($order);

    // 2. 🔥 FIX: Si payé, email avec billets PDF (pour guests aussi !)
    if ($order->payment_status === 'paid') {
        Log::info("Commande payée détectée, envoi PDF", [
            'order_id' => $order->id,
            'is_guest' => $order->user->is_guest ?? false
        ]);
        
        $this->sendPaymentConfirmation($order);
    } else {
        Log::warning("Commande non payée, pas de PDF", [
            'order_id' => $order->id,
            'payment_status' => $order->payment_status
        ]);
    }

    // 3. Notification promoteur
    $this->notifyPromoteurNewSale($order);

    // 4. Notification admin
    $this->notifyAdminNewOrder($order);
    
    Log::info("Fin sendAllOrderEmails", ['order_id' => $order->id]);
}

    /**
     * Test d'envoi d'email
     */
    public function sendTestEmail($emailTo = null)
    {
        try {
            $email = $emailTo ?? config('mail.from.address');

            Mail::raw('Ceci est un email de test depuis ClicBillet CI. Si vous recevez cet email, la configuration fonctionne !', function ($message) use ($email) {
                $message->to($email)
                        ->subject('Test Email - ClicBillet CI');
            });

            Log::info("Email de test envoyé", ['email' => $email]);
            return true;
        } catch (\Exception $e) {
            Log::error("Erreur email de test", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
 * Nettoyer les PDF temporaires après envoi
 */
public function cleanupTempPDF($orderNumber)
{
    try {
        $tempPath = storage_path('app/temp/billets-' . $orderNumber . '.pdf');
        if (file_exists($tempPath)) {
            unlink($tempPath);
            Log::info("PDF temporaire supprimé", [
                'file' => basename($tempPath),
                'order_number' => $orderNumber
            ]);
            return true;
        }
        return false;
    } catch (\Exception $e) {
        Log::error("Erreur suppression PDF temporaire", [
            'order_number' => $orderNumber,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * Nettoyer tous les PDF temporaires anciens (plus de 1h)
 */
public function cleanupOldTempPDFs()
{
    try {
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            return 0;
        }

        $files = glob($tempDir . '/billets-*.pdf');
        $cleaned = 0;
        $oneHourAgo = time() - 3600; // 1 heure

        foreach ($files as $file) {
            if (filemtime($file) < $oneHourAgo) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }

        Log::info("Nettoyage PDF temporaires anciens", [
            'files_deleted' => $cleaned,
            'total_checked' => count($files)
        ]);

        return $cleaned;
    } catch (\Exception $e) {
        Log::error("Erreur nettoyage PDF temporaires", [
            'error' => $e->getMessage()
        ]);
        return 0;
    }
}
/**
 * Envoyer email de bienvenue après inscription
 */
public function sendWelcomeEmail(User $user)
{
    try {
        Mail::to($user->email)->send(new \App\Mail\WelcomeEmail($user));

        Log::info("Email bienvenue envoyé", [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role
        ]);

        return true;
    } catch (\Exception $e) {
        Log::error("Erreur envoi email bienvenue", [
            'user_id' => $user->id,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}
}