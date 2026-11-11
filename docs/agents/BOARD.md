# Tableau de coordination des agents

Légende statut : todo / en cours / fait / bloqué. Voir `.claude/skills/billeterie-conventions/SKILL.md`.

| ID | Agent | Statut | Fichiers touchés | Dépend de | Notes |
|----|-------|--------|------------------|-----------|-------|
| P5,P6,P7 | devops-hygiene | todo | .gitignore, bootstrap/providers.php, seeders, .env.example | – | hygiène dépôt |
| P1,P2,P3 | paiements-stock | todo | VerifyCsrfToken/bootstrap/app.php, PaiementProService, PaiementProController | – | webhook |
| P4 | paiements-stock | todo | Checkout*, TicketType | P1-P3 | stock + verrou |
| D5 | securite | todo | Policies, Form Requests (avec architecte) | audit | autorisations |
| D1-D4 | architecte | todo | routes/web.php, contrôleurs → services | audit | refactor |
| D7 | qa-tests | todo | tests/, .github/workflows | P1-P4 | caractérisation |
| UI | ui-design | todo | resources/views, public/css | D1-D3 | accessibilité, mobile |
