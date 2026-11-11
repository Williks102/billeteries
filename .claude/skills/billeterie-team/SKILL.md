---
name: billeterie-team
description: Chef d'orchestre de l'équipe d'agents Billetterie (architecte, securite, paiements-stock, qa-tests, ui-design, devops-hygiene). Utiliser pour toute mission multi-domaines : correction des problèmes critiques, refactor, nouvelle fonctionnalité.
argument-hint: "<mission, ex: corriger P1-P7 | refactor checkout | nouvelle feature X>"
---
# Mission : $ARGUMENTS

Tu es le chef d'orchestre. Les sous-agents ne peuvent pas s'appeler entre eux : TOI seul les lances, relaies leurs sorties et arbitres. Charge `billeterie-conventions` d'abord.

## Procédure
0. **Branche** : `git branch --show-current` ; si `main`, créer `claude/<mission>` avant de lancer le moindre agent. Rappelle aux agents dans chaque brief : pas de commit sur `main`.
1. **Cadrer** : reformule la mission, identifie les domaines concernés et les IDs du BOARD (`docs/agents/BOARD.md`). Si la mission est floue ou touche à de l'irréversible (suppression de fichiers, migrations destructives), pose la question à l'utilisateur.
2. **Phase 0 — Hygiène** (si P5-P7 non faits) : lance `devops-hygiene` seul.
3. **Phase 1 — Audit en parallèle** : lance `securite` et `architecte` dans un même message (indépendants, lecture seule). Lis leurs rapports.
4. **Phase 2 — Implémentation** : donne à chaque implémenteur un brief précis (extraits des rapports, fichiers autorisés, critères d'acceptation). Lance en parallèle UNIQUEMENT si les périmètres de fichiers sont disjoints ; sinon séquentiel (`paiements-stock` avant `architecte` sur Checkout*).
5. **Phase 3 — Tests** : `qa-tests` écrit/exécute les tests des correctifs ; `composer run check` doit être vert (pas de CI GitHub). Un test rouge renvoie à l'agent responsable (max 2 boucles, puis remonte à l'utilisateur).
6. **Phase 4 — Revue croisée** : `securite` re-valide les changements sensibles (« validé / non validé »). `ui-design` n'intervient que si des vues sont concernées.
7. **Clôture** : vérifie que le BOARD est à jour, lance `git status`/`git diff --stat`, résume à l'utilisateur (fait / non vérifié / reste à faire). Commit sur la branche auxiliaire de la mission (jamais `main`) avec un message par lot logique ; push seulement si demandé. Pas de PR sans demande.

## Règles de briefing d'un agent
- Brief autonome : contexte, objectif, périmètre de fichiers, critères d'acceptation, ce qu'il ne doit pas toucher, format de réponse (≤ 15 lignes).
- Ne jamais déléguer la compréhension : cite les constats (`fichier:ligne`) plutôt que « corrige la sécurité ».
- Un agent qui sort de son périmètre → rejette et reformule le brief.

## Cas d'usage rapides
- « corriger les bloquants » : phases 0 → 1 (sécurité seule) → paiements-stock (P1-P4) → qa-tests → securite (re-validation).
- « refactor » : architecte (plan) → validation utilisateur du plan → implémentation par étapes → qa-tests à chaque étape.
- « nouvelle feature » : architecte (conception) → implémenteur → qa-tests → securite → ui-design.
