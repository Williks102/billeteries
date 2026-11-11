<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
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
        // Aucune exception ici : la configuration est validée paresseusement
        // (initTransaction / handleNotification) pour que route:list et route:cache
        // fonctionnent sans variables d'environnement.
    }

    /**
     * Configuration minimale présente (merchant_id + secret_key)
     */
    public function isConfigured(): bool
    {
        return !empty($this->merchantId) && !empty($this->secretKey);
    }

    /**
     * Initialiser une transaction de paiement
     */
    public function initTransaction(Order $order, array $options = []): array
    {
        if (!$this->isConfigured()) {
            Log::critical('PaiementPro non configuré (PAIEMENTPRO_MERCHANT_ID / PAIEMENTPRO_SECRET_KEY)');
            return [
                'success' => false,
                'error' => 'Le paiement en ligne est momentanément indisponible.',
                'code' => -1
            ];
        }

        try {
            // Préparer les paramètres selon la documentation
            $params = $this->prepareInitParams($order, $options);
            
            // ✅ NOUVEAU : Générer le hashcode obligatoire
            $params['hashcode'] = $this->generateHashCode($params);

            Log::info('PaiementPro - initialisation', [
                'order_id' => $order->id,
                'referenceNumber' => $params['referenceNumber'],
                'amount' => $params['amount'],
            ]);

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
                'code' => $response->Code ?? null,
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
        if (empty($this->secretKey)) {
            throw new Exception('PAIEMENTPRO_SECRET_KEY manquant : hashcode impossible');
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
     * Traitement idempotent des notifications (webhook).
     *
     * Retour : success, [duplicate], [order], [payment_successful], [error]
     */
    public function handleNotification(array $data): array
    {
        try {
            // Signature obligatoire (fail closed)
            if (!$this->verifyNotificationHashCode($data)) {
                Log::error('Hashcode invalide dans notification PaiementPro', [
                    'referenceNumber' => $data['referenceNumber'] ?? null,
                ]);
                return ['success' => false, 'error' => 'Hashcode invalide'];
            }

            $success = ((string) ($data['responsecode'] ?? '')) === '0';

            return DB::transaction(function () use ($data, $success) {
                $order = Order::where('order_number', $data['referenceNumber'])
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    Log::error('Commande non trouvée pour notification PaiementPro', [
                        'referenceNumber' => $data['referenceNumber'],
                    ]);
                    return ['success' => false, 'error' => 'Commande non trouvée'];
                }

                // Idempotence : déjà payée => rien à refaire, jamais écrasée
                if ($order->payment_status === 'paid') {
                    return ['success' => true, 'duplicate' => true, 'order' => $order, 'payment_successful' => false];
                }

                // Le montant notifié doit être celui de la commande
                if ((int) $data['amount'] !== (int) $order->total_amount) {
                    Log::critical('Montant PaiementPro différent de la commande', [
                        'order_id' => $order->id,
                        'notified' => $data['amount'],
                        'expected' => (int) $order->total_amount,
                    ]);
                    return ['success' => false, 'error' => 'Montant invalide'];
                }

                if ($success) {
                    return $this->processSuccessfulPayment($order, $data);
                }

                return $this->processFailedPayment($order, $data);
            });

        } catch (Exception $e) {
            Log::error('Erreur traitement notification PaiementPro', [
                'error' => $e->getMessage(),
                'referenceNumber' => $data['referenceNumber'] ?? null,
            ]);

            return ['success' => false, 'error' => 'Erreur de traitement'];
        }
    }

    /**
     * Vérifier le hashcode de notification (fail closed).
     * Formule inchangée : sha256(merchantId . referenceNumber . amount . secret)
     */
    private function verifyNotificationHashCode(array $data): bool
    {
        if (empty($this->secretKey) || empty($this->merchantId)) {
            Log::critical('Notification PaiementPro refusée : secret ou merchant_id non configuré');
            return false;
        }

        $hashcode = (string) ($data['hashcode'] ?? '');
        if ($hashcode === '' || !isset($data['merchantId'], $data['referenceNumber'], $data['amount'])) {
            return false;
        }

        if (!hash_equals((string) $this->merchantId, (string) $data['merchantId'])) {
            return false;
        }

        $expectedHash = hash('sha256',
            $this->merchantId .
            $data['referenceNumber'] .
            $data['amount'] .
            $this->secretKey
        );

        return hash_equals($expectedHash, $hashcode);
    }

    /**
     * Traiter un paiement réussi (appelé sous verrou, dans la transaction)
     */
    private function processSuccessfulPayment(Order $order, array $data): array
    {
        // Commande libérée (expirée/échouée) : ne la réactiver que si le stock est encore disponible
        if ($order->payment_status === 'failed') {
            if (!$order->reclaimStock()) {
                Log::critical('Paiement reçu sur commande libérée, stock indisponible : remboursement manuel requis', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]);
                return ['success' => true, 'duplicate' => false, 'order' => $order, 'payment_successful' => false];
            }
        } elseif (!in_array($order->payment_status, ['pending', 'processing'], true)) {
            Log::warning('Notification success ignorée : statut de commande inattendu', [
                'order_id' => $order->id,
                'status' => $order->payment_status,
            ]);
            return ['success' => true, 'duplicate' => true, 'order' => $order, 'payment_successful' => false];
        }

        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'expires_at' => null,
        ]);

        $payment = Payment::where('order_id', $order->id)
                         ->where('payment_method', 'paiementpro')
                         ->latest('id')
                         ->first();

        if ($payment) {
            $payment->update([
                'status' => 'completed',
                'completed_at' => now(),
                'provider_response' => $this->safeProviderData($data),
            ]);
        }

        // Billets émis (vendus) uniquement après paiement confirmé
        foreach ($order->tickets as $ticket) {
            $ticket->markAsSold();
        }

        Log::info('Paiement PaiementPro confirmé', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'amount' => $data['amount']
        ]);

        return ['success' => true, 'duplicate' => false, 'order' => $order, 'payment_successful' => true];
    }

    /**
     * Traiter un paiement échoué : uniquement depuis pending/processing, libère le stock
     */
    private function processFailedPayment(Order $order, array $data): array
    {
        if (!in_array($order->payment_status, ['pending', 'processing'], true)) {
            return ['success' => true, 'duplicate' => true, 'order' => $order, 'payment_successful' => false];
        }

        $order->releaseStock();

        $payment = Payment::where('order_id', $order->id)
                         ->where('payment_method', 'paiementpro')
                         ->latest('id')
                         ->first();

        if ($payment && $payment->status !== 'completed') {
            $payment->update([
                'status' => 'failed',
                'provider_response' => $this->safeProviderData($data),
            ]);
        }

        Log::info('Paiement PaiementPro échoué', [
            'order_id' => $order->id,
            'responsecode' => $data['responsecode'] ?? null
        ]);

        return ['success' => true, 'duplicate' => false, 'order' => $order, 'payment_successful' => false];
    }

    /**
     * Données fournisseur sans hashcode ni PII
     */
    private function safeProviderData(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'merchantId', 'referenceNumber', 'amount', 'responsecode', 'transactiondt', 'channel',
        ]));
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