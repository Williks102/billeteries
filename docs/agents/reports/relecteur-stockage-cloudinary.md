# Relecture : stockage Cloudinary (branche claude/stockage-cloudinary, commit 6590f66)

Verdict : APPROUVE SOUS RESERVES.

## Execution
`composer run check` : OK (247 tests, 1789 assertions) / OK (250) / OK (268) / OK (97, dont CloudinaryDiskTest 33 tests). Aucun reseau (faux gateway + client PSR-18 capturant).

## Verifie (points 1 a 9)
- Defaut inchange : `media_disk` reste `public` (test + config) ; pilote r2 non modifie (Media::assertConfigured/url : ajouts en bloc separe). Le pilote est enregistre paresseusement (`Storage::extend`), aucune erreur au boot sans CLOUDINARY_URL.
- Secret : aucun `cloudinary://cle:secret@` dans le diff hors faux secret de test ; .env.example, render.yaml (sync:false), docs : placeholders. URL publique = cloud name + transformations + public_id (aucune cle). Messages d'exception = classe de l'exception SDK seulement (Adapter.php:98,108,126,138).
- Fichiers : extension (Adapter.php:~215) ET MIME finfo (l.~90) ; `..`, `.`, NUL refuses, `/` initial retire (cleanPath) ; public_id et URL encodes segment par segment ; copy() n'utilise que originalUrl(config + chemin nettoye), pas de SSRF ; aucune entree utilisateur dans les transformations (config).
- Fonctionnel : upload signe via SDK (api_key/secret, pas de preset) ; prefixe de dossier unique via publicId() pour write/delete/copy/exists/url ; copy = nouvel upload (ressource independante) ; getUrl hors reseau, suffixe d'extension seulement sans `f_`.
- Migration : alias media:migrate, refuse disque local, --dry-run sans ecriture, exists() avant copie (idempotent), ne supprime rien, erreurs par fichier comptees.
- Portee : composer.json n'ajoute que cloudinary_php ; composer.lock n'ajoute que cloudinary_php 3.3.0 et transformation-builder-sdk 2.1.5 (transitif), aucune ligne retiree/mise a jour. Rien hors mission.
- Doc : variables nommees comme dans le code, secret uniquement dans Render, avertissement de ne jamais le coller en conversation/depot.

## Mutations (copie jetable dans le scratchpad, branche intacte)
Rouges : retrait controle extension (2 tests), retrait controle MIME (1), delete sans prefixe de dossier (1), copy depuis chemin brut (2), retrait garde `..` (1), upload non signe (1).
VERT (non detecte) : message d'exception du SDK recopie dans UnableToWriteFile (fuite du message du SDK).

## Constats
Bloquant : aucun.

A corriger (Important)
- I1 `tests/Feature/CloudinaryDiskTest.php` : aucun test du chemin d'echec du gateway. FakeCloudinaryGateway (l.29-56) ne leve jamais ; la mutation Adapter.php:98 `'envoi refuse '.$e->getMessage()` reste verte, donc la promesse « jamais de secret dans un message » (l.97 commentaire, doc) n'est verifiee que pour la config, pas pour une erreur SDK. Correctif : gateway qui leve une exception contenant le faux secret ; verifier que getMessage() de UnableToWriteFile/DeleteFile/CopyFile/CheckExistence ne le contient pas. Agent : qa-tests.
- I2 `app/Actions/Events/DuplicateEventAction.php:81` (non modifie) : `Media::disk()->exists()` appelle l'API Admin Cloudinary (limitee en debit) ; une erreur leve UnableToCheckExistence (throw=true), copyImage() est hors du try, le controleur affiche seulement « Erreur lors de la duplication de l'evenement » (Promoteur/EventController.php:310-313). Echec propre (pas de 500, pas d'orphelin) mais sans cause claire et aucun test. Correctif : test de duplication avec exists() qui leve ; option : creer le brouillon sans image avec avertissement. Agent : backend + qa-tests.

Mineur
- M1 Media.php:90-94 : `Log::error` a chaque appel de Media::url() quand CLOUDINARY_URL est invalide (une ligne par image/page : inondation de logs, meme schema que R2).
- M2 SdkCloudinaryGateway : `report=>true` journalise la chaine previous du SDK ; non contenant le secret a la lecture du SDK (auth Basic en en-tete, signature en corps) mais [SUPPOSE], non teste.
- M3 media:migrate : un exists() Admin API par fichier (quota) ; en cas de limitation, tout apparait « echec » sans message dedie.
- M4 DuplicateEventAction n'accepte pas webp pour l'extension (prexistant), la copie webp devient `.jpg` (sans effet visible avec f_auto).

## Non verifie
Aucun appel a un vrai compte Cloudinary : format d'URL de livraison, nom d'exception NotFound sur l'API Admin, comportement f_auto reels ; [SUPPOSE] conforme au SDK lu (NotFound present dans vendor). MySQL non concerne.

## Ecart declare/reel
Le rapport affirme « jamais de cle dans les messages » : vrai dans le code, mais non protege par un test (I1).

Recommandation : fusionner apres I1 (rapide) ; I2 au choix du proprietaire.
