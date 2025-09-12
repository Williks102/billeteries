@extends('layouts.app')

@section('title', 'Politique de confidentialité - ClicBillet CI')

@section('content')
<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="display-5 fw-bold text-orange mb-4">Politique de confidentialité</h1>
            <p class="lead">Dernière mise à jour : {{ date('d/m/Y') }}</p>
            
            <hr class="my-5">
            
            <h2>Le but de cette politique de confidentialité</h2>
            <p>Cette politique de confidentialité s'applique à toutes les données personnelles traitées par <strong>ClicBillet CI</strong> en tant que responsable de traitement conformément au droit ivoirien, notamment à la loi n°2013-450 du 19 juin 2013 relative à la protection des données personnelles.</p>
            
            <p>Les termes «ClicBillet», «nous» ou «nos/notre» utilisés dans cette politique de confidentialité font référence à <strong>ClicBillet CI</strong>. Le terme «plateforme» fait référence au site internet <strong>www.clicbillet.com</strong> et aux applications mobiles version Android et iOS associées. Lorsque nous utilisons les termes «vous» ou «vos/votre», cela inclut les clients potentiels et clients de ClicBillet, visiteurs ou utilisateurs de la plateforme, lors de la consultation ou de l'achat de services ou produits proposés par ClicBillet.</p>

            <p>La présente politique de confidentialité a pour objet d'informer les utilisateurs de notre plateforme des données personnelles que nous collectons et des informations suivantes, le cas échéant :</p>
            <ol>
                <li>Les données personnelles que nous collectons</li>
                <li>L'utilisation des données recueillies</li>
                <li>Qui a accès aux données recueillies</li>
                <li>Les droits des utilisateurs de la plateforme</li>
                <li>La politique de cookies de la plateforme</li>
            </ol>

            <p>Cette politique de confidentialité fonctionne conjointement avec nos conditions générales de vente et conditions générales d'utilisation.</p>

            <h2>Application</h2>
            <p>Nous comprenons que la confidentialité en ligne est importante pour les utilisateurs de notre plateforme. Conformément à la loi n° 2013-450 du 19 juin 2013 relative à la protection des données personnelles et à nos propres engagements, cette politique de confidentialité est conforme aux règlements suivants :</p>

            <p>Les données à caractère personnel sont :</p>
            <ol>
                <li>traitées de manière licite, loyale et transparente à l'égard de la personne concernée (licéité, loyauté, transparence) ;</li>
                <li>collectées pour des finalités déterminées, explicites et légitimes, et non traitées ultérieurement de manière incompatible avec ces finalités (limitation des finalités) ;</li>
                <li>adéquates, pertinentes et limitées à ce qui est nécessaire au regard des finalités pour lesquelles elles sont traitées (minimisation des données) ;</li>
                <li>conservées sous une forme permettant l'identification des personnes concernées pendant une durée n'excédant pas celle nécessaire au regard des finalités pour lesquelles elles sont traitées ; les données personnelles peuvent être conservées pour des durées plus longues dans la mesure où elles seront traitées exclusivement à des fins d'archivage dans l'intérêt public, à des fins de recherche scientifique ou historique ou à des fins statistiques (limitation de conservation) ;</li>
                <li>traitées de manière à assurer une sécurité appropriée des données personnelles, y compris la protection contre le traitement non autorisé ou illégal et contre la perte, la destruction ou les dommages accidentels, en utilisant des mesures techniques ou organisationnelles appropriées (intégrité et confidentialité).</li>
            </ol>

            <p>Le traitement n'est licite que si et dans la mesure où au moins une des conditions suivantes est remplie :</p>
            <ol>
                <li>la personne concernée a consenti au traitement de ses données personnelles pour une ou plusieurs finalités spécifiques ;</li>
                <li>le traitement est nécessaire à l'exécution d'un contrat auquel la personne concernée est partie ou à l'exécution de mesures précontractuelles prises à la demande de celle-ci ;</li>
                <li>le traitement est nécessaire au respect d'une obligation légale à laquelle le responsable du traitement est soumis ;</li>
                <li>le traitement est nécessaire pour protéger les intérêts vitaux de la personne concernée ou d'une autre personne physique ;</li>
                <li>le traitement est nécessaire à l'exécution d'une mission d'intérêt public ou relevant de l'exercice de l'autorité publique dont est investi le responsable du traitement ;</li>
                <li>le traitement est nécessaire aux fins des intérêts légitimes poursuivis par le responsable du traitement ou par un tiers, à moins que ne prévalent les intérêts ou les libertés et droits fondamentaux de la personne concernée qui exigent la protection des données à caractère personnel, notamment lorsque la personne concernée est un enfant.</li>
            </ol>

            <h2>Consentement</h2>
            <p>Les utilisateurs conviennent qu'en utilisant notre plateforme, ils consentent à :</p>
            <ol>
                <li>les conditions énoncées dans la présente politique de confidentialité et</li>
                <li>la collecte, l'utilisation et la conservation des données énumérées dans cette politique.</li>
            </ol>

            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Important :</strong> En utilisant notre plateforme, vous consentez aux conditions de cette politique et à la collecte de données énumérées.
            </div>
            
            <h2>1. Données personnelles que nous collectons</h2>
            
            <h3>1.1 Données collectées automatiquement</h3>
            <p>Lorsque vous visitez et utilisez notre plateforme, nous pouvons collecter et stocker automatiquement les informations suivantes :</p>
            <ul>
                <li><i class="fas fa-globe me-2 text-orange"></i>Adresse IP</li>
                <li><i class="fas fa-map-marker-alt me-2 text-orange"></i>Lieu (localisation approximative)</li>
                <li><i class="fas fa-laptop me-2 text-orange"></i>Détails matériels et logiciels (navigateur, système d'exploitation)</li>
                <li><i class="fas fa-mouse-pointer me-2 text-orange"></i>Liens sur lesquels vous cliquez lorsque vous utilisez la plateforme</li>
                <li><i class="fas fa-eye me-2 text-orange"></i>Contenus que vous consultez sur notre plateforme</li>
            </ul>
            
            <h3>1.2 Données collectées de façon non automatique</h3>
            <p>Nous pouvons également collecter les données suivantes lorsque vous exécutez certaines fonctions sur notre plateforme :</p>
            <ul>
                <li><i class="fas fa-user me-2 text-orange"></i>Prénom et nom</li>
                <li><i class="fas fa-birthday-cake me-2 text-orange"></i>Date de naissance</li>
                <li><i class="fas fa-envelope me-2 text-orange"></i>Email</li>
                <li><i class="fas fa-phone me-2 text-orange"></i>Numéro de téléphone</li>
                <li><i class="fas fa-home me-2 text-orange"></i>Adresse de domicile</li>
                <li><i class="fas fa-id-card me-2 text-orange"></i>Documents d'identité personnelle et impersonnelle</li>
                <li><i class="fas fa-credit-card me-2 text-orange"></i>Informations de paiement</li>
                <li><i class="fas fa-keyboard me-2 text-orange"></i>Données de remplissage automatique</li>
            </ul>

            <p><strong>Ces données peuvent être collectées selon les méthodes suivantes :</strong></p>
            <ul>
                <li>Enregistrement du compte</li>
                <li>Formulaires</li>
                <li>Achat de billets</li>
                <li>Création d'événements</li>
            </ul>

            <p>Veuillez noter que nous ne collectons que des données qui nous aident à atteindre les objectifs définis dans cette politique de confidentialité. Nous ne collecterons pas de données supplémentaires sans vous en informer au préalable.</p>

            <h2>2. Comment utilisons-nous les données personnelles ?</h2>
            <p>Les données personnelles collectées sur notre plateforme ne seront utilisées que pour les finalités précisées dans la présente politique ou indiquées sur les pages concernées de notre plateforme. Nous n'utilisons pas vos données au-delà de ce que nous divulguons.</p>

            <h3>2.1 Les données que nous collectons automatiquement sont utilisées aux fins suivantes :</h3>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card bg-light h-100">
                        <div class="card-body">
                            <h5 class="text-orange"><i class="fas fa-cog me-2"></i>Données automatiques</h5>
                            <ul class="mb-0">
                                <li>Amélioration du contenu</li>
                                <li>Fonctionnement interne</li>
                                <li>Statistiques</li>
                                <li>Sécurité</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card bg-light h-100">
                        <div class="card-body">
                            <h5 class="text-orange"><i class="fas fa-user-check me-2"></i>Données utilisateur</h5>
                            <ul class="mb-0">
                                <li>Informations de contact</li>
                                <li>Sécurité et règlement des litiges</li>
                                <li>Publicité ciblée</li>
                                <li>Support client</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <h2>3. Avec qui partageons-nous les données personnelles ?</h2>
            
            <h3>3.1 Employés</h3>
            <p>Nous pouvons divulguer à tout membre de notre organisation les données utilisateur dont il a raisonnablement besoin pour remplir les objectifs énoncés dans la présente politique.</p>

            <h3>3.2 Tierces parties</h3>
            <p>Nous pouvons partager des données utilisateur avec les tiers suivants :</p>
            
            <div class="row">
                <div class="col-md-4 text-center mb-3">
                    <div class="card border-0">
                        <div class="card-body">
                            <i class="fas fa-users fa-3x text-orange mb-2"></i>
                            <h6>Organisateurs d'événements</h6>
                            <p class="small">Informations des acheteurs pour l'accès aux événements</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-center mb-3">
                    <div class="card border-0">
                        <div class="card-body">
                            <i class="fas fa-credit-card fa-3x text-orange mb-2"></i>
                            <h6>Prestataires de paiement</h6>
                            <p class="small">Services certifiés pour les transactions</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-center mb-3">
                    <div class="card border-0">
                        <div class="card-body">
                            <i class="fas fa-envelope fa-3x text-orange mb-2"></i>
                            <h6>Services de messagerie</h6>
                            <p class="small">Envoi de billets et notifications</p>
                        </div>
                    </div>
                </div>
            </div>

            <p>Les tiers ne pourront pas accéder aux données de l'utilisateur au-delà de ce qui est raisonnablement nécessaire pour atteindre l'objectif donné.</p>

            <h3>3.3 Autres divulgations</h3>
            <p>Nous nous engageons à ne pas vendre ou partager vos données avec d'autres tiers, sauf dans les cas suivants :</p>
            <ul>
                <li>si la loi l'exige</li>
                <li>si elle est requise pour toute procédure judiciaire</li>
                <li>pour prouver ou protéger nos droits légaux</li>
                <li>aux acquéreurs ou acquéreurs potentiels de ClicBillet CI dans le cas où nous chercherions à la revendre en tout ou en partie</li>
            </ul>

            <p>Si vous suivez des hyperliens de notre plateforme vers un autre site, veuillez noter que nous ne sommes pas responsables et n'avons aucun contrôle sur leurs politiques et pratiques de confidentialité.</p>

            <h2>4. Combien de temps conservons-nous les données personnelles ?</h2>
            <p>Nous ne conservons pas les données des utilisateurs au-delà de ce qui est nécessaire pour remplir les finalités pour lesquelles elles sont collectées.</p>
            
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-shield-alt text-orange me-2"></i>Mesures de sécurité</h5>
                    <ul>
                        <li>Protocole SSL/TLS</li>
                        <li>Cryptage des mots de passe</li>
                        <li>Accès restreint aux employés</li>
                        <li>Surveillance continue</li>
                        <li>Sauvegardes sécurisées</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5><i class="fas fa-clock text-orange me-2"></i>Durées de conservation</h5>
                    <ul>
                        <li>Compte actif : durée d'utilisation</li>
                        <li>Données de transaction : 7 ans</li>
                        <li>Données marketing : jusqu'au retrait</li>
                        <li>Logs de sécurité : 1 an maximum</li>
                    </ul>
                </div>
            </div>

            <h2>5. Comment protégeons-nous les données personnelles ?</h2>
            <p>Pour nous assurer que votre sécurité est protégée, nous utilisons le protocole de sécurité de la couche de transport pour transmettre des informations personnelles via notre système.</p>

            <p>Toutes les données stockées dans notre système sont bien sécurisées et ne sont accessibles qu'à nos employés. Nos employés sont liés par des accords de confidentialité stricts et une violation de cet accord entraînera le licenciement de l'employé.</p>

            <p>Bien que nous prenions toutes les précautions raisonnables pour nous assurer que nos données d'utilisateur soient sécurisées et que les utilisateurs soient protégés, il reste toujours un risque de préjudice. L'Internet dans son ensemble peut parfois être peu sûr et nous ne sommes donc pas en mesure de garantir la sécurité des données des utilisateurs au-delà de ce qui est raisonnablement pratique.</p>

            <h2>6. Mineurs</h2>
            <p>Les mineurs doivent avoir le consentement d'un représentant légal pour que leurs données soient collectées, traitées et utilisées.</p>

            <p>Nous n'avons pas l'intention de collecter ou d'utiliser les données d'utilisateurs mineurs. Si nous découvrons que nous avons collecté des données auprès d'un mineur, ces données seront immédiatement supprimées.</p>

            <h2>7. Vos droits en tant qu'utilisateur</h2>
            <p>Dans le cadre de nos engagements en matière de gestion et de protection des données personnelles, les utilisateurs disposent des droits suivants en tant que personnes concernées :</p>

            <div class="alert alert-info">
                <h5><i class="fas fa-user-shield me-2"></i>Droits garantis</h5>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="mb-0">
                            <li>✅ Droit d'accès</li>
                            <li>✅ Droit de rectification</li>
                            <li>✅ Droit à l'effacement</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="mb-0">
                            <li>✅ Droit de restreindre le traitement</li>
                            <li>✅ Droit à la portabilité des données</li>
                            <li>✅ Droit d'objection</li>
                        </ul>
                    </div>
                </div>
            </div>

            <h2>8. Comment modifier, supprimer ou contester les données collectées ?</h2>
            <p>Si vous souhaitez que vos informations soient supprimées ou autrement modifiées, veuillez contacter par mail notre responsable de la confidentialité des données :</p>
            
            <div class="alert alert-success">
                <h5><i class="fas fa-envelope me-2"></i>Contact Data Protection Officer (DPO)</h5>
                <ul class="mb-0">
                    <li><strong>Email spécialisé :</strong> privacy@clicbillet.com</li>
                    <li><strong>Email général :</strong> contact@clicbillet.com</li>
                    <li><strong>Téléphone :</strong> +225 07 02 49 02 77</li>
                </ul>
            </div>

            <h2>9. Politique sur les cookies</h2>
            <p>Un cookie est un petit fichier, stocké sur le disque dur d'un utilisateur par le site Web. Il a pour finalité de collecter des données relatives aux habitudes de navigation de l'utilisateur.</p>

            <p>Nous utilisons les types de cookies suivants sur notre plateforme :</p>

            <div class="row">
                <div class="col-md-4 text-center mb-3">
                    <div class="card">
                        <div class="card-body">
                            <i class="fas fa-cogs text-orange fa-2x mb-2"></i>
                            <h6>1. Cookies fonctionnels</h6>
                            <p class="small">Nous les utilisons pour mémoriser toutes les sélections que vous effectuez sur notre plateforme afin qu'elles soient enregistrées pour vos futures visites.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-center mb-3">
                    <div class="card">
                        <div class="card-body">
                            <i class="fas fa-chart-line text-orange fa-2x mb-2"></i>
                            <h6>2. Cookies analytiques</h6>
                            <p class="small">Cela nous permet d'améliorer la conception et la fonctionnalité de notre plateforme en collectant des données sur le contenu auquel vous accédez.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-center mb-3">
                    <div class="card">
                        <div class="card-body">
                            <i class="fas fa-bullseye text-orange fa-2x mb-2"></i>
                            <h6>3. Cookies de ciblage</h6>
                            <p class="small">Cela nous permet d'améliorer la conception et la fonctionnalité de notre plateforme en collectant des données sur le contenu auquel vous accédez.</p>
                        </div>
                    </div>
                </div>
            </div>

            <p>Vous pouvez choisir d'être averti à chaque fois qu'un cookie est transmis. Vous pouvez également choisir de désactiver entièrement les cookies dans votre navigateur Internet, mais cela peut diminuer la qualité de votre expérience utilisateur.</p>

            <h3>Cookies tiers</h3>
            <p>Nous pouvons utiliser des cookies tiers sur notre plateforme pour atteindre les objectifs suivants :</p>
            <ul>
                <li>Surveillez les préférences des utilisateurs pour adapter les publicités à leurs intérêts.</li>
            </ul>

            <h2>10. Transferts internationaux</h2>
            <p>Vos données peuvent être transférées vers des serveurs situés :</p>
            <ul>
                <li>En Côte d'Ivoire (stockage principal)</li>
                <li>En Europe (services cloud sécurisés)</li>
                <li>Avec des garanties appropriées de protection</li>
            </ul>

            <h2>11. Modifications</h2>
            <p>Cette politique de confidentialité peut être modifiée à l'occasion afin de maintenir la conformité avec la loi et de tenir compte de tout changement à notre processus de collecte de données. Nous recommandons à nos utilisateurs de vérifier notre politique de temps à autre pour s'assurer qu'ils soient informés de toute mise à jour. Au besoin, nous pouvons informer les utilisateurs par e-mail des changements apportés à cette politique.</p>

            <h2>12. Contact</h2>
            <p>Si vous avez des questions concernant cette politique de confidentialité, n'hésitez pas à nous contacter en utilisant les informations suivantes :</p>

            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <h5><i class="fas fa-building text-orange me-2"></i>ClicBillet CI</h5>
                            <p class="mb-1"><i class="fas fa-map-marker-alt me-2"></i><strong>Adresse :</strong> Abidjan, Cocody, Côte d'Ivoire</p>
                            <p class="mb-1"><i class="fas fa-envelope me-2"></i><strong>Email :</strong> contact@clicbillet.com</p>
                            <p class="mb-1"><i class="fas fa-shield-alt me-2"></i><strong>Privacy :</strong> privacy@clicbillet.com</p>
                            <p class="mb-0"><i class="fas fa-phone me-2"></i><strong>Téléphone :</strong> +225 07 02 49 02 77</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <h5><i class="fas fa-clock text-orange me-2"></i>Horaires de support</h5>
                            <p class="mb-1"><strong>Lundi - Vendredi :</strong> 8h - 18h</p>
                            <p class="mb-1"><strong>Samedi :</strong> 9h - 15h</p>
                            <p class="mb-0"><strong>Dimanche :</strong> Fermé</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <hr class="my-5">
            
            <div class="bg-light p-4 rounded">
                <h5><i class="fas fa-shield-alt text-orange me-2"></i>Votre confiance, notre priorité</h5>
                <p class="mb-3">Nous nous engageons à protéger vos données personnelles avec le plus haut niveau de sécurité et transparence.</p>
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

            <div class="text-center mt-4">
                <small class="text-muted">
                    <strong>Date d'entrée en vigueur :</strong> le 1er janvier 2024
                </small>
            </div>
        </div>
    </div>
</div>
@endsection