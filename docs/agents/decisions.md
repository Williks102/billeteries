# Décisions du propriétaire (source de vérité pour les agents)

Ces décisions priment sur les hypothèses des audits. Date : 2026-11-11.

## Paiement et frais
- **PaiementPro est supprimé** (responsecode non signé). Paiement MANUEL confirmé par l'admin (virement, Mobile Money, espèces). Déjà fusionné.
- **Les frais de service sont ANNULÉS** : la plateforme ne se rémunère que par la **commission**. Les 500 FCFA (`CheckoutService::SERVICE_FEE`) disparaissent. Mission dédiée « frais de service », à lancer APRÈS la fusion de la mission N-A/N-B/N-C et AVANT les statistiques. Doit aussi corriger les textes (pricing, FAQ, CGU, emails) qui parlent de frais « par billet » / « par commande ».
- (Historique : les frais étaient de 500 FCFA par commande, pas par billet.)

## Statistiques (recommandations validées le 2026-11-11)
1. **Revenu plateforme** = la commission seule (frais de service supprimés). **Revenu promoteur** = le **net** après commission, sur les commandes `paid`.
2. **Billet vendu** = billet payé ou utilisé (`sold` + `used`) d'une commande `paid`. Un billet remboursé, annulé ou seulement réservé n'est PAS vendu.
3. **Valeurs simulées** (conversion, abandon de panier, prévisions, « +15 % ce mois » en dur) : **supprimées**, affichage « — » quand un indicateur n'est pas calculable. Pas de badge « estimation ».
4. **Période par défaut** : mois civil en cours, fuseau **Africa/Abidjan**.
- Source unique : `App\Services\Stats\*` + `App\Support\Stats\DatePeriod` + composants `<x-stat-card>`/`<x-stat-grid>` (voir architecte-unifier-statistiques.md). Ordre : tests de caractérisation d'abord (L0).

## Sécurité / mise en production
- La PR « retrait PaiementPro » est fusionnée avec trois réserves acceptées pour un MVP : plafond global de billets en attente (N-B), comptage sous MySQL (N-A), clauses de CGU contradictoires (I-C). N-A, N-B, N-C (+ N-D) sont traités dans la mission `claude/durcissement-stock-n-a-b-c`.
- AVANT de prendre de vrais paiements : test MySQL à 2 connexions exécuté (`composer run test:mysql`), `TRUSTED_PROXIES` défini et testé, `APP_DEBUG=false`, `APP_ENV=production`, `SESSION_SECURE_COOKIE=true`, placeholder CGU `[À COMPLÉTER PAR L'ORGANISATEUR…]` rempli et politique de remboursement/annulation tranchée.
- La création de compte après achat invité est désactivée tant qu'il n'existe pas de vérification d'email.

## Organisation du travail
- Pas trop de chantiers ouverts en même temps : une mission à la fois, une branche `claude/<mission>` par mission, une PR par mission.
- Ordre convenu : durcissement stock (N-A/B/C) → frais de service → statistiques → création d'événement simplifiée → mobile → « avant production ».
- Questions encore ouvertes (à poser quand la mission démarre) : création d'événement Q1-Q7 (architecte-simplifier-creation-evenement.md), mobile Q1-Q8 (ui-design-audit-mobile.md).

## Réponses aux 6 rapports (validées par le propriétaire le 2026-11-11 : « ok pour les 6 rapports », recommandations retenues)
### CGU et pages publiques
- Délais de remboursement (2 h) et de 7 jours : courent à partir de la **confirmation du paiement** (durée à fixer par le propriétaire : placeholder à remplir).
- Article 6 « frais de paiement » : supprimé (frais de service annulés).
- Confidentialité : paiement traité manuellement (banque / opérateur Mobile Money), plus de « prestataires de paiement certifiés ».
- Annulation : libre avant paiement (réservation libérée) ; après paiement selon la politique du propriétaire.
- Délai de confirmation du paiement du placeholder : à fournir (ex. 24 h ouvrées).
### Sécurité
- `TRUSTED_PROXIES` : à définir selon l'hébergeur (question à poser à l'hébergeur).
- Vérification d'email : mission AVANT l'ouverture au public. Captcha : pas maintenant.
- Remboursement refusé si un billet de la commande a été scanné/utilisé (N6).
- Production : APP_DEBUG=false, APP_ENV=production, SESSION_SECURE_COOKIE=true.
- Lien de confirmation invité : à ajouter à l'email d'instructions (l'invité n'a plus de compte pour retrouver sa commande).
- Test MySQL (`composer run test:mysql`) : à lancer par le propriétaire/serveur sur une base jetable dont le nom finit par `_test`.
### Statistiques (compléments)
- Commissions `held` / `pending` : NON comptées (seules les commissions de commandes `paid`).
- Remplissage public : billets vendus seulement ; les réservations non payées restent « disponibles ».
### Création d'événement
- Variante A (formulaire unique). Brouillon sans billet autorisé, mais publier exige ≥ 1 billet. Image et description obligatoires pour publier. Suppression de la pré-génération de billets (après validation de paiements-stock). Fin de vente par défaut = début de l'événement. L'admin crée pour un promoteur ; mode hybride retiré pour l'instant. Duplication d'événement : branchée.
### Mobile
- Bootstrap/FontAwesome hébergés en local ; vraie page panier mobile ; un seul gabarit de vue pour les checkouts (contrôleurs séparés conservés) ; retrait de `user-scalable=no` ; un seul gabarit de tableau de bord en dernier lot ; jsQR local sans mode hors ligne ; page publique de vérification de billet réduite à validité + nom d'événement ; suppression du code mort (diagnostic, events/test, ticket/, acheteurii) après vérification des références.
