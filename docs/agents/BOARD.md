# Tableau de coordination des agents

Légende statut : todo / en cours / fait / bloqué. Voir `.claude/skills/billeterie-conventions/SKILL.md`.

| ID | Agent | Statut | Fichiers touchés | Dépend de | Notes |
|----|-------|--------|------------------|-----------|-------|
| P5,P6,P7 | devops-hygiene | fait | .gitignore, bootstrap/providers.php, config/telescope.php, UserSeeder, DatabaseSeeder, .env.example, suppression backup_migration.php/admin.old/partials.old | – | rapport docs/agents/reports/devops-hygiene-p5-p7.md ; route:list exige PAIEMENTPRO_MERCHANT_ID (service instancié au boot, hors périmètre) |
| P1,P2,P3 | paiements-stock | todo | VerifyCsrfToken/bootstrap/app.php, PaiementProService, PaiementProController | – | webhook |
| P4 | paiements-stock | todo | Checkout*, TicketType | P1-P3 | stock + verrou |
| D5 | securite | todo | Policies, Form Requests (avec architecte) | audit | autorisations |
| D2 | architecte | fait | routes/*.php, Admin/AnalyticsController, Acheteur/{Order,Ticket,Profile}Controller, Promoteur/ProfileController | audit | routes en convention ressource ; test RoutingConventionsTest |
| D1,D3,D4 | architecte | todo | contrôleurs géants, CheckoutService, rôles | D2 | refactor |
| R1 | paiements-stock / architecte | todo | favoris, categories extras, cart extend-timer/status, intervention-events | D2 | routes pointant vers méthodes inexistantes |
| R2 | architecte | todo | public/js/admin-users-*.js | D2 | JS appelle /admin/users/{check-email,resend-verification,verify-email} : routes absentes |
| R3 | architecte | todo | Promoteur/SalesController::{reports,export} | D2 | code mort (doublon de ReportsController) |
| D7 | qa-tests | en cours | tests/, composer run check, .githooks (pas de GitHub Actions) | P1-P4 | caractérisation |
| UI | ui-design | todo | resources/views, public/css | D1-D3 | accessibilité, mobile |
