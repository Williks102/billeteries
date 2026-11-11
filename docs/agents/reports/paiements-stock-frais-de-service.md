# Suppression des frais de service (branche claude/frais-de-service)

Décision du propriétaire : la plateforme ne se rémunère que par la commission ; l'acheteur paie le prix des billets.

## Code
- `CheckoutService::SERVICE_FEE` supprimée (aucun code historique n'en dépendait). `total_amount` = somme des lignes (connecté, invité, direct : même `createOrder`).
- `Order::calculateCommission` : assiette = somme des `order_items.total_price` (repli `total_amount` si aucune ligne). Taux inchangé (promoteur > catégorie > défaut ; 10 % sans réglage). `platform_fee_fixed` n'est plus lu ; `commission_amount = round(taux x sous-total)`, `net = sous-total - commission`, `gross = sous-total`, `platform_fee = 0`.
- `CommissionCalculator` (widget) : mêmes règles, frais à 0.
- Vues : panier (desktop, mobile, JS de mise à jour des totaux), checkout connecté/invité, confirmation, manual-payment, email d'instructions, email promoteur, calculateur de gains promoteur (+ JS), pricing (+ JS), terms art. 6 et art. 8 (paiement manuel).

## Exceptions volontaires au grep
- `docs/agents/reports/*` (historique), `tests/Feature/NoServiceFeeTest.php` (garde-fou), commentaire de `Order::calculateCommission`, nom du test dans `composer.json`.
- Colonnes `commissions.platform_fee`, `commission_settings.platform_fee_fixed` conservées (pas de migration) ; seeder `CommissionSettingSeeder` laisse des `platform_fee_fixed` (500/300/...) désormais IGNORÉS ; `ExcelExportService` exporte toujours la colonne platform_fee (0 pour les nouvelles commandes). `OrderItem::calculateCommission` et `Commission::calculateAmounts` gardent un paramètre de frais à 0 par défaut (non appelés avec des frais).

## Impact sur les statistiques (mission suivante)
- Les commandes historiques gardent `total_amount` = sous-total + 500 et `commission_amount` = 10 % x total + 500 : AUCUNE donnée réécrite. Les sommes de `total_amount` mélangent donc deux régimes. Pour un revenu plateforme cohérent : somme de `commissions.commission_amount` (commandes `paid`) ; revenu promoteur = `net_amount`. Pour un panier moyen / dépenses acheteur : préférer `SUM(order_items.total_price)`.
- Les anciennes commissions incluent 500 de platform_fee dans `commission_amount` (ancien régime) ; si l'on veut un historique homogène, une mission de rétro-calcul est nécessaire (décision propriétaire).

## Textes
- Pricing : commission présentée à 10 % (taux par défaut du code, la page affichait 8 % : incohérence corrigée, à CONFIRMER par le propriétaire), sans frais techniques ; « prix affiché = prix payé ». CGU art. 6 : plus de frais de service/de paiement ; art. 8 : phrase « 500 FCFA par commande » retirée. Clauses de remboursement/annulation et placeholder non touchés.
- Pas de ligne « frais » dans how-it-works/welcome (aucune mention trouvée).

## Preuves
- `composer run check` : 8 tests (routes/vues) + 167 tests (7357 assertions), ~6,3 s, vert. `php artisan route:list` : 215 routes.
- Mutation (worktree jetable, +500 remis dans le total et dans la commission) : 6 tests sur 8 de NoServiceFeeTest échouent (totaux x3, commission x3).
