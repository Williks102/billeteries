# Sécurité — suppression des frais de service (revue ciblée)
1. Total serveur : VALIDÉ. CheckoutService::buildEventGroups (l.78-112) recalcule unit = TicketType::price × quantité int>=1 ; createOrder l.307 = subtotal serveur. Plus aucune variable $finalTotal/$serviceFee/SERVICE_FEE dans app/ ni resources/ (grep). Vues n'affichent que $cartTotal (affichage seul).
2. Commissions : RÉSERVE (Faible). Order::calculateCommission l.156-179 : assiette = somme order_items (ancienne commande +500 non commissionnée, pas de contournement) ; fallback total_amount seulement sans lignes. Aucun contrôleur promoteur n'écrit CommissionSetting (grep app/Http). Net plafonné par max(0) (CurrencyHelper l.87). Taux non borné dans CurrencyHelper (0-100 non forcé) : défense en profondeur seulement, admin-only.
3. Admin : VALIDÉ. Aucun contrôleur ne met à jour total_amount (seuls des sum()) ; total_amount reste en $fillable mais uniquement alimenté par createOrder (pré-existant).
4. Régressions M1/M2/C1/E1/E2/plafonds/QR/vérif publique : VALIDÉ (diff n'y touche pas ; I1 intact l.96-101).
5. Fuite : VALIDÉ (diff vues = retrait de lignes, aucune nouvelle donnée de commission exposée).
Constats : Faible — Order::calculateCommission : clamp du taux [0,100] (agent backend). Faible — seeder CommissionSettingSeeder garde platform_fee_fixed (ignoré, inoffensif ; nettoyage optionnel).
PR : oui.
