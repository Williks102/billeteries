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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
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
        
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $phone,
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
        ]);
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
        Mail::to($user->email)->send(new WelcomeEmail($user));
        
        \Log::info("Email de bienvenue envoyé", [
            'user_id' => $user->id,
            'email' => $user->email,
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