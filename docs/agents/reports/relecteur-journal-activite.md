# Relecture : journal d'activité (claude/journal-activite)

Verdict : APPROUVÉ SOUS RÉSERVES.
composer run check : OK (268 tests, 3091 assertions) puis OK (68 tests) pour test:deploy ; vert.

## Important
- I1 `app/Http/Controllers/Admin/SettingsController.php:125-136` : « from » du taux de commission lu dans config('app.commission_rate') APRÈS updateDatabaseSettings() (l.462-468, qui fait Config::set('app.commission_rate', nouvelle valeur)) : from == to, toujours. Contredit docs/journal-activite.md (« avant/après »). Aucun test. Correctif : capturer l'ancienne valeur avant updateDatabaseSettings + test. [VÉRIFIÉ par lecture]. Note : le réglage n'est de toute façon pas persisté (préexistant), le journal affirme un changement non durable.

## Mineur
- m1 LoginController.php:35-49 : le nouveau docblock + authenticated() s'intercalent entre le docblock du constructeur et le constructeur (docblock orphelin).
- m2 Un changement de statut admin produit 2 lignes (payment.confirmed + order.status_changed ; failed = order.cancelled + status_changed ; refund = order.refunded + status_changed). Non documenté dans docs/journal-activite.md.
- m3 EventActivityObserver TRACKED contient 'address' : la clé est retirée par scrub, la description la cite mais le détail est vide.
- m4 UserController::bulkAction : $count = lignes traitées (avant : lignes modifiées, sous MySQL) ; N+1 en écriture borné par la sélection admin ; exclusion de soi-même conservée (filtre inchangé).
- m5 Contournement par requête de masse (ActivityLog::query()->delete()/truncate) possible, hors périmètre, à signaler à securite ; pas de trigger.

## Vérifié
- confirmPayment : verrou, idempotence, statut, QR après commit inchangés (diff n'ajoute qu'un record + $wasStatus). record() = DB::afterCommit + try/catch, ne lève jamais ; test échec journal (table supprimée) et test rollback.
- releaseStock(bool $expired=false) : appelants Order.php:268, :421, OrderPaymentService::fail (défaut), ReleaseExpiredOrders (true) ; une seule ligne par libération (statut non pending -> return false avant).
- Migration portable (types génériques, index), down() vide, event_id sans FK, user_id nullOnDelete.
- Observers : pas de boucle ; un diff = une ligne ; quantity_sold non suivi ; deleted conserve le journal.
- Route GET dans le groupe middleware admin ; filtres validés (famille whitelist, acteur ctype_digit, période via ResolvesStatsPeriod), Blade échappé, pagination 25 ; vue sans JS inline, jetons charte.css, cibles 44 px.
- forceFill dans store/toggle/bulk : seul email_verified_at écrit ; comportement succès/erreur inchangé.
- composer.json : tests/Feature/Activity dans test:payments (donc dans check).

## Non vérifié
- Casse volontaire (afterCommit, scrub, modèle ouvert, acteur système, forceFill->update, contrôle d'accès) : tentative en copie jetable interrompue (disque saturé par la copie de vendor/). Évalué par lecture seulement : tests rollback, scrub, append-only, admin-only et AdminEmailVerificationTest semblent détecter ces casses [SUPPOSÉ].
- Suppression d'IP en clair : ipHash lu, non rejoué. MySQL/TiDB non exécuté. route:list 194 non recompté (RoutingConventionsTest vert dans check).
