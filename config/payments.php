<?php

// ==============================================
// config/payments.php
// Paiement MANUEL : l'acheteur paie hors ligne, un administrateur confirme le paiement.
// ==============================================

return [
    // Durée de réservation d'une commande non payée (minutes) avant libération du stock
    'reservation_minutes' => (int) env('ORDER_RESERVATION_MINUTES', 30),

    // Seuil d'affichage « bientôt complet » : à partir de ce nombre de places restantes ou moins (affichage seulement, aucune logique de stock)
    'low_stock_threshold' => (int) env('LOW_STOCK_THRESHOLD', 10),

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

    // Commandes GRATUITES (total serveur = 0 FCFA) : confirmées immédiatement, donc non limitées par les plafonds
    // « en attente ». Plafonds dédiés, comptés en base : par IP (empreinte HMAC) sur 1 h, par email normalisé sur 24 h.
    // Billets par commande gratuite : même plafond que max_pending_tickets_per_buyer / max_per_order.
    'free_orders_per_ip_per_hour' => (int) env('FREE_ORDERS_PER_IP_PER_HOUR', 5),
    'free_orders_per_email_per_day' => (int) env('FREE_ORDERS_PER_EMAIL_PER_DAY', 5),
    // Compte connecté : plafond par user_id en plus de l'email (changer d'email ne le contourne pas)
    'free_orders_per_user_per_day' => (int) env('FREE_ORDERS_PER_USER_PER_DAY', 5),
    // Billets gratuits par ÉVÉNEMENT et par IP sur 24 h (commandes non annulées) : limite l'épuisement du stock.
    // Les commandes gratuites ne comptent PAS dans le plafond « en attente » de l'événement : elles ne restent jamais
    // en attente (confirmées tout de suite), ce plafond mesure du stock bloqué en attente de paiement.
    'free_tickets_per_event_per_ip_per_day' => (int) env('FREE_TICKETS_PER_EVENT_PER_IP_PER_DAY', 10),

    // Throttle de l'inscription (POST register) : tentatives par minute et par IP
    'register_throttle_per_minute' => (int) env('REGISTER_THROTTLE_PER_MINUTE', 5),

    // Throttle « mot de passe oublié » et « réinitialisation » : tentatives par minute et par IP, et par e-mail
    // (clé = HMAC de l'e-mail normalisé) sur la fenêtre indiquée : 3 demandes de lien / 5 réinitialisations par 10 min.
    'password_reset_throttle_per_minute' => (int) env('PASSWORD_RESET_THROTTLE_PER_MINUTE', 5),
    'password_reset_email_max' => 3,
    'password_reset_email_reset_max' => 5,
    'password_reset_email_window_minutes' => 10,

    // Throttle des POST de checkout : tentatives par fenêtre d'une minute (clé = IP, ou id utilisateur pour un connecté)
    'checkout_throttle_per_minute' => (int) env('CHECKOUT_THROTTLE_PER_MINUTE', 10),

    // Throttle des routes publiques verify-ticket (page et API) : requêtes par minute et par IP
    'verify_ticket_throttle_per_minute' => (int) env('VERIFY_TICKET_THROTTLE_PER_MINUTE', 60),

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

    // Taux de commission PAR DÉFAUT de la plateforme (% du prix des billets), source de vérité unique :
    // lue par Order::calculateCommission, CommissionCalculator, la page de tarifs et les calculateurs JS.
    // Les réglages promoteur / catégorie (table commission_settings) restent prioritaires.
    'default_commission_rate' => (float) env('DEFAULT_COMMISSION_RATE', 6),

    // MODE DÉMO : confirme automatiquement chaque commande à la fin du checkout (référence DEMO-AUTO).
    // DÉSACTIVÉ PAR DÉFAUT. Démo uniquement : les billets deviennent gratuits. Ne JAMAIS l'activer en production.
    // Garde-fou : en APP_ENV=production, le mode démo n'est actif que si DEMO_MODE_ACKNOWLEDGED=true est
    // aussi posé (double confirmation explicite) ; sinon il est forcé à false (voir AppServiceProvider).
    'demo_auto_confirm' => (bool) env('DEMO_AUTO_CONFIRM_PAYMENTS', false)
        && (env('APP_ENV') !== 'production' || (bool) env('DEMO_MODE_ACKNOWLEDGED', false)),
    'demo_blocked_in_production' => (bool) env('DEMO_AUTO_CONFIRM_PAYMENTS', false)
        && env('APP_ENV') === 'production' && !(bool) env('DEMO_MODE_ACKNOWLEDGED', false),

    'currency' => [
        'code' => 'XOF',
        'symbol' => 'FCFA',
        'iso_code' => '952'
    ]
];
