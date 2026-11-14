<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\EmailService;
use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use App\Http\Middleware\PasswordResetThrottle;


class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
        // N3a : inscription limitée par IP (comptes jetables)
        $this->middleware('throttle:' . config('payments.register_throttle_per_minute', 5) . ',1,register')->only('register');
    }

    /**
     * Inscription. Si l'e-mail appartient à un compte INVITÉ (achat sans compte), on ne crée rien, on ne modifie
     * rien et on ne connecte personne : on envoie un lien d'activation à l'adresse (preuve de possession).
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        $email = (string) $request->input('email');
        $guest = User::where('email', $email)->where('is_guest', true)->exists();

        if ($guest) {
            return $this->requestGuestActivation($request, $email);
        }

        event(new \Illuminate\Auth\Events\Registered($user = $this->create($request->all())));

        $this->guard()->login($user);

        if ($response = $this->registered($request, $user)) {
            return $response;
        }

        return $request->wantsJson() ? new \Illuminate\Http\JsonResponse([], 201) : redirect($this->redirectPath());
    }

    /**
     * Demande d'activation : mêmes protections que « mot de passe oublié » (IP + couple e-mail/IP, délai du broker),
     * envoi APRÈS la réponse, réponse identique que le lien parte ou non, journal sans donnée personnelle.
     * Les valeurs saisies (nom, mot de passe…) sont jetées : le mot de passe se choisit à l'activation.
     */
    private function requestGuestActivation(Request $request, string $email)
    {
        $allowed = PasswordResetThrottle::attempt($request, 'forgot') === null;
        $emailHash = hash_hmac('sha256', mb_strtolower(trim($email)), (string) config('app.key'));

        if ($allowed) {
            $broker = Password::broker();
            app()->terminating(function () use ($broker, $email, $emailHash) {
                try {
                    $response = $broker->sendResetLink(['email' => $email]);
                    Log::info('Demande d\'activation de compte invité', [
                        'email_hash' => $emailHash,
                        'sent' => $response === Password::RESET_LINK_SENT,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Erreur lors de la demande d\'activation de compte invité', ['email_hash' => $emailHash]);
                }
            });
        }

        $message = in_array(config('mail.default'), ['log', 'array'], true)
            ? 'Cet e-mail a déjà servi pour un achat sans compte. L\'envoi d\'e-mails n\'est pas activé sur cette démonstration : le lien d\'activation ne peut pas être reçu.'
            : 'Cet e-mail a déjà servi pour un achat sans compte. Nous vous envoyons un lien pour activer votre compte et retrouver vos billets. Ouvrez-le depuis votre boîte e-mail.';

        return redirect()->route('register')->with('status', $message);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->where('is_guest', 0)],
            'phone' => ['required', 'string', 'max:20'],
            'role' => ['required', 'in:acheteur,promoteur'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
            'role.required' => 'Vous devez choisir un type de compte.',
            'role.in' => 'Le type de compte doit être Acheteur ou Promoteur.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
{
    // Nettoyer le numéro de téléphone
    $phone = $this->cleanPhoneNumber($data['phone']);
    
    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'phone' => $phone,
        'role' => $data['role'],
        'password' => Hash::make($data['password']),
        // customer_code sera généré automatiquement via boot()
    ]);

    // Envoyer l'email de bienvenue avec le code client
    try {
        $emailService = app(\App\Services\EmailService::class);
        $emailService->sendWelcomeEmail($user);
    } catch (\Exception $e) {
        \Log::error("Erreur envoi email bienvenue", [
            'user_id' => $user->id,
            'error' => $e->getMessage()
        ]);
    }
    
    return $user;
}

    /**
     * Nettoyer et formater le numéro de téléphone
     *
     * @param string $phone
     * @return string
     */
    private function cleanPhoneNumber($phone)
    {
        // Supprimer tous les espaces et caractères non numériques sauf le +
        $clean = preg_replace('/[^\d+]/', '', $phone);
        
        // Si le numéro commence par +225, le garder tel quel
        if (strpos($clean, '+225') === 0) {
            return $clean;
        }
        
        // Si le numéro commence par 225, ajouter le +
        if (strpos($clean, '225') === 0) {
            return '+' . $clean;
        }
        
        // Si le numéro commence par 0, remplacer par +225
        if (strpos($clean, '0') === 0) {
            return '+225' . substr($clean, 1);
        }
        
        // Sinon, ajouter +225 devant
        return '+225' . $clean;
    }

    /**
     * The user has been registered.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function registered(\Illuminate\Http\Request $request, $user)
{
    // Envoyer l'email de bienvenue
    $this->sendWelcomeEmail($user);
    
    // Redirection personnalisée selon le rôle (garder votre code existant)
    if ($user->isAdmin()) {
        return redirect()->route('admin.dashboard');
    } elseif ($user->isPromoteur()) {
        return redirect()->route('promoteur.dashboard');
    } elseif ($user->isAcheteur()) {
        return redirect()->route('acheteur.dashboard');
    }
    
    return redirect($this->redirectPath());
    }

    protected function sendWelcomeEmail(User $user)
{
    try {
        Mail::queue(new WelcomeEmail($user));
        
        \Log::info("Email de bienvenue mis en file d'attente", [
            'user_id' => $user->id,
            'role' => $user->role
        ]);
        
    } catch (\Exception $e) {
        \Log::error("Erreur envoi email bienvenue", [
            'user_id' => $user->id,
            'error' => $e->getMessage()
        ]);
        
        // Ne pas faire échouer l'inscription pour un problème d'email
    }
    }
}