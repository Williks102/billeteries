<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use SoapClient;
use Exception;

class PaiementProService
{
    private ?string $merchantId;
    private ?string $secretKey;
    private string $wsdlUrl;
    private string $processingUrl;
    private string $baseUrl;

    public function __construct()
    {
        // ✅ CORRECTION : Chemin de configuration corrigé
        $this->merchantId = config('payments.providers.paiementpro.merchant_id');
        $this->secretKey = config('payments.providers.paiementpro.secret_key');
        $this->baseUrl = config('payments.providers.paiementpro.base_url', 'https://www.paiementpro.net');
        
        // ✅ URL EXACTE selon la documentation
        $this->wsdlUrl = 'https://www.paiementpro.net/webservice/OnlineServicePayment_v2.php?wsdl';
        $this->processingUrl = 'https://www.paiementpro.net/webservice/onlinepayment/processing_v2.php';
        
        // Vérifications
        if (empty($this->merchantId)) {
            throw new \Exception('PAIEMENTPRO_MERCHANT_ID manquant dans .env');
        }
    }

    /**
     * Initialiser une transaction de paiement
     */
    public function initTransaction(Order $order, array $options = []): array
    {
        try {
            // Préparer les paramètres selon la documentation
            $params = $this->prepareInitParams($order, $options);
            
            // ✅ NOUVEAU : Générer le hashcode obligatoire
            $params['hashcode'] = $this->generateHashCode($params);

            Log::info('PaiementPro - Paramètres envoyés', $params);

            // Créer le client SOAP avec les bonnes options
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
                'error' => 'Erreur lors de l\'initialisation du paiement: ' . $e->getMessage(),
                'code' => -1
            ];
        }
    }

    /**
     * ✅ CORRECTION : Préparer les paramètres selon la documentation exacte
     */
    private function prepareInitParams(Order $order, array $options): array
    {
        $user = $order->user;
        $event = $order->event;
        
        return [
            'merchantId' => $this->merchantId,
            'countryCurrencyCode' => '952', // CFA selon documentation
            'referenceNumber' => $order->order_number,
            'amount' => (int) $order->total_amount,
            'channel' => $options['channel'] ?? '', // Peut être vide selon doc
            'customerId' => $user->id,
            'customerEmail' => $user->email,
            'customerFirstName' => $this->extractFirstName($user->name),
            'customerLastName' => $this->extractLastName($user->name), // ✅ CORRECTION : lastName pas lastname
            'customerPhoneNumber' => $this->formatPhoneNumber($user->phone),
            'description' => "Achat billet(s) - {$event->title}",
            'notificationURL' => route('payments.paiementpro.notification'),
            'returnURL' => route('payments.paiementpro.return'),
            'returnContext' => $this->prepareReturnContext($order)
            // hashcode sera ajouté après génération
        ];
    }

    /**
     * ✅ NOUVEAU : Génération du hashcode selon PaiementPro
     */
    private function generateHashCode(array $params): string
    {
        // Si pas de secret key, retourner une valeur par défaut (mode test)
        if (empty($this->secretKey)) {
            Log::warning('Secret key manquante - hashcode par défaut utilisé');
            return hash('sha256', $params['merchantId'] . $params['referenceNumber'] . $params['amount']);
        }

        // Générer le hashcode selon la méthode PaiementPro
        $hashString = $params['merchantId'] . 
                     $params['referenceNumber'] . 
                     $params['amount'] . 
                     $this->secretKey;
        
        return hash('sha256', $hashString);
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

        // ✅ Messages d'erreur selon la documentation
        $errorMessages = [
            10 => 'Paramètres insuffisants',
            11 => 'ID marchand inconnu',
            -1 => 'Erreur d\'initialisation'
        ];

        return [
            'success' => false,
            'error' => $errorMessages[$response->Code] ?? 'Erreur inconnue: ' . $response->Code,
            'description' => $response->Description ?? '',
            'code' => $response->Code
        ];
    }

    /**
     * ✅ NOUVEAU : Traitement des notifications selon la documentation
     */
    public function handleNotification(array $data): array
    {
        try {
            // Vérifier le hashcode pour la sécurité
            if (!$this->verifyNotificationHashCode($data)) {
                Log::error('Hashcode invalide dans notification PaiementPro', $data);
                return ['success' => false, 'error' => 'Hashcode invalide'];
            }

            // Trouver la commande
            $order = Order::where('order_number', $data['referenceNumber'])->first();
            if (!$order) {
                Log::error('Commande non trouvée pour notification PaiementPro', $data);
                return ['success' => false, 'error' => 'Commande non trouvée'];
            }

            // Traiter selon le code de réponse
            $success = ($data['responsecode'] == '0');
            
            if ($success) {
                $this->processSuccessfulPayment($order, $data);
            } else {
                $this->processFailedPayment($order, $data);
            }

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
     * Vérifier le hashcode de notification
     */
    private function verifyNotificationHashCode(array $data): bool
    {
        if (empty($this->secretKey)) {
            Log::warning('Vérification hashcode ignorée - secret key manquante');
            return true; // En mode test
        }

        $expectedHash = hash('sha256', 
            $data['merchantId'] . 
            $data['referenceNumber'] . 
            $data['amount'] . 
            $this->secretKey
        );

        return hash_equals($expectedHash, $data['hashcode'] ?? '');
    }

    /**
     * Traiter un paiement réussi
     */
    private function processSuccessfulPayment(Order $order, array $data): void
    {
        // Mettre à jour la commande
        $order->update([
            'payment_status' => 'paid',
            'payment_date' => now(),
        ]);

        // Mettre à jour le paiement
        $payment = Payment::where('order_id', $order->id)
                         ->where('payment_method', 'paiementpro')
                         ->first();
        
        if ($payment) {
            $payment->update([
                'status' => 'completed',
                'provider_response' => json_encode($data)
            ]);
        }

        // Marquer les tickets comme vendus
        foreach ($order->tickets as $ticket) {
            $ticket->markAsSold();
        }

        Log::info('Paiement PaiementPro confirmé', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'amount' => $data['amount']
        ]);
    }

    /**
     * Traiter un paiement échoué
     */
    private function processFailedPayment(Order $order, array $data): void
    {
        $order->update(['payment_status' => 'failed']);

        $payment = Payment::where('order_id', $order->id)
                         ->where('payment_method', 'paiementpro')
                         ->first();
        
        if ($payment) {
            $payment->update([
                'status' => 'failed',
                'provider_response' => json_encode($data)
            ]);
        }

        Log::info('Paiement PaiementPro échoué', [
            'order_id' => $order->id,
            'responsecode' => $data['responsecode']
        ]);
    }

    /**
     * Utilitaires pour noms
     */
    private function extractFirstName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        return $parts[0] ?? '';
    }

    private function extractLastName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        return count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
    }

    /**
     * Formater le numéro de téléphone
     */
    private function formatPhoneNumber(?string $phone): string
    {
        if (empty($phone)) return '';
        
        // Supprimer tous les caractères non numériques
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Format pour la Côte d'Ivoire
        if (strlen($phone) == 10 && $phone[0] == '0') {
            return '225' . substr($phone, 1); // Remplacer 0 par 225
        }
        
        return $phone;
    }

    /**
     * Préparer le contexte de retour
     */
    private function prepareReturnContext(Order $order): string
    {
        return http_build_query([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'user_id' => $order->user_id
        ]);
    }
}