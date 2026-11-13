# Stockage durable des images (Cloudflare R2)

Sur Render (offre gratuite), le disque est effacé à chaque déploiement : les affiches d'événements et les avatars disparaissent. Ce guide branche les images sur **Cloudflare R2** (stockage compatible S3, gratuit jusqu'à 10 Go). Tout se fait depuis un navigateur de téléphone.

**Pas de carte bancaire ?** Cloudflare en demande une pour R2. L'alternative gratuite sans carte est Cloudinary : voir [stockage-cloudinary.md](stockage-cloudinary.md) (`MEDIA_DISK=cloudinary`).

Sans aucune manipulation, l'application continue de fonctionner comme avant (disque local). Rien ne change tant que vous n'avez pas défini `MEDIA_DISK=r2`.

## 1. Créer le compte Cloudflare
1. Allez sur https://dash.cloudflare.com/sign-up, créez un compte (e-mail + mot de passe) et confirmez l'e-mail.
2. Dans le menu (icône en haut à gauche), ouvrez **Stockage et bases de données** puis **R2** (parfois « R2 Object Storage »). Cloudflare demande une carte bancaire pour activer R2 : le plan gratuit (10 Go, 1 million d'écritures et 10 millions de lectures par mois) suffit pour une démo et ne débite rien tant qu'il n'est pas dépassé.

## 2. Créer le bucket
1. **Créer un bucket**, nom par exemple `billetterie-images` (minuscules, tirets).
2. Emplacement : automatique. Classe de stockage : Standard. Validez.

## 3. Rendre les images lisibles publiquement
Les images doivent s'afficher dans le navigateur des visiteurs, donc le bucket doit avoir une adresse publique.
1. Ouvrez le bucket, onglet **Paramètres**.
2. Section **Accès public** / **URL publique de développement** (r2.dev) : cliquez **Activer**, tapez `allow` si demandé, confirmez.
3. Copiez l'URL affichée, de la forme `https://pub-xxxxxxxx.r2.dev` : c'est `R2_PUBLIC_URL`.
4. Plus tard, pour un vrai site, préférez un **domaine personnalisé** (même section) : l'adresse r2.dev est limitée en débit et réservée aux tests.

## 4. Noter l'ID de compte
Sur la page R2, l'**ID de compte** (32 caractères) est affiché à droite, ou dans l'adresse du tableau de bord. L'endpoint est :
`https://<ID_DE_COMPTE>.r2.cloudflarestorage.com` (c'est `R2_ENDPOINT`, sans « / » final et sans le nom du bucket).

## 5. Créer le jeton d'API (clés d'accès)
1. Page R2, **Gérer les jetons d'API** (Manage API tokens), puis **Créer un jeton API**.
2. Autorisations : **Lecture et écriture d'objets** (Object Read & Write).
3. Portée : **Appliquer à des buckets spécifiques uniquement**, choisissez votre bucket.
4. Créez. Cloudflare affiche UNE SEULE FOIS l'**ID de clé d'accès** (`R2_ACCESS_KEY_ID`) et la **clé d'accès secrète** (`R2_SECRET_ACCESS_KEY`). Copiez-les tout de suite dans un gestionnaire de mots de passe. Ne les envoyez jamais dans une conversation, un message ou le dépôt GitHub. Ignorez la « valeur du jeton » : elle ne sert pas ici.

## 6. Renseigner Render
Tableau de bord Render, votre service, **Environment**, ajoutez :

| Variable | Valeur |
|---|---|
| `MEDIA_DISK` | `r2` |
| `R2_ACCESS_KEY_ID` | l'ID de clé d'accès |
| `R2_SECRET_ACCESS_KEY` | la clé d'accès secrète |
| `R2_BUCKET` | le nom du bucket (`billetterie-images`) |
| `R2_ENDPOINT` | `https://<ID_DE_COMPTE>.r2.cloudflarestorage.com` |
| `R2_PUBLIC_URL` | `https://pub-xxxxxxxx.r2.dev` (sans « / » final) |

Enregistrez : Render redéploie. Sinon, **Manual Deploy > Deploy latest commit**.

## 7. Vérifier
1. Connectez-vous comme promoteur, créez ou modifiez un événement et téléversez une image (JPG ou PNG).
2. L'image doit s'afficher sur la fiche. Faites un clic long sur l'image pour copier son adresse : elle doit commencer par votre `R2_PUBLIC_URL`.
3. Dans Cloudflare, bucket > **Objets** : un dossier `events/` contient le fichier.
4. Déclenchez un nouveau déploiement : l'image doit toujours être là (c'est le but).

Si le téléversement échoue, l'application affiche une erreur et écrit la cause dans les Logs de Render (cherchez « Stockage des images » : le journal indique les variables manquantes, jamais leurs valeurs). Si les pages s'affichent sans image, vérifiez `R2_PUBLIC_URL` et que l'accès public est activé.

## Reprendre d'anciennes images
Sur Render, les images locales sont perdues à chaque déploiement : le plus probable est de **repartir de zéro** (retéléverser les affiches). Si vous avez un environnement avec des fichiers dans `storage/app/public` (par exemple en local) :
```
php artisan media:migrate-to-r2 --dry-run   # simulation
php artisan media:migrate-to-r2             # copie réelle
```
La commande copie `events/` et `avatars/` vers le disque média, ignore ce qui existe déjà (relançable sans risque) et ne supprime rien.

## Retour arrière
Supprimez la variable `MEDIA_DISK` (ou mettez `public`) dans Render et redéployez : l'application reprend le disque local. Les chemins en base sont relatifs (`events/xxx.jpg`) et ne changent pas ; les images déjà envoyées sur R2 restent dans le bucket mais ne s'afficheront plus.

## Limites et sécurité
- Plan gratuit : 10 Go, quotas mensuels de requêtes ; au-delà, facturation (R2 ne facture pas la sortie de données).
- Adresse r2.dev : limitée, pour tests ; domaine personnalisé conseillé en production.
- CORS : inutile, le site affiche simplement les images avec des balises `<img>`.
- Les fichiers sont nommés par un hash généré (jamais le nom d'origine) et les formats/tailles restent validés comme avant. R2 n'exécute jamais de PHP.
- Les clés ne vont que dans Render ; en cas de fuite, supprimez le jeton dans Cloudflare et recréez-en un.
- Code testé avec des faux disques uniquement : le premier téléversement réel (étape 7) est la vraie vérification.
