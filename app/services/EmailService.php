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

            Log::info("Email confirmation paiement envoyé", [
                'order_id' => $order->id,
                'email' => $order->user->email
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Erreur envoi email confirmation paiement", [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
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
        // 1. Email confirmation au client
        $this->sendOrderConfirmation($order);

        // 2. Si payé, email avec billets
        if ($order->payment_status === 'paid') {
            $this->sendPaymentConfirmation($order);
        }

        // 3. Notification promoteur
        $this->notifyPromoteurNewSale($order);

        // 4. Notification admin
        $this->notifyAdminNewOrder($order);
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
}