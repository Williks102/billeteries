{{-- resources/views/pages/terms.blade.php --}}
@extends('layouts.app')

@section('title', 'Conditions Générales de Vente - ClicBillet CI')

@push('styles')
<style>
    .terms-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        padding: 3rem;
        margin: 2rem 0;
    }

    .terms-header {
        text-align: center;
        margin-bottom: 3rem;
        padding-bottom: 2rem;
        border-bottom: 3px solid #FF6B35;
    }

    .terms-header h1 {
        color: #FF6B35;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .update-date {
        background: linear-gradient(135deg, rgba(255, 107, 53, 0.1), rgba(255, 107, 53, 0.05));
        padding: 1rem;
        border-radius: 10px;
        border-left: 4px solid #FF6B35;
        margin-bottom: 2rem;
    }

    .section-title {
        color: #1a1a1a;
        font-weight: 600;
        margin-top: 2.5rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #f8f9fa;
    }

    .subsection-title {
        color: #FF6B35;
        font-weight: 600;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
    }

    .definition-box {
        background: #f8f9fa;
        border-left: 4px solid #FF6B35;
        padding: 1.5rem;
        margin: 1.5rem 0;
        border-radius: 0 10px 10px 0;
    }

    .contact-info {
        background: linear-gradient(135deg, #FF6B35, #E55A2B);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin: 2rem 0;
        text-align: center;
    }

    .contact-info h4 {
        margin-bottom: 1rem;
    }

    .contact-link {
        color: white;
        text-decoration: underline;
    }

    .contact-link:hover {
        color: #fff3e0;
    }

    .terms-content p {
        line-height: 1.7;
        margin-bottom: 1rem;
    }

    .terms-content ul {
        margin: 1rem 0;
        padding-left: 2rem;
    }

    .terms-content li {
        margin-bottom: 0.5rem;
        line-height: 1.6;
    }

    .highlight-box {
        background: rgba(40, 167, 69, 0.1);
        border: 1px solid rgba(40, 167, 69, 0.3);
        border-radius: 10px;
        padding: 1.5rem;
        margin: 1.5rem 0;
    }

    @media (max-width: 768px) {
        .terms-container {
            padding: 2rem;
            margin: 1rem;
        }
        
        .terms-header h1 {
            font-size: 2rem;
        }
    }
</style>
@endpush

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="terms-container">
                <div class="terms-header">
                    <h1><i class="fas fa-file-contract me-3"></i>Conditions Générales de Vente</h1>
                    <p class="lead text-muted">ClicBillet CI - Plateforme de billetterie ivoirienne</p>
                </div>

                <div class="update-date">
                    <i class="fas fa-calendar-alt me-2"></i>
                    <strong>Dernière mise à jour :</strong> {{ date('d/m/Y') }}
                </div>

                <div class="terms-content">
                    <h2 class="section-title">1. Présentation</h2>
                    <p>Les présentes CGV ont pour objet de définir les droits et obligations des parties dans le cadre de la vente de tickets d'événements et de services connexes par l'intermédiaire de la plateforme de billetterie <strong>CLICBILLET</strong>.</p>
                    
                    <p>Les présentes conditions générales de vente ci-après dénommées <strong>«CGV»</strong> sont conclues d'une part par la société <strong>KOULIBALY ABISCHEK FINANCE MULTIBANKING FINANCE</strong> au capital de <strong>1 000 000 FCFA</strong> dont le siège social est situé à <strong>7 AVENUE NORGUES IMMEUBLE BSIC</strong>, immatriculée au registre du commerce et des sociétés d'Abidjan sous le numéro <strong>CI-ABJ-2017-B-21203</strong>, éditrice de la plateforme <strong>clicbillet.com</strong>, ci-après dénommé <strong>« CLICBILLET »</strong> et d'autre part, par toute personne physique ou morale ci-après dénommée <strong>« le Client »</strong>, souhaitant effectuer un achat via le site internet <strong>https://clicbillet.com</strong> et applications mobiles associées ci-après dénommées <strong>« la plateforme »</strong>.</p>

                    <div class="definition-box">
                        <h3 class="subsection-title">Définitions :</h3>
                        <ul>
                            <li><strong>Catalogue :</strong> liste des événements disponibles proposant des tickets sur la plateforme au moment de la consultation</li>
                            <li><strong>Code Client :</strong> code unique associé à chaque utilisateur inscrit sur la plateforme</li>
                            <li><strong>Promoteur :</strong> personne physique ou morale ayant publié un ou plusieurs événements sur la plateforme</li>
                            <li><strong>Articles :</strong> tout bien ou service en vente sur la plateforme à savoir des tickets d'événements et produits connexes</li>
                        </ul>
                    </div>

                    <h2 class="section-title">2. Objet du contrat</h2>
                    <p>Les présentes CGV ont pour objet de définir les droits et obligations des parties dans le cadre de la vente de tickets d'événements et de services connexes par l'intermédiaire de la plateforme de billetterie <strong>CLICBILLET</strong>.</p>

                    <h2 class="section-title">3. Acceptation des CGV</h2>
                    <p>Toute commande passée par le biais de la plateforme entraîne l'acceptation sans réserve des présentes CGV. Ces CGV fonctionnent conjointement avec notre politique de confidentialité et conditions générales d'utilisation.</p>

                    <h2 class="section-title">4. Caractéristiques des articles</h2>
                    <p>Les tickets en vente sont pour les événements mentionnés dans le catalogue. Cependant, nous pouvons également fournir d'autres articles liés aux tickets. Ces tickets sont disponibles à l'achat jusqu'à épuisement des stocks.</p>
                    
                    <p>Chaque événement est représenté par une image, une description textuelle, un ensemble de types de tickets et de nombreuses autres informations provenant de l'organisateur.</p>
                    
                    <p>Sur la plateforme, vous trouverez presque tous les types d'événements, allant des concerts aux festivals et aux événements sportifs et touristiques.</p>

                    <h2 class="section-title">5. Zone géographique</h2>
                    <p>La vente de tickets d'événements sur la plateforme n'est pas limitée à une zone géographique, bien que ces événements se déroulent généralement en <strong>Côte d'Ivoire</strong>.</p>
                    
                    <p>Les dates des événements et toutes les conditions se référant aux dates sauf indication contraire sont dans le fuseau horaire <strong>GMT (heure d'Abidjan)</strong>.</p>

                    <h2 class="section-title">6. Tarifs</h2>
                    <p>Les prix indiqués dans le catalogue et sur la page de sélection des tickets sont les prix mentionnés par l'organisateur de l'événement. <strong>CLICBILLET ne fixe pas le prix des tickets des événements</strong>, chaque organisateur est libre de fixer les prix des tickets et peut les modifier à tout moment.</p>
                    
                    <p>Des <strong>frais de service</strong> (taxes, frais de paiement...etc.), dont le montant est indiqué sur la page de paiement, peuvent s'ajouter à ces prix. Le montant TTC payé au moment de l'achat est donc le prix des tickets fixé par l'organisateur, majoré éventuellement des frais de service, constituant ainsi le prix TTC tenant compte de la TVA applicable à la date de la commande.</p>
                    
                    <p><strong>CLICBILLET</strong> se réserve le droit de modifier à tout moment le montant des frais de service, étant entendu que le montant figurant sur la page de paiement au jour de la commande sera le seul applicable au Client.</p>

                    <h2 class="section-title">7. Commandes</h2>
                    <p>Afin de passer une commande sur la plateforme, il est recommandé que le Client dispose d'une adresse e-mail valide et suive l'un des processus de commande suivants :</p>
                    
                    <div class="highlight-box">
                        <h3 class="subsection-title">Processus d'achat :</h3>
                        <ol>
                            <li><strong>Sélectionner</strong> des articles (tickets, sièges et produits connexes)</li>
                            <li><strong>Saisir les coordonnées</strong> du Client : e-mail, numéro de téléphone et informations de facturation éventuellement</li>
                            <li><strong>Payer</strong> la commande avec l'un des moyens de paiement disponibles</li>
                            <li>Pour les deux autres procédures, le Client doit posséder un compte préalablement sur la plateforme</li>
                        </ol>
                    </div>
                    
                    <p>Après validation du paiement, le Client reçoit un email de confirmation de commande. Le Client peut à tout moment au cours du processus de commande consulter le détail de sa commande ainsi que son prix total et corriger d'éventuelles erreurs, avant de confirmer pour exprimer son acceptation.</p>
                    
                    <p>Toute commande emporte contractuellement l'acceptation sans réserve de l'intégralité des conditions générales applicables au moment de l'achat telles que les conditions générales de vente et la politique de confidentialité disponibles sur la plateforme.</p>
                    
                    <p>En tant que <strong>mandataire de l'organisateur</strong> de l'événement dans la vente de ses tickets, <strong>CLICBILLET</strong> se réserve la propriété des articles jusqu'au règlement complet de la commande, c'est-à-dire jusqu'à l'encaissement du prix TTC de la commande.</p>
                    
                    <p><strong>CLICBILLET</strong> se réserve le droit d'annuler ou de refuser toute commande d'un Client avec lequel existerait un quelconque litige, notamment relatif au paiement d'une commande antérieure.</p>
                    
                    <p><strong>CLICBILLET</strong> s'engage à honorer les commandes reçues sur la plateforme seulement dans la limite des stocks disponibles.</p>

                    <h2 class="section-title">8. Modalités de paiement</h2>
                    <p>Le règlement des achats s'effectue par l'un des moyens suivants, au choix du Client :</p>
                    <ul>
                        <li><strong>Par Mobile Money</strong></li>
                        <li><strong>Par carte bancaire</strong></li>
                        <li><strong>Par code coupon</strong></li>
                    </ul>
                    
                    <p>Dès confirmation de l'achat, <strong>CLICBILLET</strong> se charge d'envoyer les tickets par e-mail au Client. Le délai de traitement de l'envoi de l'email est de <strong>5 minutes</strong> après validation du paiement. Les délais de réception de l'e-mail ne dépendent pas de <strong>CLICBILLET</strong>, mais du serveur de messagerie du Client.</p>
                    
                    <p><strong>CLICBILLET</strong> s'engage à livrer les commandes passées par le Client dans les délais impartis. En cas de non livraison des articles plus de <strong>deux (2) heures</strong> après la commande, le Client pourra procéder à la résolution de la vente et demander le remboursement, sauf cas de force majeure constaté.</p>
                    
                    <p>Le Client dispose d'un délai de <strong>sept (7) jours</strong> à compter de la date d'achat de la commande pour signaler la non réception. Passé ce délai, toute demande de résolution de la vente ne pourra être acceptée. Le Client devra adresser un courrier de réclamation à l'adresse suivante : <a href="mailto:contacts@clicbillet.com" class="contact-link">contacts@clicbillet.com</a>.</p>
                    
                    <p>Le Client est tenu de vérifier le bon état des articles livrés au moment de la livraison. Toute anomalie constatée (articles manquants, informations erronées, etc.) devra être signalée à <strong>CLICBILLET</strong>, par tous moyens suivant la livraison, notamment en écrivant à <a href="mailto:contacts@clicbillet.com" class="contact-link">contacts@clicbillet.com</a>.</p>
                    
                    <p>Avec les mêmes délais de livraison que l'envoi par email, les tickets d'événements réservés sont également disponibles, via la plateforme avec le compte associé à l'email qui a été utilisé lors de la commande.</p>

                    <h2 class="section-title">9. Utilisation et validité des tickets</h2>
                    <p>Tout ticket commandé et acheté sur la plateforme est soumis aux conditions suivantes :</p>
                    
                    <h3 class="subsection-title">Validité du ticket</h3>
                    <p>Un ticket n'est valable que pour l'événement qu'il concerne, à la date, à l'heure et dans les conditions indiquées sur le ticket. Il est disponible à tout moment dans le compte <strong>CLICBILLET</strong> associé à l'email utilisé lors de la réservation jusqu'à la fin de l'événement.</p>
                    
                    <h3 class="subsection-title">Revente de ticket</h3>
                    <p>Sauf accord préalable et écrit de <strong>CLICBILLET</strong>, il est formellement et expressément interdit d'offrir à la vente, vendre, revendre, échanger ou transférer un ticket, d'une quelconque manière et à quelque fin que ce soit (en ce compris promotionnelles ou dans le cadre d'une activité commerciale). Il est ainsi notamment interdit de revendre, de permettre la revente d'un ticket sur des plateformes commerciales ou par le biais d'intermédiaires ou de proposer un ticket sur lesdites plateformes, sans l'accord préalable et écrit de <strong>CLICBILLET</strong>. La seule alternative envisageable est le transfert de ticket entre utilisateurs <strong>CLICBILLET</strong> en utilisant la plateforme.</p>
                    
                    <h3 class="subsection-title">Reproduction de ticket</h3>
                    <p>Toute quelconque falsification, d'une quelconque manière et à quelque fin que ce soit, est formellement et expressément interdite, sous peine d'éventuelles poursuites judiciaires.</p>
                    
                    <p>Sauf stipulation expresse contraire de <strong>CLICBILLET</strong>, l'utilisation d'un ticket, quelle que soit sa forme, est unique, pour une seule entrée et pour un seul événement.</p>
                    
                    <p>L'organisateur peut refuser l'accès au lieu de l'événement s'il a des doutes sur l'origine ou la qualité du ticket qui lui est présenté. Compte tenu de la difficulté de vérifier avec certitude l'identité de l'acheteur, en cas de vérification obligatoire, seul le porteur du ticket original présenté directement sur la plateforme (dans l'application mobile par exemple) sera admis.</p>

                    <h2 class="section-title">10. Contrôle des tickets</h2>
                    <p><strong>CLICBILLET</strong> met à la disposition de l'organisateur de l'événement un système de validation, lecture du code QR des tickets. Sous la responsabilité de l'organisateur, ce système est le seul autorisé pour le contrôle des tickets.</p>
                    
                    <p>Le jour de l'événement, le Client ou participant à l'événement doit présenter le <strong>QR code</strong> du ticket valide aux personnes en charge du contrôle des tickets. Le QR code et les informations du ticket doivent être suffisamment éclairés et facilement lisibles. Le QR code permet l'identification du participant et le détail du ticket. L'organisateur pourra remettre éventuellement au Client, après contrôle, un ticket standard avec deux souches ou un bracelet lui permettant d'assister à l'événement.</p>
                    
                    <p>Chaque ticket ne peut être présenté qu'<strong>une seule fois</strong> au point de contrôle. Si un participant souhaite retourner au point de contrôle (après avoir quitté la salle par exemple), il est impératif de contacter les contrôleurs au préalable.</p>

                    <h2 class="section-title">11. Contrôle de l'identité</h2>
                    <p>L'organisateur et son équipe de contrôleurs se réservent le droit de contrôler l'identité du Client à l'entrée du lieu où se déroule l'événement. Le Client doit donc être muni d'une <strong>pièce d'identité officielle</strong>, en cours de validité et avec photo si nécessaire : carte d'identité, passeport, permis de conduire, titre de séjour ou tout autre document accepté par l'organisateur et les contrôleurs.</p>

                    <h2 class="section-title">12. Annulation et remboursements</h2>
                    <p>Tout ticket acheté sur la plateforme n'est annulable que <strong>30 JOURS</strong> avant la date de début de l'événement.</p>
                    
                    <p>Lorsque le Client annule sa réservation d'un ticket, il bénéficie d'un remboursement.</p>
                    
                    <p>Si un événement a été <strong>annulé par l'organisateur</strong> de l'événement, tous les participants seront remboursés.</p>
                    
                    <div class="highlight-box">
                        <p><strong><i class="fas fa-exclamation-triangle me-2"></i>Important :</strong> En cas de remboursement d'un ticket, quelle que soit la raison, le montant remboursé sera partiel et équivaudra à <strong>90%</strong> du prix du ticket. Le remboursement se fait par le biais de <strong>code coupon</strong>, utilisable uniquement sur la plateforme jusqu'à épuisement de son montant.</p>
                    </div>

                    <h2 class="section-title">13. Responsabilité</h2>
                    <p><strong>CLICBILLET</strong> est responsable à l'égard des présentes CGV. Conformément à nos conditions générales d'utilisation, <strong>CLICBILLET</strong> décline toute responsabilité en cas d'indisponibilité ou dysfonctionnement du service résultant d'un cas de force majeure.</p>
                    
                    <p><strong>CLICBILLET</strong> ne pourra être tenu responsable des incidents pouvant survenir lors de la commande. De même, <strong>CLICBILLET</strong> n'est pas responsable en cas de perte, de vol ou d'utilisation illicite d'un ticket.</p>
                    
                    <p><strong>CLICBILLET</strong> n'est pas responsable des questions de santé et de sécurité des événements du catalogue.</p>
                    
                    <p><strong>CLICBILLET</strong> n'est pas responsable du déroulement de l'événement (modification de contenu, changement de distribution artistique ou sportive, changement d'horaires, etc.) ou de son annulation.</p>
                    
                    <p>Chaque organisateur fixe les règles propres à l'organisation de l'événement. Ce règlement est communiqué par l'organisateur au Client.</p>
                    
                    <p>Toute commande du Client implique son adhésion au règlement de l'organisateur, sous peine de voir sa responsabilité engagée.</p>

                    <h2 class="section-title">14. Service Client</h2>
                    <div class="contact-info">
                        <h4><i class="fas fa-headset me-2"></i>Besoin d'aide ?</h4>
                        <p>Pour toute information, vous pouvez contacter le service Client de <strong>CLICBILLET</strong> :</p>
                        <p>
                            <i class="fas fa-phone me-2"></i><strong>Téléphone :</strong> 
                            <a href="tel:+22507024902" class="contact-link">+225 07 02 49 02 77</a><br>
                            <small>(prix d'un appel local)</small>
                        </p>
                        <p>
                            <i class="fas fa-envelope me-2"></i><strong>Email :</strong> 
                            <a href="mailto:contact@clicbillet.com" class="contact-link">contact@clicbillet.com</a>
                        </p>
                    </div>

                    <h2 class="section-title">15. Droit applicable et litiges</h2>
                    <p>Les ventes de tickets effectuées sur la plateforme sont soumises à la <strong>loi ivoirienne</strong>. En cas de litige, les <strong>tribunaux ivoiriens</strong> seront seuls compétents.</p>

                    <hr class="my-5">

                    <div class="text-center">
                        <p class="text-muted">
                            <i class="fas fa-balance-scale me-2"></i>
                            Ces conditions générales de vente sont effectives à compter du {{ date('d/m/Y') }}
                        </p>
                        <a href="{{ route('home') }}" class="btn btn-outline-secondary me-3">
                            <i class="fas fa-arrow-left me-2"></i>Retour à l'accueil
                        </a>
                        <a href="{{ route('pages.contact') }}" class="btn btn-primary">
                            <i class="fas fa-question-circle me-2"></i>Questions sur les CGV
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection