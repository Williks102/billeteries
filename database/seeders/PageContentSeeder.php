<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;

class PageContentSeeder extends Seeder
{
    public function run()
    {
        // Page À propos
        Page::create([
            'title' => 'À propos',
            'slug' => 'about',
            'excerpt' => 'Découvrez ClicBillet CI, votre plateforme de billetterie',
            'content' => '
                <h1>À propos de ClicBillet CI</h1>
                <p>ClicBillet CI est la plateforme de référence pour la vente de billets d\'événements en Côte d\'Ivoire.</p>
                <p>Notre mission est de connecter les organisateurs d\'événements avec leur public en offrant une solution simple, sécurisée et efficace.</p>
                <h2>Nos valeurs</h2>
                <ul>
                    <li>Simplicité d\'utilisation</li>
                    <li>Sécurité des transactions</li>
                    <li>Support client réactif</li>
                    <li>Innovation constante</li>
                </ul>
            ',
            'template' => 'about',
            'is_active' => true,
            'show_in_menu' => true,
            'menu_order' => 1
        ]);

        // Page FAQ
        Page::create([
            'title' => 'FAQ',
            'slug' => 'faq',
            'excerpt' => 'Réponses aux questions les plus fréquentes',
            'content' => '
                <h1>Questions fréquemment posées</h1>
                <div class="faq-item">
                    <h3>Comment acheter un billet ?</h3>
                    <p>Il suffit de sélectionner votre événement, choisir vos billets et procéder au paiement sécurisé.</p>
                </div>
                <div class="faq-item">
                    <h3>Puis-je annuler ma commande ?</h3>
                    <p>Les conditions d\'annulation dépendent de la politique de l\'organisateur.</p>
                </div>
                <div class="faq-item">
                    <h3>Comment devenir promoteur ?</h3>
                    <p>Inscrivez-vous sur notre plateforme et choisissez le rôle "Promoteur" lors de votre inscription.</p>
                </div>
            ',
            'template' => 'faq',
            'is_active' => true,
            'show_in_menu' => true,
            'menu_order' => 2
        ]);

        // Page Contact
        Page::create([
            'title' => 'Contact',
            'slug' => 'contact',
            'excerpt' => 'Contactez notre équipe',
            'content' => '
                <h1>Nous contacter</h1>
                <div class="row">
                    <div class="col-md-6">
                        <h3>Informations de contact</h3>
                        <p><i class="fas fa-envelope text-orange me-2"></i> <strong>Email :</strong> contact@clicbillet.ci</p>
                        <p><i class="fas fa-phone text-orange me-2"></i> <strong>Téléphone :</strong> +225 XX XX XX XX XX</p>
                        <p><i class="fas fa-map-marker-alt text-orange me-2"></i> <strong>Adresse :</strong> Abidjan, Côte d\'Ivoire</p>
                    </div>
                    <div class="col-md-6">
                        <h3>Horaires d\'ouverture</h3>
                        <ul class="list-unstyled">
                            <li><strong>Lundi - Vendredi :</strong> 8h00 - 18h00</li>
                            <li><strong>Samedi :</strong> 9h00 - 16h00</li>
                            <li><strong>Dimanche :</strong> Fermé</li>
                        </ul>
                    </div>
                </div>
            ',
            'template' => 'contact',
            'is_active' => true,
            'show_in_menu' => true,
            'menu_order' => 3
        ]);

        // Page Comment ça marche
        Page::create([
            'title' => 'Comment ça marche',
            'slug' => 'how-it-works',
            'excerpt' => 'Découvrez comment utiliser notre plateforme facilement',
            'content' => '
                <h1>Comment ça marche</h1>
                <div class="row">
                    <div class="col-md-6">
                        <h2>Pour les acheteurs</h2>
                        <ol>
                            <li>Parcourez les événements disponibles</li>
                            <li>Sélectionnez vos billets préférés</li>
                            <li>Payez en ligne de manière sécurisée</li>
                            <li>Recevez vos billets par email avec QR code</li>
                            <li>Présentez votre billet à l\'entrée</li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <h2>Pour les organisateurs</h2>
                        <ol>
                            <li>Créez votre compte promoteur</li>
                            <li>Ajoutez votre événement avec toutes les infos</li>
                            <li>Configurez vos types de billets et prix</li>
                            <li>Suivez vos ventes en temps réel</li>
                            <li>Scannez les billets à l\'entrée</li>
                        </ol>
                    </div>
                </div>
            ',
            'template' => 'default',
            'is_active' => true,
            'show_in_menu' => true,
            'menu_order' => 4
        ]);

        // Page Conditions d'utilisation
        Page::create([
            'title' => 'Conditions d\'utilisation',
            'slug' => 'terms-of-service',
            'excerpt' => 'Conditions générales d\'utilisation de la plateforme',
            'content' => '
                <h1>Conditions d\'utilisation</h1>
                <p><em>Dernière mise à jour : ' . date('d/m/Y') . '</em></p>
                <h2>1. Acceptation des conditions</h2>
                <p>En utilisant ClicBillet CI, vous acceptez pleinement ces conditions d\'utilisation.</p>
                <h2>2. Description du service</h2>
                <p>ClicBillet CI est une plateforme de vente de billets d\'événements qui met en relation les organisateurs et les acheteurs.</p>
                <h2>3. Inscription et compte utilisateur</h2>
                <p>Pour utiliser certaines fonctionnalités, vous devez créer un compte avec des informations exactes et à jour.</p>
                <h2>4. Responsabilités des utilisateurs</h2>
                <p>Les utilisateurs sont responsables de l\'exactitude des informations fournies et du respect des présentes conditions.</p>
                <h2>5. Paiements et remboursements</h2>
                <p>Les conditions de paiement et de remboursement sont définies par chaque organisateur d\'événement.</p>
            ',
            'template' => 'default',
            'is_active' => true,
            'show_in_menu' => false,
            'menu_order' => 0
        ]);

        // Page Politique de confidentialité
        Page::create([
            'title' => 'Politique de confidentialité',
            'slug' => 'privacy-policy',
            'excerpt' => 'Comment nous protégeons vos données personnelles',
            'content' => '
                <h1>Politique de confidentialité</h1>
                <p><em>Dernière mise à jour : ' . date('d/m/Y') . '</em></p>
                <h2>1. Collecte des données</h2>
                <p>Nous collectons uniquement les données nécessaires au fonctionnement du service : nom, email, téléphone pour les commandes.</p>
                <h2>2. Utilisation des données</h2>
                <p>Vos données sont utilisées pour :</p>
                <ul>
                    <li>Traiter vos commandes de billets</li>
                    <li>Vous envoyer vos billets par email</li>
                    <li>Améliorer nos services</li>
                    <li>Vous contacter si nécessaire</li>
                </ul>
                <h2>3. Protection des données</h2>
                <p>Nous utilisons des mesures de sécurité appropriées (cryptage SSL, serveurs sécurisés) pour protéger vos données.</p>
                <h2>4. Vos droits</h2>
                <p>Vous pouvez demander l\'accès, la modification ou la suppression de vos données en nous contactant.</p>
            ',
            'template' => 'default',
            'is_active' => true,
            'show_in_menu' => false,
            'menu_order' => 0
        ]);

        echo "✅ Pages créées avec succès !\n";
        echo "Total pages créées : " . Page::count() . "\n";
    }
}