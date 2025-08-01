<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\User;

class PageController extends Controller
{
    /**
     * Page À propos
     */
    public function about()
    {
        $stats = [
            'total_events' => Event::where('status', 'published')->count(),
            'total_users' => User::where('role', 'acheteur')->count(),
            'total_promoters' => User::where('role', 'promoteur')->count(),
            'categories_count' => EventCategory::count(),
        ];

        return view('pages.about', compact('stats'));
    }

    /**
     * Comment ça marche
     */
    public function howItWorks()
    {
        return view('pages.how-it-works');
    }

    /**
     * FAQ - Foire aux questions
     */
    public function faq()
    {
        $faqs = [
            'general' => [
                'title' => 'Questions générales',
                'questions' => [
                    [
                        'question' => 'Comment acheter des billets sur ClicBillet CI ?',
                        'answer' => 'C\'est très simple ! Parcourez les événements, sélectionnez vos billets, ajoutez-les au panier et procédez au paiement. Vous recevrez vos billets par email immédiatement.'
                    ],
                    [
                        'question' => 'Quels moyens de paiement acceptez-vous ?',
                        'answer' => 'Nous acceptons les cartes bancaires (Visa, Mastercard), Mobile Money (Orange Money, MTN Money, Moov Money) et les virements bancaires.'
                    ],
                    [
                        'question' => 'Puis-je annuler ma commande ?',
                        'answer' => 'Les annulations sont possibles jusqu\'à 48h avant l\'événement selon les conditions de l\'organisateur. Contactez notre support pour plus d\'informations.'
                    ]
                ]
            ],
            'organizers' => [
                'title' => 'Pour les organisateurs',
                'questions' => [
                    [
                        'question' => 'Comment devenir promoteur sur ClicBillet CI ?',
                        'answer' => 'Inscrivez-vous avec un compte promoteur, soumettez vos documents d\'identification, et commencez à créer vos événements après validation.'
                    ],
                    [
                        'question' => 'Quelles sont vos commissions ?',
                        'answer' => 'Nos commissions varient de 5% à 15% selon le type d\'événement et le volume. Consultez notre page tarifs pour plus de détails.'
                    ]
                ]
            ],
            'technical' => [
                'title' => 'Questions techniques',
                'questions' => [
                    [
                        'question' => 'Comment utiliser mes billets électroniques ?',
                        'answer' => 'Présentez votre billet PDF ou l\'écran de votre téléphone avec le QR code à l\'entrée. Assurez-vous que votre téléphone soit chargé.'
                    ],
                    [
                        'question' => 'Que faire si je n\'ai pas reçu mes billets ?',
                        'answer' => 'Vérifiez vos spams. Si vous ne trouvez toujours pas vos billets, contactez notre support avec votre numéro de commande.'
                    ]
                ]
            ]
        ];

        return view('pages.faq', compact('faqs'));
    }

    /**
     * Page contact
     */
    public function contact()
    {
        return view('pages.contact');
    }

    /**
     * Traitement du formulaire de contact
     */
    public function submitContact(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10',
        ], [
            'name.required' => 'Le nom est obligatoire',
            'email.required' => 'L\'email est obligatoire',
            'email.email' => 'L\'email doit être valide',
            'subject.required' => 'Le sujet est obligatoire',
            'message.required' => 'Le message est obligatoire',
            'message.min' => 'Le message doit contenir au moins 10 caractères',
        ]);

        try {
            // Envoyer l'email à l'équipe support
            Mail::raw(
                "Nouveau message de contact\n\n" .
                "Nom: {$request->name}\n" .
                "Email: {$request->email}\n" .
                "Sujet: {$request->subject}\n\n" .
                "Message:\n{$request->message}",
                function ($message) use ($request) {
                    $message->to(config('mail.from.address'))
                            ->subject('[ClicBillet] ' . $request->subject)
                            ->replyTo($request->email, $request->name);
                }
            );

            return redirect()->route('pages.contact')
                           ->with('success', 'Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.');

        } catch (\Exception $e) {
            Log::error('Erreur envoi formulaire contact: ' . $e->getMessage());

            return redirect()->route('pages.contact')
                           ->with('error', 'Une erreur est survenue lors de l\'envoi. Veuillez réessayer.')
                           ->withInput();
        }
    }

    /**
     * Conditions d'utilisation
     */
    public function termsOfService()
    {
        return view('pages.terms');
    }

    /**
     * Politique de confidentialité
     */
    public function privacyPolicy()
    {
        return view('pages.privacy');
    }

    /**
     * Mentions légales
     */
    public function legalMentions()
    {
        return view('pages.legal');
    }

    /**
     * Guide promoteur
     */
    public function promoterGuide()
    {
        return view('pages.promoter-guide');
    }

    /**
     * Tarifs
     */
    public function pricing()
    {
        $pricing = [
            'basic' => [
                'name' => 'Événements gratuits',
                'commission' => '0%',
                'features' => [
                    'Billetterie gratuite',
                    'Support email',
                    'Outils de base'
                ]
            ],
            'standard' => [
                'name' => 'Événements payants',
                'commission' => '5%',
                'features' => [
                    'Commission réduite',
                    'Support prioritaire',
                    'Statistiques avancées',
                    'Marketing email'
                ]
            ],
            'premium' => [
                'name' => 'Événements premium',
                'commission' => '3%',
                'features' => [
                    'Commission minimale',
                    'Support téléphonique',
                    'Manager dédié',
                    'Marketing personnalisé',
                    'API access'
                ]
            ]
        ];

        return view('pages.pricing', compact('pricing'));
    }

    /**
     * Support
     */
    public function support()
    {
        return view('pages.support');
    }

    /**
     * Traitement du formulaire de support
     */
    public function submitSupport(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'category' => 'required|string',
            'priority' => 'required|string',
            'message' => 'required|string|min:10',
        ]);

        try {
            // Envoyer le ticket de support
            Mail::raw(
                "Nouveau ticket de support [{$request->priority}]\n\n" .
                "Catégorie: {$request->category}\n" .
                "Nom: {$request->name}\n" .
                "Email: {$request->email}\n\n" .
                "Message:\n{$request->message}",
                function ($message) use ($request) {
                    $message->to(config('mail.from.address'))
                            ->subject("[Support ClicBillet] {$request->category} - {$request->priority}")
                            ->replyTo($request->email, $request->name);
                }
            );

            return redirect()->route('pages.support')
                           ->with('success', 'Votre demande de support a été envoyée. Nous vous répondrons sous 24h.');

        } catch (\Exception $e) {
            Log::error('Erreur envoi support: ' . $e->getMessage());

            return redirect()->route('pages.support')
                           ->with('error', 'Erreur lors de l\'envoi. Veuillez réessayer.')
                           ->withInput();
        }
    }
}