<?php

// ==============================================
// config/payments.php
// ==============================================

return [
    'default' => env('PAYMENT_DEFAULT_PROVIDER', 'paiementpro'),
    
    'providers' => [
        'paiementpro' => [
            'enabled' => env('PAIEMENTPRO_ENABLED', true),
            'merchant_id' => env('PAIEMENTPRO_MERCHANT_ID'),
            'secret_key' => env('PAIEMENTPRO_SECRET_KEY'),
            'base_url' => env('PAIEMENTPRO_BASE_URL', 'https://www.paiementpro.net'),
            'test_mode' => env('PAIEMENTPRO_TEST_MODE', false),
        ],
    ],
    
    // Durée de réservation d'une commande non payée (minutes) avant libération du stock
    'reservation_minutes' => (int) env('ORDER_RESERVATION_MINUTES', 30),

    'currency' => [
        'code' => 'XOF',
        'symbol' => 'FCFA',
        'iso_code' => '952'
    ]
];