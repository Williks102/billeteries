# Audit sécurité : branche claude/journal-activite

Verdict : OK avec réserves. Aucun constat critique ni élevé.

## Moyens
1. Création d'un compte (dont un admin) non journalisée : `UserActivityObserver` n'a ni `created` ni `deleted`
   (`app/Observers/UserActivityObserver.php:266`). Un admin créé via `UserController::store`
   (`app/Http/Controllers/Admin/UserController.php:~70`) ne laisse aucune trace dans le journal ; la suppression de compte non plus.
   Correctif : ajouter `created` (id, rôle) et `deleted` (id, rôle), en id seulement. Exécutant : backend.
2. Append-only applicatif seulement : les garde-fous (`ActivityLog.php:205-226`) ne couvrent pas
   `ActivityLog::query()->delete()/update()`, `DB::table('activity_logs')`, `truncate`, `withoutEvents`.
   Aucune route ni code actuel ne le fait (vérifié), donc risque latent. Correctif : droits DB
   (pas de UPDATE/DELETE pour l'utilisateur applicatif) ou trigger ; test d'architecture interdisant `activity_logs` hors `ActivityLogger`.

## Faibles
- Filtre de clés (`ActivityLogger.php:24,87-101`) : récursif, insensible à la casse, par fragment : robuste pour les clés listées.
  Manques : `mail` seul, `tel`/`mobile`, `ip`, `iban`, `card`, `authorization`, `jwt`, `guest`. Il ne filtre pas les valeurs
  (une valeur libre sous une clé neutre passerait) ; les appelants actuels n'en fournissent aucune. Ajouter ces fragments.
- Le fragment `address` supprime silencieusement le diff de l'adresse d'événement (`EventActivityObserver.php:238`) alors que la
  description affiche « address » modifié : incohérence, pas une fuite. Retirer `address` de TRACKED ou renommer.
- L'écran affiche le nom des acteurs (y compris acheteurs/promoteurs) : donnée personnelle minimale, visible des admins seuls ;
  acceptable, à mentionner dans la politique de confidentialité interne.
- `LoginController.php:~35` : le nouveau docblock s'intercale avant le docblock du constructeur (cosmétique). Les échecs de
  connexion admin ne sont pas journalisés (choix de périmètre).
- Pré-existant, non attribué à la branche : `UserController.php:84-91` loggue `new_user_email` et `admin_name` (PII dans les logs
  applicatifs) ; `SettingsController` loggue `request->only(...)`. Remplacer par les seuls ids.

## Points vérifiés conformes
1. Autorisation : route `admin/activity` dans `routes/admin.php`, groupe `middleware('admin')` (`routes/web.php:145`),
   `AdminMiddleware` -> 403 non-admin, redirection login pour invité ; testé (`ActivityLogScreenTest.php:51-54`).
   Une seule route GET, aucune écriture ni export. Liens sujets vers des routes `admin.*` déjà protégées, sans IDOR.
2. Aucune PII : descriptions construites avec ids, `order_number`, statuts ; pas de référence de paiement libre. Empreinte IP =
   `hash_hmac('sha256', ip, config('app.key'))` (`CheckoutService.php:579-582`), pas de hash nu ; null en console. `UserActivityObserver`
   ne lit que `role` et `email_verified_at` (booléen), jamais password/remember_token/email.
3. Intégrité : aucun contenu utilisateur dans `description` (titre d'événement absent, seulement dans `properties` des diffs,
   échappé en Blade `{{ }}` ; aucun `{!! !!}`). Filtre `family` validé par liste blanche, `actor` par `ctype_digit` / `system`,
   `LIKE` sur préfixes constants, pagination fixe à 25, aucun SQL brut.
4. Fiabilité : `record()` ne lève jamais (double try/catch), écrit via `DB::afterCommit` (rien si rollback), aucune requête
   ajoutée dans les sections verrouillées (`releaseStock`, `confirmPayment`, `refund`), donc pas de nouvelle condition de course.
   `quantity_sold` non journalisé : pas de surcharge dans le checkout.
5. `forceFill` limité à `email_verified_at` : pas de mass assignment ; `role` validé `in:admin,promoteur,acheteur` et
   `store` derrière le middleware admin ; `toggleEmailVerification` refuse son propre compte ; `bulkAction` exclut le compte courant
   et ne touche pas au rôle.
6. Les nouveaux `Log::warning` ne contiennent que l'action et la classe de l'exception.
7. Aucun secret dans le diff ; les tests n'utilisent que des jeux factices `*.example.test`.

Note : `php artisan test tests/Feature/Activity` n'a rien exécuté (25 avertissements `file_get_contents`, probablement
environnement du bac à sable) : tests non confirmés par cet audit. Relancer `composer run check` en CI.
