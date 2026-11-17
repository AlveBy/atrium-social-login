<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Tests;

use Atrium\Admin\AtriumServiceProvider;
use Atrium\Admin\Auth\SignIn;
use Atrium\Admin\Models\AdminUser;
use Atrium\SocialLogin\Models\SocialAccount;
use Atrium\SocialLogin\Models\SocialProvider;
use Atrium\SocialLogin\Providers;
use Atrium\SocialLogin\SocialLoginServiceProvider;
use Atrium\SocialLogin\Tests\Support\Person;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Psr\Http\Message\RequestInterface;
use Tighten\Ziggy\ZiggyServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /** What the providers are asked for and what they answer, in order. */
    protected MockHandler $http;

    /** @var list<array{request:RequestInterface}> */
    protected array $sent = [];

    protected function getPackageProviders($app): array
    {
        return [
            InertiaServiceProvider::class,
            ZiggyServiceProvider::class,
            AtriumServiceProvider::class,
            SocialLoginServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $config = $app['config'];
        $config->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $config->set('app.name', 'Testing site');
        $target = env('DB_CONNECTION');
        $config->set('database.default', in_array($target, ['mysql', 'pgsql'], true) ? $target : 'testing');
        $config->set('atrium.demo', false);
        $config->set('atrium.modules', ['alveby/atrium-social-login']);
        $config->set('atrium.auth.two_factor.enabled', false);
        $config->set('atrium.dashboard.timings.enabled', false);

        // A second guard, like the personal account's.
        $config->set('auth.guards.people', ['driver' => 'session', 'provider' => 'people']);
        $config->set('auth.providers.people', ['driver' => 'eloquent', 'model' => Person::class]);
        $config->set('atrium-social.guards.account.enabled', false);
        $config->set('atrium-social.guards.people', [
            'enabled' => true,
            'prefix' => 'people/auth/social',
            'middleware' => ['web'],
            'localized' => false,
            'auth_middleware' => 'auth:people',
            'layout' => 'atrium-social::layouts.admin',
            'login_route' => 'people.login',
            'active_column' => 'active',
            'allowed_domains' => [],
            'register' => true,
        ]);
    }

    protected function defineRoutes($router): void
    {
        $router->middleware('web')->get('people/login', fn () => 'people login')->name('people.login');
        // Where Laravel's auth middleware sends a guest of a plain guard.
        $router->middleware('web')->get('login', fn () => 'login')->name('login');
        $router->middleware(['web', 'auth:people'])->get('people/home', fn () => 'people home')->name('people.home');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->http = new MockHandler;
        $stack = HandlerStack::create($this->http);
        $stack->push(Middleware::history($this->sent));
        $this->app->make(Providers::class)->useHttpClient(new Client(['handler' => $stack]));

        $this->app->make(SignIn::class)->finishUsing('people', function ($request, $user, bool $remember) {
            Auth::guard('people')->login($user, $remember);
            $request->session()->regenerate();

            return redirect()->intended(route('people.home'));
        });
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/database');
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    protected function admin(array $attributes = []): AdminUser
    {
        $admin = AdminUser::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret-password',
            'is_active' => true,
            ...$attributes,
        ]);

        $admin->forceFill(['is_superadmin' => true])->save();

        return $admin;
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    protected function person(array $attributes = []): Person
    {
        return Person::query()->create([
            'name' => 'Olga',
            'email' => 'olga@example.org',
            'password' => 'person-password',
            ...$attributes,
        ]);
    }

    /**
     * A provider switched on, with a made-up client.
     *
     * @param  list<string>  $guards
     * @param  array<string,string>  $settings
     */
    protected function provider(string $key = 'github', array $guards = ['admin', 'people'], array $settings = [], string $secret = 'test-secret'): SocialProvider
    {
        $provider = SocialProvider::query()->create([
            'key' => $key,
            'guards' => $guards,
            'client_id' => 'test-client',
            'client_secret' => $secret,
            'settings' => $settings,
        ]);
        $this->app->make(Providers::class)->refresh();

        return $provider;
    }

    protected function link(Authenticatable $user, string $guard = 'admin', string $provider = 'github', string $id = '1001'): SocialAccount
    {
        return SocialAccount::query()->create([
            'guard' => $guard,
            'user_id' => $user->getAuthIdentifier(),
            'provider' => $provider,
            'provider_user_id' => $id,
        ]);
    }

    /**
     * Off to the provider: the state it was given.
     */
    protected function startAt(string $url): string
    {
        $location = (string) $this->get($url)->assertRedirect()->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertIsString($query['state'] ?? null, 'the provider gets a state');

        return $query['state'];
    }

    /**
     * GitHub's answers: a token, the profile, the addresses.
     *
     * @param  list<array{email:string,primary:bool,verified:bool}>  $emails
     */
    protected function github(int $id = 1001, array $emails = [['email' => 'admin@example.com', 'primary' => true, 'verified' => true]], string $login = 'octo'): void
    {
        $this->http->append(
            new Response(200, ['Content-Type' => 'application/json'], (string) json_encode(['access_token' => 'token-1', 'token_type' => 'bearer'])),
            new Response(200, ['Content-Type' => 'application/json'], (string) json_encode(['id' => $id, 'node_id' => 'N'.$id, 'login' => $login, 'name' => 'Octo Cat', 'email' => null, 'avatar_url' => null])),
            new Response(200, ['Content-Type' => 'application/json'], (string) json_encode($emails)),
        );
    }

    protected function back(string $guardRoute, string $state, string $provider = 'github', array $extra = []): TestResponse
    {
        return $this->get(route($guardRoute.'callback', ['provider' => $provider, 'code' => 'code-1', 'state' => $state, ...$extra]));
    }
}
