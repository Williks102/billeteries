<?php

namespace Tests\Feature;

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** 419 : message clair + retour au formulaire (sans mot de passe) ; pages d'authentification jamais mises en cache. */
class SessionExpiredProtectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['session.driver' => 'array', 'app.key' => 'base64:' . base64_encode(str_repeat('t', 32))]);
        Route::middleware('web')->post('/_t/expire', fn () => throw new TokenMismatchException('x'))->name('t.expire');
        Route::middleware('web')->post('/_t/logout', fn () => throw new TokenMismatchException('x'))->name('logout');
    }

    public function test_token_mismatch_redirects_back_with_a_clear_message_and_keeps_input_but_not_passwords(): void
    {
        $res = $this->from('/login')->post('/_t/expire', [
            'email' => 'a@b.test', 'password' => 'secret-123', 'password_confirmation' => 'secret-123', 'name' => 'Awa',
        ]);

        $res->assertRedirect('/login');
        $res->assertSessionHasErrors('session');
        $this->assertStringContainsString('session a expiré', session('errors')->first('session'));
        $this->assertSame('a@b.test', session()->getOldInput('email'));
        $this->assertSame('Awa', session()->getOldInput('name'));
        $this->assertNull(session()->getOldInput('password'));
        $this->assertNull(session()->getOldInput('password_confirmation'));
        $this->assertNull(session()->getOldInput('_token'));
    }

    public function test_expired_logout_goes_to_login(): void
    {
        $this->from('/mon-compte')->post('/_t/logout')->assertRedirect(route('login'));
    }

    public function test_json_requests_keep_the_419_status(): void
    {
        $this->postJson('/_t/expire')->assertStatus(419);
    }

    public function test_auth_get_pages_are_not_cacheable_but_others_are_untouched(): void
    {
        foreach (['/login', '/register'] as $path) {
            $cc = $this->get($path)->headers->get('Cache-Control');
            $this->assertStringContainsString('no-store', $cc, $path);
        }
        $this->assertStringNotContainsString('no-store', (string) $this->get('/pricing')->headers->get('Cache-Control'));
    }

    public function test_login_view_displays_the_session_error(): void
    {
        $this->withSession(['errors' => new \Illuminate\Support\ViewErrorBag])->get('/login')->assertOk();
        $html = view('auth.login', ['errors' => (new \Illuminate\Support\ViewErrorBag)->put('default', new \Illuminate\Support\MessageBag(['session' => 'Votre session a expiré.']))])->render();
        $this->assertStringContainsString('Votre session a expiré.', $html);
    }
}
