@extends('layouts.app')

@section('title', 'Conditions d\'utilisation - ClicBillet CI')

@section('content')
<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="display-5 fw-bold text-orange mb-4">Conditions d'utilisation</h1>
            <p class="lead">Dernière mise à jour : {{ date('d/m/Y') }}</p>
            
            <hr class="my-5">
            
            <h2>1. Acceptation des conditions</h2>
            <p>En utilisant la plateforme ClicBillet CI, vous acceptez sans réserve les présentes conditions d'utilisation. Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser notre service.</p>
            
            <h2>2. Description du service</h2>
            <p>ClicBillet CI est une plateforme de billetterie en ligne qui permet :</p>
            <ul>
                <li>Aux organisateurs de créer et promouvoir leurs événements</li>
                <li>Au public d'acheter des billets pour divers événements</li>
                <li>La gestion sécurisée des transactions financières</li>
                <li>La délivrance de billets électroniques</li>
            </ul>
            
            <h2>3. Inscription et compte utilisateur</h2>
            <h3>3.1 Création de compte</h3>
            <p>Pour utiliser certaines fonctionnalités, vous devez créer un compte en fournissant des informations exactes et complètes.</p>
            
            <h3>3.2 Responsabilité du compte</h3>
            <p>Vous êtes responsable de la confidentialité de votre mot de passe et de toutes les activités effectuées sous votre compte.</p>
            
            <h3>3.3 Types de comptes</h3>
            <ul>
                <li><strong>Acheteur :</strong> Pour acheter des billets d'événements</li>
                <li><strong>Promoteur :</strong> Pour organiser et vendre des billets d'événements</li>
                <li><strong>Administrateur :</strong> Gestion de la plateforme</li>
            </ul>
            
            <h2>4. Utilisation de la plateforme</h2>
            <h3>4.1 Règles générales</h3>
            <p>Vous vous engagez à :</p>
            <ul>
                <li>Utiliser la plateforme de manière légale et éthique</li>
                <li>Ne pas perturber le fonctionnement du service</li>
                <li>Respecter les droits des autres utilisateurs</li>
                <li>Fournir des informations exactes</li>
            </ul>
            
            <h3>4.2 Interdictions</h3>
            <p>Il est strictement interdit de :</p>
            <ul>
                <li>Utiliser des moyens automatisés pour accéder au service</li>
                <li>Tenter de contourner les mesures de sécurité</li>
                <li>Publier du contenu illégal, offensant ou trompeur</li>
                <li>Revendre des billets à des prix supérieurs (sauf autorisation)</li>
            </ul>
            
            <h2>5. Achats et paiements</h2>
            <h3>5.1 Prix et disponibilité</h3>
            <p>Les prix affichés sont en Francs CFA (FCFA) et incluent toutes les taxes applicables. La disponibilité des billets est limitée et soumise à la politique "premier arrivé, premier servi".</p>
            
            <h3>5.2 Moyens de paiement</h3>
            <p>Nous acceptons :</p>
            <ul>
                <li>Cartes bancaires (Visa, Mastercard)</li>
                <li>Mobile Money (Orange Money, MTN Money, Moov Money)</li>
                <li>Virements bancaires (sur validation)</li>
            </ul>
            
            <h3>5.3 Confirmation de commande</h3>
            <p>Une fois le paiement validé, vous recevrez un email de confirmation avec vos billets électroniques.</p>
            
            <h2>6. Billets électroniques</h2>
            <h3>6.1 Validité</h3>
            <p>Les billets électroniques sont valables uniquement pour l'événement, la date et le lieu spécifiés.</p>
            
            <h3>6.2 Présentation à l'entrée</h3>
            <p>Vous devez présenter vos billets (PDF imprimé ou écran mobile) avec une pièce d'identité valide à l'entrée.</p>
            
            <h3>6.3 Fraude</h3>
            <p>Toute tentative de fraude (billets falsifiés, duplicatas, etc.) entraînera un refus d'accès et d'éventuelles poursuites.</p>
            
            <h2>7. Annulations et remboursements</h2>
            <h3>7.1 Annulation par l'organisateur</h3>
            <p>En cas d'annulation d'un événement par l'organisateur, vous serez remboursé intégralement sous 7 jours ouvrables.</p>
            
            <h3>7.2 Annulation par l'acheteur</h3>
            <p>Les conditions d'annulation varient selon l'organisateur. Consultez les conditions spécifiques de chaque événement.</p>
            
            <h3>7.3 Reports d'événements</h3>
            <p>En cas de report, vos billets restent valables pour la nouvelle date. Si vous ne pouvez pas assister à la nouvelle date, contactez l'organisateur.</p>
            
            <h2>8. Responsabilités et limitations</h2>
            <h3>8.1 Limitation de responsabilité</h3>
            <p>ClicBillet CI ne peut être tenu responsable :</p>
            <ul>
                <li>Du contenu des événements ou du comportement des organisateurs</li>
                <li>Des dommages indirects ou consécutifs</li>
                <li>Des pertes de données ou interruptions de service</li>
            </ul>
            
            <h3>8.2 Responsabilité des organisateurs</h3>
            <p>Les organisateurs sont entièrement responsables de leurs événements, y compris la sécurité, le contenu et le respect des réglementations.</p>
            
            <h2>9. Propriété intellectuelle</h2>
            <p>Tous les éléments de la plateforme (logo, design, textes, etc.) sont protégés par les droits de propriété intellectuelle. Toute reproduction non autorisée est interdite.</p>
            
            <h2>10. Protection des données personnelles</h2>
            <p>Nous nous engageons à protéger vos données personnelles conformément à notre <a href="{{ route('pages.privacy') }}">Politique de confidentialité</a> et aux réglementations en vigueur.</p>
            
            <h2>11. Modification des conditions</h2>
            <p>Nous nous réservons le droit de modifier ces conditions à tout moment. Les utilisateurs seront informés des changements majeurs par email ou notification sur la plateforme.</p>
            
            <h2>12. Résiliation</h2>
            <p>Nous pouvons suspendre ou résilier votre compte en cas de violation de ces conditions, sans préavis et sans remboursement.</p>
            
            <h2>13. Droit applicable et juridiction</h2>
            <p>Ces conditions sont régies par le droit ivoirien. Tout litige sera soumis aux tribunaux compétents d'Abidjan, Côte d'Ivoire.</p>
            
            <h2>14. Contact</h2>
            <p>Pour toute question concernant ces conditions d'utilisation :</p>
            <ul>
                <li><strong>Email :</strong> legal@clicbillet.ci</li>
                <li><strong>Téléphone :</strong> +225 XX XX XX XX</li>
                <li><strong>Adresse :</strong> Abidjan, Cocody, Côte d'Ivoire</li>
            </ul>
            
            <hr class="my-5">
            
            <div class="bg-light p-4 rounded">
                <h5>Vous avez des questions ?</h5>
                <p class="mb-3">Notre équipe est là pour vous aider à comprendre ces conditions.</p>
                <a href="{{ route('pages.contact') }}" class="btn btn-orange">
                    <i class="fas fa-envelope me-2"></i>Nous contacter
                </a>
            </div>
        </div>
    </div>
</div>
@endsection