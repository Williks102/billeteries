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
