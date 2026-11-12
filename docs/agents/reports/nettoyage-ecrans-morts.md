# Nettoyage du code mort et des écrans cassés

Branche `claude/nettoyage-ecrans-morts`. Suppression seulement, aucune fonctionnalité ajoutée. Routes : **215 avant, 200 après** (`php artisan route:list`, -15).
`composer run check` : vert (121 + 250 + 164 + 54 tests).

## Supprimé

| Supprimé | Fichier | Pourquoi | Vérifié par |
|---|---|---|---|
| 12 routes `admin/analytics*` (index, data, filter, export, realtime, events, promoters, compare, cart-abandonment, sales-heatmap, cohort, predictions) | routes/admin.php | Décision propriétaire (valeurs inventées, SQL MySQL-only) | route:list (aucune route non admin n'utilise `AnalyticsController`), grep routes/*.php |
| `AnalyticsController` | app/Http/Controllers/Admin/AnalyticsController.php | Plus aucune route | grep : seul usage = routes/admin.php |
| `analytics`, `analyticsData`, `analyticsFilter`, `analyticsExport` + helpers `getAnalyticsDateRange`, `calculateConversionRate`, `calculateRetentionRate`, `calculatePaymentSuccessRate`, `getRevenueEvolution`, `generateAnalyticsExportData` | Admin/AdminController.php | Exclusivement utilisés par analytics | grep de chaque helper (aucun autre appelant) |
| Vue analytics | resources/views/admin/analytics.blade.php | Plus rendue | grep |
| Entrée de menu « Analytics » et bouton du tableau de bord | layouts/admin.blade.php, admin/dashboard.blade.php | Lien vers route supprimée | grep `admin.analytics` |
| Routes `manage-tickets`, `store-tickets`, `audit-history` | routes/admin.php | Écrans cassés (décision 2) | route:list, grep |
| `manageHybrid`, `manageTickets`, `storeTickets`, `auditHistory` | Admin/EventController.php | Vues inexistantes, `Event::adminHasPermission`/`auditLogs`/`logAction` inexistants | grep |
| `storeWithMode`, `changeManagementMode` (sans route) | Admin/EventController.php | Redirigeaient vers `admin.events.manage-hybrid` (route inexistante) et appelaient `logAction` inexistant | route:list (aucune route), grep |
| Helpers `createTicketTypes`, `getEventInterventionStats` | Admin/EventController.php | Exclusifs aux méthodes supprimées ; le second appelait `auditLogs()` inexistant et `eventNeedsIntervention` inexistant | grep |
| `search`, `category`, `trending`, `byLocation`, `recommendations` | app/Http/Controllers/EventController.php | Méthodes publiques sans route, vues inexistantes | route:list (seule `events/{event}` -> `show`), grep vues/JS |
| `publicVerify` + helper `getPublicStatusMessage` | TicketVerificationController.php | Sans route, vue `tickets.public-verify` inexistante | route:list, grep ; `verify()` et `/verify-ticket/{code}` intacts |
| Vue `checkout/verify-phone` + `public/js/checkout-verify-phone.js` | resources/views/checkout, public/js | Aucun contrôleur ne la rend ; le JS n'était référencé que par elle | grep |
| Entrées `KNOWN_MISSING` (9) | tests/Feature/ViewReferencesTest.php | Liste désormais vide, structure du test gardée | DeadCodeRemovalTest |
| Entrée `admin.events.manage-hybrid` de `UNKNOWN_NAMES` | tests/Feature/RoutingConventionsTest.php | Nom plus utilisé | grep |

## Gardé exprès
- Table `event_audit_logs`, sa migration et le modèle `EventAuditLog` (futur journal d'activité) ; aucune migration touchée.
- `interventionDashboard`, `restorePromoterControl`, `quickPublish`, `quickUnpublish`, `updateStatus`, `bulkAction`, `export` : ne dépendent d'aucune méthode supprimée.
- Route `/verify-ticket/{code}`, `verify()`, `TicketVerificationPrivacyTest`, `checkout.direct`, Chart.js et librairies du tableau de bord. Aucun JS/CSS propre à analytics n'existait dans public/ ni resources/ (grep).

## Non supprimé par prudence (à décider)
- Route `admin.api.intervention-events` : gardée sur demande, mais elle pointe vers `getInterventionEvents`, méthode inexistante (404/500 si appelée ; aucune vue/JS ne l'appelle).
- Autres routes vers des méthodes inexistantes, hors périmètre : favoris acheteur, extras catégories admin, `cart.extend.timer`, `cart.status` (listées dans `KNOWN_ROUTES_WITHOUT_ACTION` du nouveau test).
- Vue d'auth appelant `verification.resend` (route absente) : listée dans le nouveau test.
- Méthodes publiques `EventController::index` et `api` (publiques) sans route, non listées dans la mission ; `Admin\EventController::hybridStats`, `validateAdminPermission`, `getEventPriority`, `notifyPromoterOfModeChange` (hybride, sans route ou appelées seulement par `restorePromoterControl`) conservées.
- `Event::adminHasPermission`, `auditLogs`, `logAction`, `getManagementHistory`, `needsAdminIntervention` n'existent pas dans le modèle : plus aucun appelant actif sauf `hybridStats` (utilise `EventAuditLog::` directement, valide).
- `promoteur/*/reports` utilisent une icône `fa-analytics` (simple icône, laissée).

## Tests
- Supprimés : aucun (aucun test n'exerçait uniquement le code supprimé).
- Adaptés : `ViewReferencesTest` (KNOWN_MISSING vide), `RoutingConventionsTest` (UNKNOWN_NAMES -1).
- Ajouté : `tests/Feature/DeadCodeRemovalTest.php` (8 tests : 404, noms de routes supprimés, aucun lien dans les vues, routes -> méthodes existantes, `route('nom')` statiques des vues existants, menu sans Analytics, fichiers supprimés + modèle gardé, KNOWN_MISSING vide), ajouté à `test:routes`. Les noms de routes dynamiques (`route($var)`, concaténations) sont ignorés : non résolubles par regex.
