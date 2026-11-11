<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TicketType;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Commission;
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
     * Traitement du checkout invité (paiement manuel confirmé par l'admin)
     */
    public function process(Request $request)
    {
        Log::info('Début checkout invité (paiement manuel)', [
            'create_account' => $request->boolean('create_account'),
            'payment_method' => $request->payment_method,
        ]);

        try {
            // Validation conditionnelle
            $rules = [
                'email' => 'required|email',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255', 
                'phone' => 'required|string|min:10',
                'terms_accepted' => 'required',
                'payment_method' => CheckoutService::paymentMethodRule(),
            ];
            
            // Mot de passe requis seulement si création de compte
            if ($request->boolean('create_account')) {
                $rules['password'] = 'required|min:6|confirmed';
            }
            
            $validated = $request->validate($rules);
            
            // Vérification du panier
            $cart = session()->get('cart', []);
            if (empty($cart)) {
                return redirect()->route('cart.show')->with('error', 'Votre panier est vide.');
            }

            // Utilisateur + commandes + stock + billets dans UNE transaction (prix recalculés côté serveur)
            [$user, $orders] = DB::transaction(function () use ($request, $cart) {
                $user = $this->handleUser($request);

                return [$user, $this->createOrdersFromCart($cart, $user, $request)];
            });

            // Preuve de possession liée à la session (retour/annulation/création de compte)
            foreach ($orders as $o) {
                app(CheckoutService::class)->rememberGuestOrder($o);
            }

            // Instructions de paiement par email, puis page de confirmation
            app(CheckoutService::class)->sendManualInstructions($orders);
            session()->forget(['cart', 'cart_timer']);

            return $this->redirectToConfirmation($orders);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Erreur checkout invité', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Erreur: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Redirection vers la page de confirmation : par token pour un invité, par commande
     * pour un utilisateur connecté (compte créé pendant le checkout).
     */
    private function redirectToConfirmation(array $orders)
    {
        $first = $orders[0];
        $message = count($orders) > 1
            ? 'Commandes enregistrées ! Instructions de paiement envoyées par email.'
            : 'Commande enregistrée ! Instructions de paiement envoyées par email.';
        $others = collect($orders)->skip(1)->pluck('order_number')->all();

        if ($first->guest_token) {
            return redirect()->route('checkout.guest.confirmation', $first->guest_token)
                ->with('success', $message)->with('other_order_numbers', $others);
        }

        if (auth()->check() && $first->user_id === auth()->id()) {
            return redirect()->route('checkout.confirmation', $first)
                ->with('success', $message)->with('other_order_numbers', $others);
        }

        return redirect()->route('home')->with('success', $message);
    }

    /**
     * Créer les commandes depuis le panier (ADAPTÉE À VOTRE LOGIQUE)
     */
    private function createOrdersFromCart($cart, $user, $request)
    {
        $service = app(CheckoutService::class);
        $orders = [];

        foreach ($service->buildEventGroups($cart) as $eventId => $group) {
            $orders[] = $service->createOrder($user->id, $eventId, $group, [
                'payment_method' => $request->payment_method,
                'billing_email' => $request->email,
                'billing_phone' => $request->phone,
                'guest_token' => $user->is_guest ? Str::random(32) : null,
            ]);
        }

        return $orders;
    }

    /**
     * Enregistre l'utilisateur avec forceFill : is_guest, first_name et last_name ne sont pas dans
     * User::$fillable, donc create()/update() les ignoraient silencieusement (aucun guest_token,
     * donc aucune page de confirmation invité). Valeurs issues du serveur/validées uniquement.
     */
    private function saveUser(User $user, array $attributes): User
    {
        $user->forceFill($attributes)->save();

        return $user;
    }

    // ===== VOS MÉTHODES EXISTANTES (inchangées) =====

    /**
     * Gérer la création/récupération de l'utilisateur (VOTRE LOGIQUE)
     */
    private function handleUser(Request $request)
    {
        Log::info('Début handle user invité', [
            'email' => $request->email,
            'create_account' => $request->boolean('create_account')
        ]);
        
        $email = $request->email;
        $existingUser = User::where('email', $email)->first();
        $createAccount = $request->boolean('create_account');
        
        // Si utilisateur existe
        if ($existingUser) {
            Log::info('Utilisateur existant trouvé', [
                'user_id' => $existingUser->id,
                'is_guest' => $existingUser->is_guest
            ]);
            
            // Si demande création de compte mais utilisateur normal existe
            if ($createAccount && !$existingUser->is_guest) {
                throw new \Exception('Un compte existe déjà avec cet email. Veuillez vous connecter.');
            }
            
            // Si utilisateur normal, l'utiliser
            if (!$existingUser->is_guest) {
                return $existingUser;
            }
            
            // Si création de compte sur un invité existant
            if ($createAccount && $existingUser->is_guest) {
                Log::info('Conversion invité vers compte');
                $this->saveUser($existingUser, [
                    'name' => $request->first_name . ' ' . $request->last_name,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone' => $request->phone,
                    'password' => Hash::make($request->password),
                    'is_guest' => false,
                    'guest_converted_at' => now()
                ]);
                auth()->login($existingUser);
                return $existingUser;
            }
            
            // Mise à jour invité existant
            Log::info('Mise à jour invité existant');
            $this->saveUser($existingUser, [
                'name' => $request->first_name . ' ' . $request->last_name,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
            ]);
            return $existingUser;
        }
        
        // Utilisateur n'existe pas - créer selon le mode
        if ($createAccount) {
            Log::info('Création nouveau compte');
            $user = $this->saveUser(new User(), [
                'name' => $request->first_name . ' ' . $request->last_name,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => 'acheteur',
                'email_verified_at' => now()
            ]);
            auth()->login($user);
            return $user;
        } else {
            Log::info('Création nouvel invité');
            return $this->saveUser(new User(), [
                'name' => $request->first_name . ' ' . $request->last_name,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $email,
                'phone' => $request->phone,
                'password' => Hash::make(Str::random(16)),
                'role' => 'acheteur',
                'is_guest' => true
            ]);
        }
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
     * Permettre à un invité de créer un compte après achat (méthode existante)
     */
    public function createAccountAfterPurchase(Request $request, $token)
    {
        // Votre méthode existante reste identique
        $request->validate([
            'password' => 'required|min:6|confirmed'
        ]);

        $order = Order::where('guest_token', $token)->firstOrFail();

        // Le token seul (URL) ne suffit pas : la session doit avoir créé cette commande
        if (!app(CheckoutService::class)->ownsGuestOrder($order, (string) $token)) {
            abort(403);
        }

        $user = $order->user;

        if (!$user->is_guest) {
            return redirect()->route('home')->with('error', 'Action non autorisée.');
        }

        try {
            DB::beginTransaction();

            // Convertir l'invité en utilisateur normal
            $user->update([
                'name' => $user->first_name . ' ' . $user->last_name,
                'password' => Hash::make($request->password),
                'is_guest' => false,
                'guest_converted_at' => now()
            ]);

            // Supprimer les tokens invité
            Order::where('user_id', $user->id)->update(['guest_token' => null]);

            // Connecter l'utilisateur
            auth()->login($user);

            DB::commit();

            return redirect()->route('acheteur.dashboard')->with('success', 
                'Votre compte a été créé ! Vous pouvez maintenant gérer toutes vos commandes.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur lors de la création du compte.']);
        }
    }
}