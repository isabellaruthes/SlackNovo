<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class AuthController extends Controller
{
    private const LOGIN_WINDOW_SECONDS = 60;

    private const ACCOUNT_FAILURE_LIMIT = 5;

    private const IP_ATTEMPT_LIMIT = 30;

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $ipKey = 'login:ip:'.hash('sha256', (string) $request->ip());
        $this->ensureLoginIsNotThrottled($ipKey, self::IP_ATTEMPT_LIMIT);
        RateLimiter::hit($ipKey, self::LOGIN_WINDOW_SECONDS);

        $credentials = $request->validate([
            'nome' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:1024'],
        ]);

        // The account limit remains shared when the client changes IP or casing.
        $accountKey = 'login:account:'.hash('sha256', Str::lower(trim($credentials['nome'])));
        $this->ensureLoginIsNotThrottled($accountKey, self::ACCOUNT_FAILURE_LIMIT);

        if (! Auth::attempt(['nome' => $credentials['nome'], 'password' => $credentials['password']])) {
            RateLimiter::hit($accountKey, self::LOGIN_WINDOW_SECONDS);

            return back()
                ->withErrors(['nome' => 'Nome de usuário ou senha incorretos.'])
                ->onlyInput('nome');
        }

        // A successful login must not reset the shared IP budget.
        RateLimiter::clear($accountKey);
        $request->session()->regenerate();

        // Seed auth.session immediately, before the first protected request.
        $request->session()->put(
            'password_hash_'.Auth::getDefaultDriver(),
            Auth::guard()->hashPasswordForCookie(Auth::user()->getAuthPassword()),
        );

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function ensureLoginIsNotThrottled(string $key, int $limit): void
    {
        if (RateLimiter::tooManyAttempts($key, $limit)) {
            throw new TooManyRequestsHttpException(
                max(1, RateLimiter::availableIn($key)),
                'Muitas tentativas de login. Aguarde e tente novamente.',
            );
        }
    }
}
