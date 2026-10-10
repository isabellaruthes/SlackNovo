<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();

        // Fail before RefreshDatabase can migrate a database outside the test sandbox.
        if ($app['config']->get('database.default') !== 'sqlite'
            || $app['config']->get('database.connections.sqlite.database') !== ':memory:'
            || ! empty($app['config']->get('database.connections.sqlite.url'))
            || $app['config']->get('cache.default') !== 'array'
            || $app['config']->get('session.driver') !== 'array') {
            throw new LogicException('Authentication security tests require SQLite :memory: and array cache/session.');
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false, 'logging.default' => 'null']);
    }

    public function test_account_limit_is_shared_across_ips_and_normalized_names(): void
    {
        $this->createUser();

        foreach (['OPERADOR', ' operador ', 'operador', 'OpErAdOr', 'OPERADOR '] as $index => $name) {
            $this->failedLogin($name, '192.0.2.'.($index + 1));
        }

        $response = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.1'])
            ->postJson('/login', ['nome' => 'operador', 'password' => 'correct-password']);

        $this->assertThrottled($response);
        $this->assertGuest();
    }

    public function test_ip_limit_is_shared_across_accounts_and_expires(): void
    {
        $user = $this->createUser();

        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $this->failedLogin('unknown-'.$attempt);
        }

        $this->assertThrottled($this->postJson('/login', [
            'nome' => $user->nome,
            'password' => 'correct-password',
        ]));
        $this->assertGuest();

        // A separate origin still has its own budget.
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.2'])
            ->post('/login', ['nome' => $user->nome, 'password' => 'correct-password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/');

        $this->travel(61)->seconds();

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1'])
            ->post('/login', ['nome' => $user->nome, 'password' => 'correct-password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_account_limit_expires_and_retry_after_decreases(): void
    {
        $user = $this->createUser();
        $this->freezeTime();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->failedLogin();
        }

        $credentials = ['nome' => $user->nome, 'password' => 'correct-password'];
        $this->assertThrottled($this->postJson('/login', $credentials));

        $this->travel(30)->seconds();

        $this->postJson('/login', $credentials)->assertStatus(429)->assertHeader('Retry-After', '30');

        $this->travel(31)->seconds();

        $this->post('/login', $credentials)->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_success_clears_account_failures_but_preserves_ip_attempts(): void
    {
        $user = $this->createUser();

        for ($attempt = 1; $attempt <= 21; $attempt++) {
            $this->failedLogin('unknown-'.$attempt);
        }

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->failedLogin();
        }

        $this->post('/login', ['nome' => $user->nome, 'password' => 'correct-password'])
            ->assertRedirect('/dashboard');
        $this->post('/logout')->assertRedirect('/');

        // These four failures are possible only if the successful login cleared the account counter.
        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->failedLogin();
        }

        // The account has four failures, but this origin has already made 30 attempts.
        $this->assertThrottled($this->postJson('/login', [
            'nome' => $user->nome,
            'password' => 'correct-password',
        ]));
        $this->assertGuest();
    }

    public function test_existing_and_unknown_accounts_receive_the_same_generic_errors(): void
    {
        $this->createUser();
        $responses = [];

        foreach (['operador', 'unknown-account'] as $name) {
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $this->failedLogin($name);
            }

            $response = $this->postJson('/login', ['nome' => $name, 'password' => 'wrong-password']);
            $this->assertThrottled($response);
            $responses[] = $response->json();
        }

        $this->assertSame($responses[0], $responses[1]);
    }

    public function test_invalid_credential_types_and_lengths_are_rejected(): void
    {
        foreach ([
            [['nome' => str_repeat('n', 51), 'password' => 'password'], 'nome'],
            [['nome' => ['operador'], 'password' => 'password'], 'nome'],
            [['nome' => 'operador', 'password' => str_repeat('p', 1025)], 'password'],
            [['nome' => 'operador', 'password' => ['password']], 'password'],
        ] as [$credentials, $field]) {
            $this->from('/login')->post('/login', $credentials)
                ->assertRedirect('/login')->assertSessionHasErrors($field);
            $this->assertGuest();
            $this->get('/login')->assertOk();
        }
    }

    public function test_malformed_requests_also_consume_the_ip_budget(): void
    {
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->post('/login', [])->assertRedirect()->assertSessionHasErrors(['nome', 'password']);
        }

        $this->assertThrottled($this->postJson('/login', ['nome' => 'unknown', 'password' => 'wrong-password']));
        $this->assertGuest();
    }

    public function test_all_private_endpoints_reject_anonymous_requests(): void
    {
        $token = $this->enableCsrf();
        $requests = 0;

        foreach ($this->app['router']->getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'admin/')
                && ! in_array($route->uri(), ['dashboard', 'logout'], true)) {
                continue;
            }

            $uri = '/'.preg_replace('/\{[^}]+\}/', '1', $route->uri());

            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $this->withSession(['_token' => $token])->call($method, $uri, ['_token' => $token])
                    ->assertRedirect('/login');
                $requests++;
            }
        }

        $this->assertGreaterThan(0, $requests);
    }

    public function test_mutating_endpoints_reject_missing_and_invalid_csrf_tokens(): void
    {
        $token = $this->enableCsrf();
        $user = $this->createUser();
        $this->actingAs($user);
        $requests = 0;

        foreach ($this->app['router']->getRoutes() as $route) {
            if (! in_array('web', $route->gatherMiddleware(), true)) {
                continue;
            }

            $uri = '/'.preg_replace('/\{[^}]+\}/', '1', $route->uri());

            foreach (array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) as $method) {
                $this->withSession(['_token' => $token])->call($method, $uri)->assertStatus(419);
                $requests++;
            }
        }

        $this->assertGreaterThan(0, $requests);
        $this->withSession(['_token' => $token])->post('/logout', ['_token' => 'invalid-token'])
            ->assertStatus(419);
        $this->assertAuthenticatedAs($user);

        $this->withSession(['_token' => $token])->post('/logout', ['_token' => $token])
            ->assertRedirect('/');
        $this->assertGuest();

        $this->withSession(['_token' => $token])->post('/login', [
            '_token' => $token,
            'nome' => $user->nome,
            'password' => 'correct-password',
        ])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rotates_session_and_logout_invalidates_it(): void
    {
        $user = $this->createUser();
        $this->withSession(['_token' => 'previous-csrf-token', 'sentinel' => 'previous-session-data']);
        $previousId = session()->getId();
        $previousToken = session()->token();
        $this->withCookie(session()->getName(), $previousId);

        $this->post('/login', ['nome' => $user->nome, 'password' => 'correct-password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($previousId, session()->getId());
        $this->assertNotSame($previousToken, session()->token());

        $authenticatedId = session()->getId();
        $authenticatedToken = session()->token();
        $this->withCookie(session()->getName(), $authenticatedId);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
        $this->assertNotSame($authenticatedId, session()->getId());
        $this->assertNotSame($authenticatedToken, session()->token());
        $this->assertFalse(session()->has('sentinel'));

        // Replaying the former authenticated cookie must not restore access.
        Auth::forgetGuards();
        $this->withCookie(session()->getName(), $authenticatedId)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    #[DataProvider('passwordChangeScenarios')]
    public function test_password_change_revokes_a_previously_authenticated_session(bool $visitProtectedRoute): void
    {
        $user = $this->createUser();

        $this->post('/login', ['nome' => $user->nome, 'password' => 'correct-password'])
            ->assertRedirect('/dashboard');
        $this->withCookie(session()->getName(), session()->getId());

        if ($visitProtectedRoute) {
            $this->get('/dashboard')->assertOk();
        }

        $user->forceFill(['senha' => Hash::make('replacement-password')])->save();
        Auth::forgetGuards();

        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public static function passwordChangeScenarios(): array
    {
        return [
            'immediately after login' => [false],
            'after a protected request' => [true],
        ];
    }

    private function createUser(): User
    {
        return User::create(['nome' => 'operador', 'senha' => Hash::make('correct-password')]);
    }

    private function failedLogin(string $name = 'operador', string $ip = '192.0.2.1'): TestResponse
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])->from('/login')->post('/login', [
            'nome' => $name,
            'password' => 'wrong-password',
        ]);
        $response->assertRedirect('/login')
            ->assertSessionHasErrors(['nome' => 'Nome de usuário ou senha incorretos.']);
        $this->assertGuest();

        return $response;
    }

    private function assertThrottled(TestResponse $response): void
    {
        $response->assertStatus(429)->assertHeader('Retry-After')
            ->assertExactJson(['message' => 'Muitas tentativas de login. Aguarde e tente novamente.']);
        $seconds = (int) $response->headers->get('Retry-After');
        $this->assertGreaterThanOrEqual(1, $seconds);
        $this->assertLessThanOrEqual(60, $seconds);
    }

    private function enableCsrf(): string
    {
        // The in-memory migrations have already run; only the HTTP test shortcut is disabled.
        $this->app->instance('env', 'local');
        $this->assertFalse($this->app->runningUnitTests());

        return str_repeat('a', 40);
    }
}
