# devops-hygiene P5-P7
- P7 : .gitignore corrigé (lignes `cat`/`EOF` retirées, ajout *.backup, *.old, /database/*.sqlite). `git rm --cached` de database.sqlite et des *.backup/*.old. Supprimés (non référencés, Grep) : backup_migration.php, resources/views/admin.old, partials.old. Fichiers .backup/.old restent sur disque (ignorés).
- P5 : TelescopeServiceProvider inexistant retiré de bootstrap/providers.php (package auto-découvert) ; TELESCOPE_ENABLED=false par défaut.
- P6 : UserSeeder : mots de passe via SEED_ADMIN/PROMOTEUR/ACHETEUR_PASSWORD sinon aléatoires ; no-op en production ; DatabaseSeeder n'affiche plus d'identifiants.
- .env.example créé. Note : echo de PageContentSeeder/TicketSeeder non touchés (pas de secrets).
- Constat hors périmètre : `php artisan route:list` échoue sans PAIEMENTPRO_MERCHANT_ID (PaiementProService lève une exception à l'instanciation) ; OK avec la variable.
- Pint --test : échecs préexistants (public/index.php, LayoutComposer.php).
