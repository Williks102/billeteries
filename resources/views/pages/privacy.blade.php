@extends('layouts.app')

@section('title', 'Politique de confidentialité - ClicBillet CI')

@section('content')
<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="display-5 fw-bold text-orange mb-4">Politique de confidentialité</h1>
            <p class="lead">Dernière mise à jour : {{ date('d/m/Y') }}</p>
            
            <hr class="my-5">
            
            <h2>1. Introduction</h2>
            <p>ClicBillet CI s'engage à protéger votre vie privée et vos données personnelles. Cette politique explique comment nous collectons, utilisons et protégeons vos informations lorsque vous utilisez notre plateforme.</p>
            
            <h2>2. Informations collectées</h2>
            <h3>2.1 Données d'inscription</h3>
            <p>Lors de votre inscription, nous collectons :</p>
            <ul>
                <li>Nom et prénom</li>
                <li>Adresse email</li>
                <li>Numéro de téléphone</li>
                <li>Mot de passe (crypté)</li>
                <li>Type de compte (acheteur/promoteur)</li>
            </ul>
            
            <h3>2.2 Données de transaction</h3>
            <p>Lors d'un achat ou d'une vente :</p>
            <ul>
                <li>Informations de facturation</li>
                <li>Historique des transactions</li>
                <li>Méthode de paiement (sans données bancaires complètes)</li>
                <li>Adresse IP</li>
            </ul>
            
            <h3>2.3 Données d'utilisation</h3>
            <p>Automatiquement collectées :</p>
            <ul>
                <li>Pages visitées et temps de navigation</li>
                <li>Type d'appareil et navigateur</li>
                <li>Localisation approximative</li>
                <li>Cookies et technologies similaires</li>
            </ul>
            
            <h2>3. Utilisation des données</h2>
            <p>Nous utilisons vos données pour :</p>
            <ul>
                <li><strong>Fournir nos services :</strong> Traitement des commandes, délivrance de billets</li>
                <li><strong>Communication :</strong> Confirmations, notifications, support client</li>
                <li><strong>Amélioration :</strong> Analyse du comportement utilisateur, optimisation de la plateforme</li>
                <li><strong>Sécurité :</strong> Prévention de la fraude, protection des comptes</li>
                <li><strong>Marketing :</strong> Recommandations d'événements (avec votre consentement)</li>
            </ul>
            
            <h2>4. Partage des données</h2>
            <h3>4.1 Organisateurs d'événements</h3>
            <p>Nous partageons avec les organisateurs :</p>
            <ul>
                <li>Nom et contact des acheteurs de billets</li>
                <li>Informations nécessaires pour l'accès à l'événement</li>
                <li>Statistiques anonymisées de vente</li>
            </ul>
            
            <h3>4.2 Prestataires de services</h3>
            <p>Nous travaillons avec des partenaires pour :</p>
            <ul>
                <li>Traitement des paiements (banques, Mobile Money)</li>
                <li>Envoi d'emails (services de messagerie)</li>
                <li>Hébergement et stockage de données</li>
                <li>Analyse et statistiques</li>
            </ul>
            
            <h3>4.3 Obligations légales</h3>
            <p>Nous pouvons divulguer vos données si requis par :</p>
            <ul>
                <li>Autorités judiciaires</li>
                <li>Forces de l'ordre</li>
                <li>Organismes de régulation</li>
            </ul>
            
            <h2>5. Sécurité des données</h2>
            <p>Nous mettons en place des mesures de sécurité pour protéger vos données :</p>
            <ul>
                <li><strong>Cryptage :</strong> Transmission SSL/TLS, stockage crypté des mots de passe</li>
                <li><strong>Accès restreint :</strong> Seules les personnes autorisées peuvent accéder aux données</li>
                <li><strong>Surveillance :</strong> Monitoring continu pour détecter les intrusions</li>
                <li><strong>Sauvegardes :</strong> Copies de sécurité régulières et sécurisées</li>
            </ul>
            
            <h2>6. Conservation des données</h2>
            <p>Nous conservons vos données :</p>
            <ul>
                <li><strong>Compte actif :</strong> Pendant toute la durée d'utilisation</li>
                <li><strong>Données de transaction :</strong> 7 ans (obligations légales)</li>
                <li><strong>Données marketing :</strong> Jusqu'au retrait du consentement</li>
                <li><strong>Logs de sécurité :</strong> 1 an maximum</li>
            </ul>
            
            <h2>7. Vos droits</h2>
            <p>Conformément à la réglementation, vous disposez des droits suivants :</p>
            
            <h3>7.1 Droit d'accès</h3>
            <p>Vous pouvez demander une copie de toutes les données que nous détenons sur vous.</p>
            
            <h3>7.2 Droit de rectification</h3>
            <p>Vous pouvez corriger ou mettre à jour vos informations personnelles.</p>
            
            <h3>7.3 Droit à l'effacement</h3>
            <p>Vous pouvez demander la suppression de vos données (sous certaines conditions).</p>
            
            <h3>7.4 Droit à la portabilité</h3>
            <p>Vous pouvez récupérer vos données dans un format structuré.</p>
            
            <h3>7.5 Droit d'opposition</h3>
            <p>Vous pouvez vous opposer au traitement de vos données pour le marketing.</p>
            
            <h3>7.6 Exercer vos droits</h3>
            <p>Pour exercer ces droits, contactez-nous à : <strong>privacy@clicbillet.ci</strong></p>
            
            <h2>8. Cookies et technologies similaires</h2>
            <h3>8.1 Types de cookies utilisés</h3>
            <ul>
                <li><strong>Essentiels :</strong> Fonctionnement de la plateforme</li>
                <li><strong>Fonctionnels :</strong> Préférences utilisateur</li>
                <li><strong>Analytiques :</strong> Statistiques d'usage</li>
                <li><strong>Marketing :</strong> Publicité ciblée (avec consentement)</li>
            </ul>
            
            <h3>8.2 Gestion des cookies</h3>
            <p>Vous pouvez gérer vos préférences de cookies :</p>
            <ul>
                <li>Via les paramètres de votre navigateur</li>
                <li>Grâce à notre bandeau de consentement</li>
                <li>En nous contactant directement</li>
            </ul>
            
            <h2>9. Transferts internationaux</h2>
            <p>Vos données peuvent être transférées vers des serveurs situés :</p>
            <ul>
                <li>En Côte d'Ivoire (stockage principal)</li>
                <li>En Europe (services cloud sécurisés)</li>
                <li>Avec des garanties appropriées de protection</li>
            </ul>
            
            <h2>10. Mineurs</h2>
            <p>Notre service n'est pas destiné aux personnes de moins de 16 ans. Si nous découvrons que nous avons collecté des données d'un mineur sans consentement parental, nous les supprimerons immédiatement.</p>
            
            <h2>11. Modifications de cette politique</h2>
            <p>Nous pouvons mettre à jour cette politique de confidentialité. Les changements majeurs seront notifiés par :</p>
            <ul>
                <li>Email aux utilisateurs enregistrés</li>
                <li>Notification sur la plateforme</li>
                <li>Mise à jour de la date en haut de cette page</li>
            </ul>
            
            <h2>12. Contact</h2>
            <p>Pour toute question concernant cette politique de confidentialité :</p>
            <ul>
                <li><strong>Email :</strong> privacy@clicbillet.ci</li>
                <li><strong>Courrier :</strong> ClicBillet CI - DPO, Abidjan, Cocody</li>
                <li><strong>Téléphone :</strong> +225 XX XX XX XX</li>
            </ul>
            
            <hr class="my-5">
            
            <div class="bg-light p-4 rounded">
                <h5><i class="fas fa-shield-alt text-orange me-2"></i>Votre confiance, notre priorité</h5>
                <p class="mb-3">Nous nous engageons à protéger vos données personnelles avec le plus haut niveau de sécurité.</p>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <a href="{{ route('pages.contact') }}" class="btn btn-orange">
                            <i class="fas fa-envelope me-2"></i>Nous contacter
                        </a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <a href="{{ route('pages.terms') }}" class="btn btn-outline-orange">
                            <i class="fas fa-file-contract me-2"></i>Conditions d'utilisation
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection