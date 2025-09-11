<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use SoapClient;
use Exception;

class PaiementProService
{
    private string $merchantId;
    private string $wsdlUrl;
    private string $processingUrl;
    private string $secretKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->merchantId = config('payments.paiementpro.merchant_id');
        $this->secretKey = config('payments.paiementpro.secret_key');
        $this->baseUrl = config('payments.paiementpro.base_url', 'https://www.paiementpro.net');
        $this->wsdlUrl = $this->baseUrl . '/webservice/OnlineServicePayment_v2.php?wsdl';
        $this->processingUrl = $this->baseUrl . '/webservice/onlinepayment/processing_v2.php';
    }

    /**
     * Initialiser une transaction de paiement
     */
    public function initTransaction(Order $order, array $options = []): array
    {
        try {
            // Préparer les paramètres
            $params = $this->prepareInitParams($order, $options);
            
            // Générer le hashcode pour la sécurité
            $params['hashcode'] = $this->generateHashCode($params);

            // Créer le client SOAP
            $client = new SoapClient($this->wsdlUrl, [
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => true,
                'exceptions' => true
            ]);

            // Appeler initTransact
            $response = $client->initTransact($params);

            Log::info('PaiementPro initTransaction response', [
                'order_id' => $order->id,
                'response' => $response
            ]);

            return $this->handleInitResponse($order, $response);

        } catch (Exception $e) {
            Log::error('Erreur initialisation PaiementPro', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Erreur lors de l\'initialisation du paiement',
                'code' => -1
            ];
        }
    }

    /**
     * Préparer les paramètres pour l'initialisation
     */
    private function prepareInitParams(Order $order, array $options): array
    {
        $user = $order->user;
        $event = $order->event;
        
        return [
            'merchantId' => $this->merchantId,
            'countryCurrencyCode' => '952', // XOF (CFA)
            'referenceNumber' => $order->order_number,
            'amount' => (int) $order->total_amount, // Montant en FCFA
            'channel' => $options['channel'] ?? '', // OMCIV2, MOMO, CARD, FLOOZ, PAYPAL
            'customerId' => $user->id,
            'customerEmail' => $user->email,
            'customerFirstName' => $this->extractFirstName($user->name),
            'customerLastName' => $this->extractLastName($user->name),
            'customerPhoneNumber' => $this->formatPhoneNumber($user->phone),
            'description' => "Achat billet(s) - {$event->title}",
            'notificationURL' => route('payments.paiementpro.notification'),
            'returnURL' => route('payments.paiementpro.return'),
            'returnContext' => $this->prepareReturnContext($order)
        ];
    }

    /**
     * Traiter la réponse d'initialisation
     */
    private function handleInitResponse(Order $order, $response): array
    {
        if ($response->Code == 0) {
            // Succès - sauvegarder la session ID
            Payment::create([
                'order_id' => $order->id,
                'payment_method' => 'paiementpro',
                'amount' => $order->total_amount,
                'status' => 'pending',
                'provider_transaction_id' => $response->Sessionid,
                'provider_response' => json_encode($response)
            ]);

            return [
                'success' => true,
                'sessionId' => $response->Sessionid,
                'redirectUrl' => $this->processingUrl . '?sessionid=' . $response->Sessionid,
                'code' => 0
            ];
        }

        // Gestion des erreurs
        $errorMessages = [
            10 => 'Paramètres insuffisants',
            11 => 'ID marchand inconnu',
            -1 => 'Erreur d\'initialisation'
        ];

        return [
            'success' => false,
            'error' => $errorMessages[$response->Code] ?? $response->Description,
            'code' => $response->Code
        ];
    }

    /**
     * Traiter la notification de PaiementPro
     */
    public function handleNotification(array $data): array
    {
        try {
            // Vérifier le hashcode pour la sécurité
            if (!$this->verifyHashCode($data)) {
                Log::warning('PaiementPro notification - hashcode invalide', $data);
                return ['success' => false, 'error' => 'Hash invalide'];
            }

            // Trouver la commande
            $order = Order::where('order_number', $data['referenceNumber'])->first();
            if (!$order) {
                Log::error('PaiementPro notification - commande introuvable', $data);
                return ['success' => false, 'error' => 'Commande introuvable'];
            }

            // Mettre à jour le statut du paiement
            $payment = Payment::where('order_id', $order->id)
                             ->where('payment_method', 'paiementpro')
                             ->first();

            if (!$payment) {
                Log::error('PaiementPro notification - paiement introuvable', $data);
                return ['success' => false, 'error' => 'Paiement introuvable'];
            }

            // Traiter selon le code de réponse
            if ($data['responsecode'] == '0') {
                // Transaction réussie
                $this->handleSuccessfulPayment($order, $payment, $data);
            } else {
                // Transaction échouée
                $this->handleFailedPayment($order, $payment, $data);
            }

            Log::info('PaiementPro notification traitée', [
                'order_id' => $order->id,
                'status' => $data['responsecode'] == '0' ? 'success' : 'failed'
            ]);

            return ['success' => true];

        } catch (Exception $e) {
            Log::error('Erreur traitement notification PaiementPro', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Traiter un paiement réussi
     */
    private function handleSuccessfulPayment(Order $order, Payment $payment, array $data): void
{
    // Mettre à jour le paiement
    $payment->update([
        'status' => 'completed',
        'completed_at' => now(),
        'provider_response' => json_encode($data)
    ]);

    // ✅ UTILISER VOTRE MÉTHODE EXISTANTE
    $order->markAsPaid('PAIEMENTPRO-' . ($data['transactionId'] ?? time()));

    // ⚠️ CHANGEMENT: Marquer les billets comme vendus (au lieu de réservés)
    $order->tickets()->update(['status' => 'sold']);

    // Les emails sont déjà envoyés par votre méthode markAsPaid()
}

    /**
     * Traiter un paiement échoué
     */
    private function handleFailedPayment(Order $order, Payment $payment, array $data): void
    {
        $payment->update([
            'status' => 'failed',
            'provider_response' => json_encode($data)
        ]);

        $order->markPaymentAsFailed();

        // Envoyer email d'échec
        app(\App\Services\EmailService::class)->sendPaymentFailedNotification($order);
    }

    /**
     * Préparer le contexte de retour
     */
    private function prepareReturnContext(Order $order): string
    {
        return http_build_query([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'event_id' => $order->event_id
        ]);
    }

    /**
     * Extraire le prénom du nom complet
     */
    private function extractFirstName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        return $parts[0] ?? '';
    }

    /**
     * Extraire le nom de famille du nom complet
     */
    private function extractLastName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        array_shift($parts); // Enlever le premier élément (prénom)
        return implode(' ', $parts) ?: $parts[0] ?? '';
    }

    /**
     * Formater le numéro de téléphone
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Nettoyer le numéro
        $clean = preg_replace('/[^\d+]/', '', $phone);
        
        // Retourner au format attendu (ex: 22507517917)
        if (strpos($clean, '+225') === 0) {
            return substr($clean, 1); // Enlever le +
        }
        
        if (strpos($clean, '225') === 0) {
            return $clean;
        }
        
        if (strpos($clean, '0') === 0) {
            return '225' . substr($clean, 1);
        }
        
        return '225' . $clean;
    }

    /**
     * Obtenir les canaux de paiement disponibles
     */
    public function getAvailableChannels(): array
    {
        return [
            'CARD' => 'Carte bancaire',
            'MOMO' => 'Mobile Money',
            'OMCIV2' => 'Orange Money',
            'FLOOZ' => 'Flooz',
            'PAYPAL' => 'PayPal'
        ];
    }

    /**
     * Vérifier le statut d'une transaction
     */
    public function checkTransactionStatus(string $sessionId): array
    {
        // Cette méthode pourrait être utilisée pour vérifier le statut
        // si PaiementPro fournit une API de vérification de statut
        return ['status' => 'unknown'];
    }
}