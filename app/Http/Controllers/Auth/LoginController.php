<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    /**
     * Journal d'activité : seules les connexions d'administrateur sont tracées (identifiant du compte, jamais l'e-mail).
     */
    protected function authenticated(\Illuminate\Http\Request $request, $user)
    {
        if (($user->role ?? null) === 'admin') {
            \App\Services\ActivityLogger::record(
                \App\Models\ActivityLog::AUTH_ADMIN_LOGIN,
                $user,
                'Connexion administrateur'
            );
        }

        return null;
    }

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
}
