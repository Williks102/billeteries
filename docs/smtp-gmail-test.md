# Tester les e-mails avec un compte Gmail (démo seulement)

Tant que le nom de domaine et l'hébergement définitif ne sont pas en place, l'application n'envoie aucun e-mail
(`MAIL_MAILER=log` : les messages sont seulement écrits dans les journaux de Render). Ce guide permet de **tester**
les vrais envois (confirmation de commande, billets en PDF, mot de passe oublié) avec un compte Gmail dédié.

**Ce n'est pas une solution de production** :
- Gmail limite le nombre de destinataires par jour (de l'ordre de 500 pour un compte gratuit : à vérifier chez Google) et peut suspendre un compte qui envoie en masse ;
- l'expéditeur affiché est toujours l'adresse Gmail du compte (Gmail réécrit les autres adresses) ;
- pour vendre de vrais billets il faudra un SMTP professionnel avec votre propre domaine (voir le comparatif dans la conversation / `docs/PASSATION.md`).

## 1. Un compte Gmail dédié
Créez un compte réservé à l'application (ex. `billetterie.test.xxx@gmail.com`). N'utilisez **pas** votre compte personnel.

## 2. Validation en 2 étapes
Compte Google, Sécurité, **Validation en 2 étapes** : activez-la (nécessaire pour créer un mot de passe d'application).

## 3. Mot de passe d'application
1. Ouvrez `https://myaccount.google.com/apppasswords` (connecté avec le compte dédié). Si la page est introuvable, cherchez « Mots de passe d'application » dans la recherche du compte Google.
2. Donnez-lui un nom (« billetterie »), puis **Créer**.
3. Google affiche **une seule fois** un code de 16 lettres. Copiez-le (sans les espaces) directement dans Render (étape 4). **Ne l'envoyez à personne** : ni dans une conversation avec l'assistant, ni sur GitHub.

## 4. Variables dans Render (Environment)
Le fichier `render.yaml` du dépôt contient déjà les valeurs publiques de Gmail (`MAIL_SCHEME=smtp`, `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`). Il vous reste **4 valeurs à saisir dans Render** (elles ne doivent jamais être écrites dans le dépôt, qui est public) :

| Variable | Valeur |
|---|---|
| `MAIL_MAILER` | `smtp` |
| `MAIL_USERNAME` | l'adresse Gmail du compte dédié |
| `MAIL_PASSWORD` | le mot de passe d'application (16 lettres, sans espaces) |
| `MAIL_FROM_ADDRESS` | la même adresse Gmail |

Après la fusion de la PR qui ajoute ces variables, Render peut vous demander leurs valeurs depuis la page **Blueprint** du service (bouton de synchronisation) ; sinon ajoutez-les à la main dans **Environment**. Enregistrez : Render redéploie.

## 5. Tester
1. Connectez-vous comme admin, menu **Paramètres, Emails**.
2. Tapez votre adresse et cliquez **Envoyer le test**. Le message doit arriver en quelques secondes (regardez aussi les indésirables).
3. Faites ensuite un vrai parcours : une commande, puis « mot de passe oublié » avec une adresse de test.

## 6. Si ça ne marche pas
Onglet **Logs** de Render. Ne copiez jamais le mot de passe dans un message.
- « Failed to authenticate » : mauvais mot de passe d'application, ou validation en 2 étapes non activée.
- « Connection could not be established » : vérifiez `MAIL_HOST` et `MAIL_PORT`.
- Message reçu mais en indésirables : normal pour un test ; avec un domaine et un SMTP professionnel ce sera réglé.

## À savoir pendant le test
- L'application envoie les e-mails **pendant** la requête (`QUEUE_CONNECTION=sync`) : une commande peut prendre quelques secondes de plus.
- Ce sont de **vrais** e-mails envoyés à de vraies adresses : n'utilisez que des adresses de testeurs.
- Le contenu n'est plus écrit en clair dans les journaux dès que `MAIL_MAILER=smtp`.

## Revenir en arrière
Dans Render, remettez `MAIL_MAILER` à `log` (ou supprimez la variable), puis supprimez le mot de passe d'application dans le compte Google (Sécurité, Mots de passe d'application).
