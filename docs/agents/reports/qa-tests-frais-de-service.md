# QA – tests I1 (affichage du total sans frais)

Fichier : `tests/Feature/DisplayedTotalNoFeeTest.php` (ajouté à `test:payments`).
- `test_authenticated_checkout_displays_exact_total` (1 ligne ; multi-lignes/événements)
- `test_direct_booking_checkout_displays_exact_total` (session `direct_booking`, même vue `checkout.show` ; `checkout.direct` est POST + redirection)
- `test_cart_page_displays_exact_total` (total, `id="mobileTotal"` initial)
- `test_client_js_and_views_add_no_fixed_amount_to_totals` (garde-fou source : views checkout/cart/emails + public/js/{cart,checkout}*.js ; motifs `+ 500`, `500 +`, serviceFee, service_fee, platform_fee)

Mutations (worktree jetable, supprimé) : `+ 500` dans checkout/show.blade.php:182 -> 5 échecs ; dans cart/show.blade.php:864 -> 3 échecs. Les deux rouges.
Aucun bug applicatif trouvé. Le total du panier est calculé côté serveur ; le JS inline (`updateDesktopTotals`) fait `total = subtotal`.
