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
use Illuminate\Support\Facades\Hash;
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
        $serviceFee = $cartTotal > 0 ? 500 : 0;
        $finalTotal = $cartTotal + $serviceFee;

        return view('checkout.guest', compact('cart', 'cartTotal', 'serviceFee', 'finalTotal'));
    }

    /**
     * Traitement du checkout invité (paiement manuel confirmé par l'admin).
     *
     * Aucune création/conversion de compte ici (E1) : le checkout invité ne crée qu'un compte invité
     * pour un email inconnu et ne touche JAMAIS à un compte existant. La réponse est identique que
     * l'email soit connu ou non. Le compte se crée après achat (createAccountAfterPurchase).
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
            $service->assertWithinOrderLimits((string) $request->email, null, $request->ip(), count($groups), true);

            // Utilisateur + commandes + stock + billets dans UNE transaction (prix recalculés côté serveur)
            [$user, $orders] = DB::transaction(function () use ($request, $groups, $service) {
                $user = $this->findOrCreateGuest($request);

                return [$user, $this->createOrdersFromGroups($service, $groups, $user, $request)];
            });

            $service->recordGuestOrders($request->ip(), count($orders));

            // Preuve de possession liée à la session (retour/annulation/création de compte)
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
     * réponse soit identique ; le token seul ne donne aucun droit sur le compte (cf. createAccountAfterPurchase).
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
     * Récupère le compte portant cet email SANS le modifier (ni nom, ni téléphone, ni statut),
     * sans connexion automatique ; crée un compte invité si l'email est inconnu.
     */
    private function findOrCreateGuest(Request $request): User
    {
        $existing = User::where('email', $request->email)->first();
        if ($existing) {
            return $existing;
        }

        return User::create([
            'name' => trim($request->first_name . ' ' . $request->last_name),
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make(Str::random(32)),
            'role' => 'acheteur',
            'is_guest' => true,
        ]);
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

    /**
     * Permettre à un invité de créer un compte après achat.
     * Preuve exigée : token de commande + session qui a créé la commande (C1) ET commande à l'origine
     * du compte invité (la plus ancienne de ce compte) : un tiers ayant passé commande avec l'email
     * d'un invité existant ne peut donc jamais le convertir.
     */
    public function createAccountAfterPurchase(Request $request, $token)
    {
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $order = Order::where('guest_token', $token)->firstOrFail();

        // Le token seul (URL) ne suffit pas : la session doit avoir créé cette commande
        $service = app(CheckoutService::class);
        if (!$service->ownsGuestOrder($order, (string) $token)) {
            abort(403);
        }

        $refused = back()->withErrors([
            'error' => 'Impossible de créer un compte depuis cette commande. Si vous avez déjà un compte, connectez-vous.',
        ]);

        try {
            $user = DB::transaction(function () use ($order, $request) {
                $user = User::whereKey($order->user_id)->lockForUpdate()->first();

                if (!$user || !$user->is_guest || (int) $user->orders()->min('id') !== (int) $order->id) {
                    return null;
                }

                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'is_guest' => false,
                    'guest_converted_at' => now(),
                    'email_verified_at' => null, // la possession de l'email n'est pas prouvée
                ])->save();

                // Supprimer les tokens invité
                Order::where('user_id', $user->id)->update(['guest_token' => null]);

                return $user;
            });

            if (!$user) {
                return $refused;
            }

            auth()->login($user);
            $request->session()->regenerate();

            return redirect()->route('acheteur.dashboard')->with('success',
                'Votre compte a été créé ! Vous pouvez maintenant gérer toutes vos commandes.');

        } catch (\Throwable $e) {
            $service->logFailure('Erreur création de compte après achat', $e);

            return back()->withErrors(['error' => 'Erreur lors de la création du compte.']);
        }
    }
}
