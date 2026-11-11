# Commission par défaut 6 % (paiements-stock)

Décision propriétaire : taux par défaut = 6 % du prix des billets (assiette = sous-total, platform_fee = 0, inchangés).

## Source unique
`config('payments.default_commission_rate')` (config/payments.php, env `DEFAULT_COMMISSION_RATE`, défaut 6, documentée dans .env.example). Lue par Order::calculateCommission, CommissionCalculator, pricing.blade.php et le composant calculateur ; les deux scripts JS reçoivent le taux via `data-commission-rate` (plus de constante).

## Exemple
5 000 F : avant (code 10 %) commission 500, net 4 500 ; page tarifs affichait 8 % ; après : commission 300, net 4 700. 2 x 10 000 F : commission 1 200, net 18 800, platform_fee 0.

## Seeder
CommissionSettingSeeder : défaut ET catégories (concert 8, théâtre 12, sport 7, conférence 15, festival 9) alignés sur 6.00 ; mécanisme promoteur > catégorie > défaut intact. platform_fee_fixed/min_commission du seeder inchangés (déjà ignorés par le calcul). UpdateCommissionsDataSeeder : aucun taux codé.

## Non modifié (documenté)
- Migrations existantes : `order_items.commission_rate` default 10.00 (2025_07_12_141627) et `settings.commission_rate` = '10.0' (2025_07_29_214626). Non utilisés pour le calcul (CheckoutService fournit toujours le taux ; la ligne settings n'est pas lue par le calcul). Pas de migration nécessaire ; à signaler si un autre code lit `settings.commission_rate`.
- Les taux promoteur/catégorie déjà en base de production ne sont PAS modifiés par le seeder : à aligner via l'admin si voulu.
- PageController::pricing a un tableau `$pricing` (0 %/5 %/3 %) non utilisé par la vue (hors périmètre).
- Bornage 0-100 du taux : non traité (hors périmètre).
- Correctif annexe : le composant promoter-earnings-calculator s'incluait lui-même (exemple d'usage après `<?php`) = récursion infinie au rendu ; exemple retiré.

## Tests
tests/Feature/DefaultCommissionRateTest.php (8 tests : défaut 6, 2x10 000, priorité promoteur/catégorie, CommissionCalculator, arrondi 1000/999/0, page tarifs 6 % + 4 700 F sans 10/8 %, cohérence data-attribute/config, garde JS) ; NoServiceFeeTest adapté (1 200/18 800). Ajouté à `composer run check`. Mutation (défaut 10) : 6 tests rouges.
A re-valider par securite : rien de sensible (pas d'accès/entrée utilisateur ; taux lu en config côté serveur). qa-tests : scénario = commande réelle de bout en bout sans réglage -> Commission 6 %.
