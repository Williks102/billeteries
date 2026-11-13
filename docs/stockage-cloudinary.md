# Stockage durable des images avec Cloudinary (sans carte bancaire)

Sur Render (offre gratuite), le disque est effacé à chaque déploiement : les affiches d'événements et les avatars disparaissent. **Cloudinary** est une alternative à Cloudflare R2 ([stockage-images.md](stockage-images.md)) : son plan gratuit ne demande pas de carte bancaire. Tout se fait depuis un navigateur de téléphone.

Sans manipulation, l'application continue de fonctionner comme avant (disque local). Rien ne change tant que vous n'avez pas défini `MEDIA_DISK=cloudinary`.

## 1. Créer le compte gratuit
1. Allez sur https://cloudinary.com et choisissez **Sign up for free** (inscription gratuite).
2. Créez le compte avec votre e-mail (ou Google/GitHub), puis confirmez l'e-mail reçu. Aucune carte bancaire n'est demandée pour le plan gratuit.
3. Répondez aux questions de démarrage comme vous voulez (elles ne changent rien).

## 2. Repérer le « Cloud name »
Sur le tableau de bord (Dashboard, ou **Programmable Media**), cherchez **Cloud name** (« nom du cloud ») : un mot comme `dxyz12abc`. Notez-le. Il est aussi visible dans **Settings** (roue crantée) en haut du compte.

## 3. Obtenir la clé d'API
1. Ouvrez **Settings** (roue crantée) puis **API Keys** (« Clés d'API »).
2. Vous y voyez une clé existante, ou créez-en une avec **Generate New API Key**.
3. Notez l'**API Key** (des chiffres) et l'**API Secret** (cliquez sur l'icône « œil » pour l'afficher). Le secret est comme un mot de passe : ne l'envoyez jamais dans une conversation, un message ou le dépôt GitHub. Gardez-le dans un gestionnaire de mots de passe.

Le tableau de bord affiche aussi une ligne « API environment variable » de la forme `CLOUDINARY_URL=cloudinary://…` : c'est exactement ce qu'il nous faut, vous pouvez la copier-coller.

## 4. Composer `CLOUDINARY_URL`
Le format est :

```
cloudinary://<API Key>:<API Secret>@<Cloud name>
```

Remplacez les trois morceaux, sans espace ni chevrons. Cette valeur contient le secret : elle ne va **que** dans Render (étape suivante).

## 5. Renseigner Render
Tableau de bord Render, votre service, **Environment**, ajoutez :

| Variable | Valeur |
|---|---|
| `MEDIA_DISK` | `cloudinary` |
| `CLOUDINARY_URL` | la valeur composée à l'étape 4 |
| `CLOUDINARY_FOLDER` | facultatif : un dossier de rangement, par exemple `billetterie` (vide par défaut) |
| `CLOUDINARY_TRANSFORMATIONS` | facultatif : défaut `f_auto,q_auto` (format et qualité optimisés automatiquement) |

Enregistrez : Render redéploie. Sinon, **Manual Deploy > Deploy latest commit**.

## 6. Vérifier
1. Connectez-vous comme promoteur, créez ou modifiez un événement et téléversez une image (JPG, PNG ou GIF).
2. L'image doit s'afficher. Clic long sur l'image pour copier son adresse : elle commence par `https://res.cloudinary.com/<votre cloud name>/image/upload/`.
3. Dans Cloudinary, **Media Library** (Assets) : un dossier `events/` (précédé de votre `CLOUDINARY_FOLDER` s'il existe) contient l'image.
4. Redéployez : l'image doit toujours s'afficher.

Si le téléversement échoue, l'application affiche une erreur et écrit la cause dans les Logs de Render (cherchez « Stockage des images » : le journal cite le nom de la variable à corriger, jamais sa valeur). Si les pages s'affichent sans image, vérifiez que `CLOUDINARY_URL` est complète et sans faute.

## Reprendre d'anciennes images
Sur Render, le plus probable est de repartir de zéro (retéléverser les affiches). Si vous avez des fichiers dans `storage/app/public` (en local par exemple) :
```
php artisan media:migrate --dry-run   # simulation, sans rien écrire
php artisan media:migrate             # copie réelle (ancien nom : media:migrate-to-r2, identique)
```
La commande copie `events/` et `avatars/` vers le disque média, ignore ce qui existe déjà et ne supprime rien. Elle refuse de s'exécuter si `MEDIA_DISK` désigne un disque local.

## Retour arrière
Supprimez la variable `MEDIA_DISK` (ou mettez `public`) dans Render et redéployez : retour au disque local. Les chemins en base sont relatifs (`events/xxx.jpg`) et ne changent pas ; les images déjà envoyées restent dans Cloudinary mais ne s'afficheront plus. Pour passer à R2 plus tard, changez `MEDIA_DISK` et utilisez `media:migrate` depuis un environnement qui a encore les fichiers.

## Limites et comparaison avec R2
- Plan gratuit Cloudinary : un quota mensuel de stockage, de bande passante et de transformations, partagé en « crédits ». **Vérifiez les quotas actuels sur le site de Cloudinary** (page des tarifs) : ils changent. Au dépassement, le compte peut être limité ou demander de passer à un plan payant.
- R2 (Cloudflare) : stockage brut, 10 Go gratuits et sortie de données gratuite, mais carte bancaire obligatoire à l'activation. Cloudinary : pas de carte, optimisation d'image automatique (`f_auto,q_auto`), mais quotas en crédits à surveiller.
- Seules les images JPEG, PNG, GIF et WebP sont acceptées par le disque (vérification du type du fichier en plus de celle du formulaire). Les fichiers sont nommés par un hash, jamais par leur nom d'origine.
- Les envois sont toujours signés avec votre clé API et votre secret (aucun « upload preset » public). La clé et le secret n'apparaissent jamais dans les adresses d'images ni dans les logs.
- En cas de fuite du secret : régénérez la clé dans Settings > API Keys et mettez à jour `CLOUDINARY_URL` dans Render.
- Limite honnête : le code est testé avec un faux client uniquement (aucun compte Cloudinary réel n'a été utilisé). Le premier téléversement réel (étape 6) est la vraie vérification ; le format d'adresse des images est à confirmer à ce moment-là.
