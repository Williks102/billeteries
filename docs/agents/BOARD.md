# Tableau de coordination des agents

Légende statut : todo / en cours / fait / bloqué. Voir `.claude/skills/billeterie-conventions/SKILL.md`.

| ID | Agent | Statut | Fichiers touchés | Dépend de | Notes |
|----|-------|--------|------------------|-----------|-------|
| P5,P6,P7 | devops-hygiene | fait | .gitignore, bootstrap/providers.php, config/telescope.php, UserSeeder, DatabaseSeeder, .env.example, suppression backup_migration.php/admin.old/partials.old | – | rapport docs/agents/reports/devops-hygiene-p5-p7.md ; route:list exige PAIEMENTPRO_MERCHANT_ID (service instancié au boot, hors périmètre) |
| P1,P2,P3 | paiements-stock | fait | bootstrap/app.php, VerifyCsrfToken (supprimé), PaiementProService, PaiementProController, config/payments.php, Order | – | rapport docs/agents/reports/paiements-stock-p1-p4.md ; securite à re-valider ; qa-tests : voir rapport |
| P4 | paiements-stock | fait | Checkout*, GuestCheckout*, CheckoutService (nouveau), TicketType, Order, migration orders.expires_at/paid_at, ReleaseExpiredOrders, routes/console.php, composer.json (check étendu), tests/Feature/{PaiementProWebhookSecurity,StockAndWebhookFlow}Test | P1-P3 | non testé sur MySQL réel ; CheckoutService amorce D3 (architecte à relire) |
| C1 | paiements-stock | fait | PaiementProController (return/cancel), GuestCheckoutController, CheckoutService, tests/Feature/StockAndWebhookFlowTest | P1-P4 | fuite guest_token corrigée (preuve de session) ; securite à re-valider ; rapport paiements-stock-p1-p4.md section C1 |
| D5 | securite | todo | Policies, Form Requests (avec architecte) | audit | autorisations |
| D2 | architecte | fait | routes/*.php, Admin/AnalyticsController, Acheteur/{Order,Ticket,Profile}Controller, Promoteur/ProfileController | audit | routes en convention ressource ; test RoutingConventionsTest |
| D1,D3,D4 | architecte | todo | contrôleurs géants, CheckoutService, rôles | D2 | refactor |
| R1 | paiements-stock / architecte | todo | favoris, categories extras, cart extend-timer/status, intervention-events | D2 | routes pointant vers méthodes inexistantes |
| R2 | architecte | todo | public/js/admin-users-*.js | D2 | JS appelle /admin/users/{check-email,resend-verification,verify-email} : routes absentes |
| R3 | architecte | todo | Promoteur/SalesController::{reports,export} | D2 | code mort (doublon de ReportsController) |
| D7 | qa-tests | en cours | tests/, composer run check, .githooks (pas de GitHub Actions) | P1-P4 | caractérisation |
| UI | ui-design | todo | resources/views, public/css | D1-D3 | accessibilité, mobile |
| SEC-P1-P4 | securite | fait | docs/agents/reports/securite-p1-p4.md | – | audit P1-P4 : 4 Critiques confirmés ; + cancel public (IDOR), double incrément quantity_sold ; à implémenter par paiements-stock |
| RETRAIT-PP | paiements-stock | fait | PaiementProService/Controller supprimés, OrderPaymentService, CheckoutService, EmailService, checkout/*, admin/orders, config/payments, routes web+admin, bootstrap/app, tests | P1-P4, C1 | rapport docs/agents/reports/paiements-stock-retrait-paiementpro.md ; branche claude/retirer-paiementpro ; securite à re-valider ; qa-tests : voir rapport ; User::$fillable (is_guest) à corriger par le propriétaire |
| SEC-RETRAIT-PP | securite | fait | docs/agents/reports/securite-retrait-paiementpro.md | RETRAIT-PP | NON VALIDÉ : E1 (prise de contrôle compte invité via forceFill), E2 (stock hoarding sans throttle), M1/M2 (transitions admin sans libération stock) ; à corriger par paiements-stock |
| QA-MANUEL | qa-tests | fait | tests/Feature/{ManualPaymentJourney,OrderExpiry,ConfirmPaymentAuthorization,AdminOrderStatusPaths,CheckoutValidation}Test, tests/Known/KnownBugsTest, tests/Concerns, composer.json (scripts) | RETRAIT-PP | check vert (62 tests) ; 2 bugs rouges hors check (script test:known-bugs) : failed admin puis paid double le stock ; CGU invité accepte "0" -> paiements-stock ; rapport docs/agents/reports/qa-tests-paiement-manuel.md |
| FIX-SEC-PM | paiements-stock | fait | GuestCheckout/Checkout/Admin OrderController, OrderPaymentService, CheckoutService, Order, User, EmailService, routes/web.php (throttle), config/payments, .env.example, views checkout, migration admin_notes, tests, composer.json | SEC-RETRAIT-PP | E1, E2, M1, M2, M3, CGU, F2, F3 corrigés ; F1/F4 non traités ; tests/Known supprimé ; securite à re-valider ; qa-tests : voir tests GuestCheckoutSecurity/OrderStatusTransitions ; rapport docs/agents/reports/paiements-stock-correctifs-securite.md |
