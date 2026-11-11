<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TicketType;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Commission;
use App\Exceptions\CheckoutRefusedException;
use App\Services\CheckoutService;
use App\Services\EmailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class GuestCheckoutController extends Controller
{
    /**
     * Page de checkout invité (méthode show inchangée)
     */
    public function show()
    {
        $cart = session()->get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('cart.show')->with('error', 'Votre panier est vide.');
        }

        // Vérifications de disponibilité...
        
        $cartTotal = array_sum(array_column($cart, 'total_price'));

        return view('checkout.guest', compact('cart', 'cartTotal'));
    }

    /**
     * Traitement du checkout invité (paiement manuel confirmé par l'admin).
     *
     * Aucune création/conversion de compte ici (E1/N1) : le checkout invité ne crée qu'un compte invité
     * inutilisable (mot de passe aléatoire) pour un email inconnu et ne touche JAMAIS à un compte existant.
     * Aucun chemin ne convertit un invité en compte tant qu'il n'existe pas de vérification d'email.
     * La réponse est identique que l'email soit connu ou non.
     */
    public function process(Request $request)
    {
        $service = app(CheckoutService::class);

        Log::info('Début checkout invité (paiement manuel)', [
            'payment_method' => $request->payment_method,
        ]);

        try {
            $request->validate([
                'email' => 'required|email|max:255',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone' => 'required|string|min:10|max:20',
                'terms_accepted' => 'required|accepted',
                'payment_method' => CheckoutService::paymentMethodRule(),
            ]);

            // Vérification du panier
            $cart = session()->get('cart', []);
            if (empty($cart)) {
                return redirect()->route('cart.show')->with('error', 'Votre panier est vide.');
            }

            $groups = $service->buildEventGroups($cart);
            $tickets = $service->countTickets($groups);
            $service->assertWithinOrderLimits((string) $request->email, null, $request->ip(), count($groups), true, $tickets);

            // N-A : lecture (non verrouillante) du compte AVANT la transaction, pour qu'aucune lecture ne
            // fixe l'instantané MySQL avant les verrous de réservation.
            $existing = User::where('email', $request->email)->first();

            // Utilisateur + commandes + stock + billets dans UNE transaction (prix recalculés côté serveur)
            [$user, $orders] = DB::transaction(function () use ($request, $groups, $service, $existing) {
                $user = $existing ?? $this->createGuest($request);

                return [$user, $this->createOrdersFromGroups($service, $groups, $user, $request)];
            });

            $service->generateQrCodes($orders); // I2 : hors transaction

            $service->recordGuestOrders($request->ip(), count($orders), $tickets);

            // Preuve de possession liée à la session (retour/annulation)
            foreach ($orders as $o) {
                $service->rememberGuestOrder($o);
            }

            // Instructions de paiement par email, puis page de confirmation
            $service->sendManualInstructions($orders);
            session()->forget(['cart', 'cart_timer']);

            return $this->redirectToConfirmation($orders);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (CheckoutRefusedException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Throwable $e) {
            $service->logFailure('Erreur checkout invité', $e);

            return back()->with('error', CheckoutService::GENERIC_ERROR)->withInput();
        }
    }

    /**
     * Redirection vers la page de confirmation par token (même réponse que l'email soit connu ou non).
     */
    private function redirectToConfirmation(array $orders)
    {
        $message = count($orders) > 1
            ? 'Commandes enregistrées ! Instructions de paiement envoyées par email.'
            : 'Commande enregistrée ! Instructions de paiement envoyées par email.';
        $others = collect($orders)->skip(1)->pluck('order_number')->all();

        return redirect()->route('checkout.guest.confirmation', $orders[0]->guest_token)
            ->with('success', $message)->with('other_order_numbers', $others);
    }

    /**
     * Crée les commandes. Toujours un guest_token (même pour un email déjà connu) afin que la
     * réponse soit identique ; le token seul ne donne aucun droit sur le compte.
     */
    private function createOrdersFromGroups(CheckoutService $service, array $groups, User $user, Request $request): array
    {
        $orders = [];

        foreach ($groups as $eventId => $group) {
            $orders[] = $service->createOrder($user->id, $eventId, $group, [
                'payment_method' => $request->payment_method,
                'billing_email' => $request->email,
                'billing_phone' => $request->phone,
                'guest_token' => Str::random(32),
            ]);
        }

        return $orders;
    }

    /**
     * Crée un compte invité (email inconnu). Un compte existant est lu AVANT la transaction (N-A) et
     * n'est jamais modifié (ni nom, ni téléphone, ni statut), sans connexion automatique.
     */
    private function createGuest(Request $request): User
    {
        return User::create([
            'name' => trim($request->first_name . ' ' . $request->last_name),
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => $this->unusablePasswordHash(),
            'role' => 'acheteur',
            'is_guest' => true,
        ]);
    }

    /**
     * Empreinte de mot de passe inutilisable (N5) : forme bcrypt valide mais aléatoire, sans calcul bcrypt.
     * Aucune valeur en clair ne lui correspond ; le coût est donc le même que l'email soit connu ou non
     * (aucun hachage coûteux dans la branche « email inconnu »). Seul un reset de mot de passe
     * (avec preuve de possession de l'email) pourra définir un vrai mot de passe.
     */
    private function unusablePasswordHash(): string
    {
        $alphabet = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $salt = '';
        for ($i = 0; $i < 53; $i++) {
            $salt .= $alphabet[random_int(0, 63)];
        }

        // Le coût doit correspondre à la config de hachage (le cast `hashed` vérifie la configuration)
        return sprintf('$2y$%02d$', (int) config('hashing.bcrypt.rounds', 12)) . $salt;
    }

    /**
     * Page de confirmation pour invités (méthode existante)
     */
    public function confirmation($token)
    {
        $order = Order::where('guest_token', $token)
                     ->with(['user', 'event', 'orderItems.ticketType', 'tickets'])
                     ->firstOrFail();
        
        return view('checkout.guest-confirmation', compact('order'));
    }
}
