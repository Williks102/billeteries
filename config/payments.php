<?php

// ==============================================
// config/payments.php
// Paiement MANUEL : l'acheteur paie hors ligne, un administrateur confirme le paiement.
// ==============================================

return [
    // Durée de réservation d'une commande non payée (minutes) avant libération du stock
    'reservation_minutes' => (int) env('ORDER_RESERVATION_MINUTES', 30),

    // Durée de réservation d'une commande en paiement manuel (minutes) : 48 h par défaut
    'manual_reservation_minutes' => (int) env('MANUAL_RESERVATION_MINUTES', 2880),

    // Réservation manuelle pour un invité (compte non vérifié) : plus courte, 24 h par défaut
    'guest_manual_reservation_minutes' => (int) env('GUEST_MANUAL_RESERVATION_MINUTES', 1440),

    // Plafond de commandes pending/processing simultanées par email et par compte (anti-accaparement)
    'max_pending_orders' => (int) env('MAX_PENDING_ORDERS', 3),

    // Plafond de commandes invité par IP sur la fenêtre de réservation invité (CGNAT : valeur plus large)
    'max_pending_orders_per_ip' => (int) env('MAX_PENDING_ORDERS_PER_IP', 10),

    // Plafond de BILLETS en attente (pending/processing) par événement, compté sous verrou :
    // clamp(ceil(pending_ratio x capacité totale), pending_min, max_pending_tickets_per_event).
    // Capacité = somme des quantity_available de l'événement. Le max est un plafond dur (200 : au-delà
    // d'un événement de ~670 places, 30 % de stock bloqué ne serait plus proportionné ; il faut alors
    // >= 20 emails et >= 10 IP distincts pour l'atteindre).
    'pending_ratio' => (float) env('PENDING_RATIO', 0.3),
    'pending_min' => (int) env('PENDING_MIN', 10),
    'max_pending_tickets_per_event' => (int) env('MAX_PENDING_TICKETS_PER_EVENT', 200),

    // Quota de BILLETS en attente par acteur : email normalisé (alias +tag retiré) ou compte connecté.
    'max_pending_tickets_per_buyer' => (int) env('MAX_PENDING_TICKETS_PER_BUYER', 10),

    // Quota de billets par IP sur la fenêtre de réservation invité (plus large : CGNAT / réseau partagé)
    'max_pending_tickets_per_ip' => (int) env('MAX_PENDING_TICKETS_PER_IP', 20),

    // Throttle de l'inscription (POST register) : tentatives par minute et par IP
    'register_throttle_per_minute' => (int) env('REGISTER_THROTTLE_PER_MINUTE', 5),

    // Throttle des POST de checkout : tentatives par fenêtre d'une minute (clé = IP, ou id utilisateur pour un connecté)
    'checkout_throttle_per_minute' => (int) env('CHECKOUT_THROTTLE_PER_MINUTE', 10),

    // Méthodes de paiement manuelles autorisées (clé = valeur du champ payment_method).
    // Les coordonnées viennent de l'environnement : aucun numéro de compte réel dans le dépôt.
    'manual_methods' => [
        'bank_transfer' => [
            'label' => 'Virement bancaire',
            'instructions' => 'Effectuez le virement du montant total en indiquant le numéro de commande en référence.',
            'details' => [
                'Banque' => env('MANUAL_BANK_NAME'),
                'Titulaire' => env('MANUAL_BANK_ACCOUNT_HOLDER'),
                'IBAN / RIB' => env('MANUAL_BANK_IBAN'),
            ],
        ],
        'mobile_money' => [
            'label' => 'Mobile Money (transfert manuel)',
            'instructions' => 'Envoyez le montant total par Mobile Money au numéro indiqué en mentionnant le numéro de commande.',
            'details' => [
                'Numéro' => env('MANUAL_MOBILE_MONEY_NUMBER'),
                'Titulaire' => env('MANUAL_MOBILE_MONEY_HOLDER'),
            ],
        ],
        'cash' => [
            'label' => 'Espèces au point de vente',
            'instructions' => 'Réglez le montant total en espèces au point de vente en présentant votre numéro de commande.',
            'details' => [
                'Point de vente' => env('MANUAL_CASH_LOCATION'),
            ],
        ],
    ],

    'currency' => [
        'code' => 'XOF',
        'symbol' => 'FCFA',
        'iso_code' => '952'
    ]
];
