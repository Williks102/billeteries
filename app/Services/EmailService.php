<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Mail\OrderConfirmation;
use App\Mail\ManualPaymentInstructions;
use App\Mail\PaymentConfirmation;
use App\Mail\PromoteurNewSale;
use App\Mail\AdminNewOrder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Support\MailDiagnostic;

class EmailService
{
    /**
     * Mettre en file l'e-mail de confirmation de commande (retourne true = job enregistré, pas « envoyé »)
     */
    public function sendOrderConfirmation(Order $order)
    {
        try {
            // Mis en file : le destinataire est résolu par le Mailable au moment de l'envoi (job).
            Mail::queue(new OrderConfirmation($order));

            Log::info("Email confirmation commande mis en file d'attente", [
                'order_id' => $order->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Erreur envoi email confirmation commande", [
                'order_id' => $order->id,
                'error' => MailDiagnostic::redact($e->getMessage())
            ]);
            return false;
        }
    }

    /**
     * Mettre en file l'e-mail de confirmation de paiement (PDF des billets généré dans le job)
     */
    public function sendPaymentConfirmation(Order $order)
{
    try {
        // Mis en file : le PDF des billets est généré dans le job, jamais pendant la requête.
        Mail::queue(new PaymentConfirmation($order));

        Log::info("Email confirmation paiement mis en file d'attente", [
            'order_id' => $order->id,
        ]);

        return true;
    } catch (\Exception $e) {
        Log::error("Erreur envoi email confirmation paiement", [
            'order_id' => $order->id,
            'error' => MailDiagnostic::redact($e->getMessage())
        ]);
        return false;
    }
}



    /**
     * Envoyer les instructions de paiement manuel (virement, Mobile Money, espèces)
     */
    public function sendManualPaymentInstructions(Order $order)
    {
        try {
            Mail::queue(new ManualPaymentInstructions($order));

            Log::info('Instructions de paiement manuel mises en file d\'attente', [
                'order_id' => $order->id,
                'payment_method' => $order->payment_method,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur envoi instructions de paiement manuel', [
                'order_id' => $order->id,
                'error' => MailDiagnostic::redact($e->getMessage())
            ]);
            return false;
        }
    }

    /**
     * Alias historique (ancien nom lié au seul virement bancaire)
     */
    public function sendBankTransferInstructions(Order $order)
    {
        return $this->sendManualPaymentInstructions($order);
    }

    /**
     * Notifier le promoteur d'une nouvelle vente
     */
    public function notifyPromoteurNewSale(Order $order)
    {
        try {
            $promoteur = $order->event->promoteur;

            Mail::queue(new PromoteurNewSale($order));

            Log::info("Email promoteur nouvelle vente mis en file d'attente", [
                'order_id' => $order->id,
                'promoteur_id' => $promoteur->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Erreur envoi email promoteur", [
                'order_id' => $order->id,
                'error' => MailDiagnostic::redact($e->getMessage())
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
                Mail::queue(new AdminNewOrder($order, $admin));
            }

            Log::info("Email admin nouvelle commande mis en file d'attente", [
                'order_id' => $order->id,
                'admin_count' => $admins->count()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Erreur envoi email admin", [
                'order_id' => $order->id,
                'error' => MailDiagnostic::redact($e->getMessage())
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

            Log::info("Email de test envoyé", ['email_hash' => substr(sha1(strtolower((string) $email)), 0, 10)]);
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
        Mail::queue(new \App\Mail\WelcomeEmail($user));

        Log::info("Email bienvenue mis en file d'attente", [
            'user_id' => $user->id,
            'role' => $user->role
        ]);

        return true;
    } catch (\Exception $e) {
        Log::error("Erreur envoi email bienvenue", [
            'user_id' => $user->id,
            'error' => MailDiagnostic::redact($e->getMessage())
        ]);
        return false;
    }
}
}