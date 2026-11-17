<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Tests\Feature;

use Atrium\SocialLogin\Guards;
use Atrium\SocialLogin\Tests\TestCase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * A guard whose pages live on the multilingual site gets its routes in
 * every language, but the address registered at the provider stays one.
 * A guard switched off, or without an auth guard behind it, gets none.
 */
final class WiringTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('atrium-social.guards.people.localized', true);
        $app['config']->set('atrium-social.guards.ghost', ['enabled' => true, 'prefix' => 'ghost/social']);
        $app['config']->set('atrium-social.guards.account.enabled', false);
        // A localized page of the site, there before this module's routes.
        $app['router']->get('page/{provider}', fn () => 'page')->name('localized.site.page');
    }

    public function test_a_localized_guard_has_routes_in_every_language_and_one_return_address(): void
    {
        $this->assertTrue(Route::has('atrium-social.people.accounts'));
        $this->assertTrue(Route::has('localized.atrium-social.people.accounts'));
        $this->assertSame('people', Route::getRoutes()->getByName('localized.atrium-social.people.callback')?->defaults['guard'] ?? null);
        // Another module's localized route is left as it was.
        $page = Route::getRoutes()->getByName('localized.site.page');
        $this->assertNotNull($page);
        $this->assertArrayNotHasKey('guard', $page->defaults);
        $this->assertArrayNotHasKey('provider', $page->wheres);
        $this->assertSame(url('people/auth/social/callback/github'), $this->app->make(Guards::class)->callbackUrl('people', 'github'));
        $this->assertStringEndsWith('/people/auth/social/accounts', $this->app->make(Guards::class)->route('people', 'accounts'));

        // In another language its pages are that language's.
        $this->app->setLocale('de');
        $this->assertSame(route('localized.atrium-social.people.accounts', ['locale' => 'de']), $this->app->make(Guards::class)->url('people', 'atrium-social.people.accounts'));
        $this->assertSame(route('admin.social.accounts'), $this->app->make(Guards::class)->route('admin', 'accounts'));
    }

    public function test_only_enabled_guards_with_an_auth_guard_behind_them_count(): void
    {
        $this->assertSame(['admin', 'people'], $this->app->make(Guards::class)->names());
        $this->assertFalse(Route::has('atrium-social.ghost.redirect'));
        $this->assertFalse(Route::has('atrium-social.account.redirect'));
    }

    public function test_only_the_return_address_goes_without_a_token(): void
    {
        $csrf = new class($this->app, $this->app['encrypter']) extends ValidateCsrfToken
        {
            public function skips(string $path): bool
            {
                return $this->inExceptArray(Request::create($path, 'POST'));
            }
        };

        $this->assertTrue($csrf->skips('/admin/auth/social/callback/github/return'));
        $this->assertTrue($csrf->skips('/people/auth/social/callback/apple/return'));
        $this->assertFalse($csrf->skips('/admin/auth/social/providers/github'));
        $this->assertFalse($csrf->skips('/admin/auth/social/accounts/1'));
    }
}
