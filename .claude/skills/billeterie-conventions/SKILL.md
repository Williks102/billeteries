---
name: billeterie-conventions
description: Contexte, règles et protocole de coordination communs à tous les agents du projet Billetterie (Laravel 12). À charger par tout agent avant de travailler.
user-invocable: false
---

# Contexte projet
Billetterie en ligne (Côte d'Ivoire) : Laravel 12, PHP 8.2, rôles admin / promoteur / acheteur, panier, checkout classique + invité, paiement PaiementPro (webhook), QR codes, exports Excel/PDF, Spatie Permission (installé, peu utilisé).

# Problèmes connus (check-up du 2026-11-11) — source de vérité des priorités
P1 Webhook `/payments/paiementpro/notification` soumis au CSRF (419) ; `except` vide.
P2 `verifyNotificationHashCode` retourne true si secret vide ; mode test par défaut.
P3 Webhook sans idempotence : pas de contrôle montant / statut déjà payé / verrou.
P4 Stock `quantity_sold` incrémenté sans `lockForUpdate` ni revérification ; pas de libération des réservations expirées.
P5 `bootstrap/providers.php` référence `App\Providers\TelescopeServiceProvider` inexistant ; Telescope en require-dev.
P6 Seeders avec mots de passe faibles affichés en console.
P7 `.gitignore` corrompu (1re ligne `cat > .gitignore << 'EOF'`) ; versionnés : database.sqlite, backup_migration.php, *.backup, admin.old/, partials.old/.
D1 Contrôleurs géants (AdminController 1084 l., Admin/EventController 908…).
D2 ~15 closures d'analytics dans routes/web.php ; routes de test/diagnostic ; routes dupliquées.
D3 CheckoutController et GuestCheckoutController dupliqués ; pas de CheckoutService.
D4 Rôles maison + Spatie en doublon ; mélange promoteur/promoter.
D5 Pas de Form Requests ni Policies ; TicketController::download laisse passer les rôles autres qu'acheteur/promoteur.
D6 Emails/PDF synchrones malgré la table jobs.
D7 Aucun test réel, pas de CI, pas de .env.example, README par défaut.
D8 Logs trop bavards (`$request->all()`).

# Règles pour tous
1. Langue : français pour les rapports et messages de commit ; code et identifiants en anglais comme l'existant.
2. Branche de travail : `ccr-ac4ce980-b71gip`. Jamais de push ailleurs, jamais de PR sans demande explicite.
3. Un agent ne modifie QUE les fichiers de son périmètre (voir sa fiche). Hors périmètre : le signaler dans le BOARD, ne pas toucher.
4. Ne jamais inventer : citer `fichier:ligne` pour toute affirmation. Distinguer « vérifié » de « supposé ».
5. Aucun secret réel dans le dépôt, les logs ou les rapports.
6. Changement petit et atomique ; lancer `vendor/bin/pint --test` et `php artisan test` si l'environnement le permet ; sinon dire explicitement que ce n'est pas vérifié.

# Protocole de coordination
- Tableau partagé : `docs/agents/BOARD.md`. Chaque agent LIT le tableau avant de commencer, puis y AJOUTE une ligne par tâche (ne réécrit pas celles des autres).
- Format d'une ligne : `| ID | Agent | Statut (todo/en cours/fait/bloqué) | Fichiers touchés | Dépend de | Notes |`
- Rapport détaillé dans `docs/agents/reports/<agent>-<sujet>.md` (audit : constats classés Critique/Élevé/Moyen/Faible, avec preuve et correctif proposé).
- Fin de mission : répondre au chef d'orchestre en ≤ 15 lignes : fait / non fait / bloquants / prochaine étape recommandée.
- Ordre de dépendance : devops-hygiene → securite & architecte (audit) → paiements-stock → qa-tests → ui-design → revue finale.
