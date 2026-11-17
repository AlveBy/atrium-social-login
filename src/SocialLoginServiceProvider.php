<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin;

use Atrium\Admin\Auth\Permissions;
use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Models\AdminUser;
use Atrium\Admin\Modules\Modules;
use Atrium\SocialLogin\Http\Controllers\SocialController;
use Atrium\SocialLogin\Http\Middleware\ReloadInertiaVisits;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class SocialLoginServiceProvider extends ServiceProvider
{
    public const PACKAGE = 'alveby/atrium-social-login';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/atrium-social.php', 'atrium-social');

        $this->app->singleton(Guards::class);
        $this->app->singleton(Providers::class);
        $this->app->singleton(SocialLogin::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'atrium-social');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'atrium-social');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/atrium-social.php' => config_path('atrium-social.php')], 'atrium-social-config');
            $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/atrium-social')], 'atrium-social-views');
            $this->publishes([__DIR__.'/../lang' => $this->app->langPath('vendor/atrium-social')], 'atrium-social-lang');
        }

        // Installed with `atrium:module:install alveby/atrium-social-login`:
        // its tables exist from then on, and only then does it offer a way in.
        if (! $this->app->make(Modules::class)->isInstalled(self::PACKAGE)) {
            return;
        }

        if (! $this->app->routesAreCached()) {
            $this->registerRoutes();
        }

        $this->registerHooks();
    }

    private function registerRoutes(): void
    {
        $guards = $this->app->make(Guards::class);
        $except = [];

        foreach ($guards->names() as $guard) {
            $isAdmin = $guard === $guards->adminGuard();
            $authMiddleware = $guards->config($guard, 'auth_middleware', 'auth:'.$guard);
            $prefix = trim((string) $guards->config($guard, 'prefix', $guard.'/auth/social'), '/');
            $name = $guards->routeName($guard, '');
            $group = function () use ($prefix, $name, $guard, $guards, $isAdmin, $authMiddleware): void {
                Route::prefix($prefix)
                    ->middleware([...(array) $guards->config($guard, 'middleware', ['web']), ReloadInertiaVisits::class])
                    ->name($name)
                    ->group(function () use ($isAdmin, $authMiddleware): void {
                        require __DIR__.'/../routes/web.php';
                    });
            };

            if ($guards->config($guard, 'localized', false)) {
                Route::localized($group);
            } else {
                $group();
            }

            // A provider's POST from its own site: no session here, or a
            // fresh one would replace the person's (see SocialController).
            Route::post($prefix.'/callback/{provider}', [SocialController::class, 'bounce'])->name($name.'bounce');

            // Posted on by this package's own page, without a token: the
            // state it carries is what proves the request.
            $except[] = $prefix.'/callback/*/return';

            foreach (Route::getRoutes()->getRoutes() as $route) {
                $routeName = (string) $route->getName();

                if (str_starts_with($routeName, $name) || str_starts_with($routeName, 'localized.'.$name)) {
                    $route->defaults('guard', $guard)->where('provider', '[a-z0-9_-]+');
                }
            }
        }

        ValidateCsrfToken::except($except);
    }

    private function registerHooks(): void
    {
        // A button on the login screen for every provider switched on.
        Hook::filter('atrium.auth.methods', function (array $methods, string $guard): array {
            $guards = $this->app->make(Guards::class);
            $providers = $this->app->make(Providers::class);

            if (! $guards->has($guard)) {
                return $methods;
            }

            foreach ($providers->enabledFor($guard) as $key) {
                $methods[] = [
                    'key' => 'social-'.$key,
                    'label' => __('atrium-social::social.login.button', ['provider' => $providers->label($key)]),
                    'url' => $guards->route($guard, 'redirect', ['provider' => $key]),
                    'kind' => 'link',
                ];
            }

            return $methods;
        });

        Hook::filter('atrium.admin.menu', function (array $menu, ?AdminUser $user = null): array {
            $guards = $this->app->make(Guards::class);

            if ($user === null || ! $guards->has($guards->adminGuard())) {
                return $menu;
            }

            $children = [['label' => 'atrium-social::social.menu.accounts', 'route' => 'admin.social.accounts', 'icon' => 'globe']];

            if ($this->app->make(Permissions::class)->canSetUp($user, 'social_login')) {
                $children[] = ['label' => 'atrium-social::social.menu.providers', 'route' => 'admin.social.providers', 'icon' => 'settings'];
            }

            $menu[] = ['label' => 'atrium-social::social.menu.title', 'icon' => 'globe', 'children' => $children];

            return $menu;
        });
    }
}
