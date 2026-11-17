<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Tests\Feature;

use Atrium\Admin\Facades\Hook;
use Atrium\SocialLogin\Guards;
use Atrium\SocialLogin\Http\Middleware\ReloadInertiaVisits;
use Atrium\SocialLogin\Providers;
use Atrium\SocialLogin\SocialLogin;
use Atrium\SocialLogin\SocialLoginServiceProvider;
use Atrium\SocialLogin\Tests\Support\Person;
use Atrium\SocialLogin\Tests\TestCase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * The routes of a guard: what a guard gets when its config says nothing,
 * and that what it does say is followed.
 */
final class RoutesTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $config = $app['config'];

        foreach (['members', 'club', 'shop'] as $guard) {
            $config->set("auth.guards.{$guard}", ['driver' => 'session', 'provider' => 'people']);
        }

        $config->set('auth.providers.people', ['driver' => 'eloquent', 'model' => Person::class]);
        // Only switched on: everything else is left to the defaults.
        $config->set('atrium-social.guards.members', ['enabled' => true]);
        $config->set('atrium-social.guards.club', [
            'enabled' => true,
            'prefix' => '/club/social/',
            'middleware' => ['web', 'throttle:60,1'],
            'localized' => false,
            // A list works as well as one name.
            'auth_middleware' => ['auth.club', 'verified'],
        ]);
        // One group by its name, not in a list.
        $config->set('atrium-social.guards.shop', ['enabled' => true, 'middleware' => 'web', 'auth_middleware' => 'auth:shop']);
        // Configured, but its guard does not exist in auth.guards.
        $config->set('atrium-social.guards.ghost', ['enabled' => true]);
    }

    public function test_a_guard_that_says_nothing_gets_its_own_prefix_the_web_group_and_laravels_auth(): void
    {
        $redirect = Route::getRoutes()->getByName('atrium-social.members.redirect');
        $accounts = Route::getRoutes()->getByName('atrium-social.members.accounts');

        $this->assertNotNull($redirect);
        $this->assertSame('members/auth/social/redirect/{provider}', $redirect->uri());
        $this->assertSame(['web', ReloadInertiaVisits::class], $redirect->gatherMiddleware());
        $this->assertSame('members', $redirect->defaults['guard']);
        $this->assertSame('[a-z0-9_-]+', $redirect->wheres['provider']);
        $this->assertSame(['web', ReloadInertiaVisits::class, 'auth:members'], $accounts->gatherMiddleware());
        // Not localized unless asked: no copy of the route per language.
        $this->assertNull(Route::getRoutes()->getByName('localized.atrium-social.members.redirect'));
    }

    public function test_a_guard_that_says_how_gets_its_prefix_trimmed_its_middleware_and_its_auth(): void
    {
        $redirect = Route::getRoutes()->getByName('atrium-social.club.redirect');
        $accounts = Route::getRoutes()->getByName('atrium-social.club.accounts');

        $this->assertSame('club/social/redirect/{provider}', $redirect->uri());
        $this->assertSame(['web', 'throttle:60,1', ReloadInertiaVisits::class], $redirect->gatherMiddleware());
        $this->assertSame(['web', 'throttle:60,1', ReloadInertiaVisits::class, 'auth.club', 'verified'], $accounts->gatherMiddleware());
        $this->assertSame('club', $redirect->defaults['guard']);
    }

    public function test_middleware_named_alone_is_taken_as_a_list_of_one(): void
    {
        $this->assertSame(['web', ReloadInertiaVisits::class], Route::getRoutes()->getByName('atrium-social.shop.redirect')->gatherMiddleware());
        $this->assertSame(['web', ReloadInertiaVisits::class, 'auth:shop'], Route::getRoutes()->getByName('atrium-social.shop.accounts')->gatherMiddleware());
    }

    public function test_every_guard_takes_the_providers_post_outside_its_session_and_only_its_return_skips_the_token(): void
    {
        $bounce = Route::getRoutes()->getByName('atrium-social.members.bounce');

        $this->assertNotNull($bounce);
        $this->assertSame('members/auth/social/callback/{provider}', $bounce->uri());
        $this->assertSame(['POST'], $bounce->methods());
        $this->assertSame([], $bounce->gatherMiddleware());
        $this->assertSame('club/social/callback/{provider}', Route::getRoutes()->getByName('atrium-social.club.bounce')->uri());
        $this->assertSame('admin.social.bounce', Route::getRoutes()->getByName('admin.social.bounce')?->getName());

        $except = $this->app->make(ValidateCsrfToken::class)->getExcludedPaths();
        $this->assertContains('members/auth/social/callback/*/return', $except);
        $this->assertContains('club/social/callback/*/return', $except);
        $this->assertContains('admin/auth/social/callback/*/return', $except);
        $this->assertNotContains('members/auth/social/callback/*', $except);
    }

    public function test_a_guard_that_does_not_exist_gets_neither_routes_nor_buttons(): void
    {
        $this->provider('github', ['ghost']);

        $this->assertNull(Route::getRoutes()->getByName('atrium-social.ghost.redirect'));
        $this->assertSame([['key' => 'x']], Hook::apply('atrium.auth.methods', [['key' => 'x']], 'ghost'));
    }

    public function test_the_services_are_shared_so_what_one_learns_the_next_one_knows(): void
    {
        foreach ([Guards::class, Providers::class, SocialLogin::class] as $service) {
            $this->assertSame($this->app->make($service), $this->app->make($service));
        }
    }

    public function test_the_config_views_and_translations_can_be_published(): void
    {
        $root = realpath(__DIR__.'/../..');
        $paths = fn (string $tag): array => array_map(
            fn (string $from): string => (string) realpath($from),
            array_keys(ServiceProvider::pathsToPublish(SocialLoginServiceProvider::class, $tag)),
        );

        $this->assertSame([$root.'/config/atrium-social.php'], $paths('atrium-social-config'));
        $this->assertSame([config_path('atrium-social.php')], array_values(ServiceProvider::pathsToPublish(SocialLoginServiceProvider::class, 'atrium-social-config')));
        $this->assertSame([$root.'/resources/views'], $paths('atrium-social-views'));
        $this->assertSame([resource_path('views/vendor/atrium-social')], array_values(ServiceProvider::pathsToPublish(SocialLoginServiceProvider::class, 'atrium-social-views')));
        $this->assertSame([$root.'/lang'], $paths('atrium-social-lang'));
        $this->assertSame([$this->app->langPath('vendor/atrium-social')], array_values(ServiceProvider::pathsToPublish(SocialLoginServiceProvider::class, 'atrium-social-lang')));
    }
}
