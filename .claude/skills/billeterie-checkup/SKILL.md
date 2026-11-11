---
name: billeterie-checkup
description: Lance un check-up complet et coordonné (architecture, sécurité, tests, hygiène, UI) et produit un rapport de synthèse priorisé dans docs/agents/reports/checkup.md.
disable-model-invocation: true
---
# Check-up complet

1. Charge `billeterie-conventions` (l'état attendu est la liste P1-P7 / D1-D8 ; vérifie lesquels sont encore vrais).
2. Dans UN SEUL message, lance en parallèle (lecture seule, brief : « audit uniquement, rapport dans docs/agents/reports/ ») :
   - `securite` : checklist complète.
   - `architecte` : tailles de classes, routes, duplication, dette.
   - `qa-tests` : état des tests/CI, couverture des flux critiques (sans écrire de code applicatif).
   - `devops-hygiene` : dépôt, config prod, seeders, env.
   - `ui-design` : parcours d'achat mobile, accessibilité, vues obsolètes.
3. Consolide dans `docs/agents/reports/checkup.md` : tableau `Constat | Gravité | Agent propriétaire | Effort (S/M/L) | Statut (corrigé / toujours vrai / nouveau)`.
4. Mets à jour `docs/agents/BOARD.md` avec les nouvelles tâches (sans écraser les lignes existantes).
5. Réponds à l'utilisateur : top 5 des risques, ce qui a été corrigé depuis le dernier check-up, et propose de lancer `/billeterie-team <mission>`.

Aucune modification du code applicatif pendant un check-up.
