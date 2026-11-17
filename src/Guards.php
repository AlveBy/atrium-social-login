<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin;

use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Support\ContentLanguages;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

/**
 * The guards people sign in to with another account, as config/atrium-social.php describes them.
 */
final class Guards
{
    /**
     * @return list<string>
     */
    public function names(): array
    {
        $names = [];

        foreach ((array) config('atrium-social.guards', []) as $name => $guard) {
            if (($guard['enabled'] ?? false) && config("auth.guards.{$name}") !== null) {
                $names[] = (string) $name;
            }
        }

        return $names;
    }

    public function has(string $guard): bool
    {
        return in_array($guard, $this->names(), true);
    }

    public function config(string $guard, string $key, mixed $default = null): mixed
    {
        return config("atrium-social.guards.{$guard}.{$key}", $default);
    }

    public function adminGuard(): string
    {
        return (string) config('atrium.auth.guard', 'admin');
    }

    public function routeName(string $guard, string $name): string
    {
        return ($guard === $this->adminGuard() ? 'admin.social.' : "atrium-social.{$guard}.").$name;
    }

    /**
     * @param  array<string,mixed>  $parameters
     */
    public function route(string $guard, string $name, array $parameters = []): string
    {
        return $this->url($guard, $this->routeName($guard, $name), $parameters);
    }

    /**
     * @param  array<string,mixed>  $parameters
     */
    public function url(string $guard, string $name, array $parameters = []): string
    {
        return $this->config($guard, 'localized', false)
            ? app(ContentLanguages::class)->route($name, $parameters)
            : route($name, $parameters);
    }

    /**
     * Where a provider sends people back: one fixed address per guard and
     * provider, the one to register at the provider.
     */
    public function callbackUrl(string $guard, string $provider): string
    {
        // Without a language prefix: the provider wants one exact address.
        return route($this->routeName($guard, 'callback'), ['provider' => $provider]);
    }

    /**
     * Where a provider's answer is posted on from this site (see
     * SocialController::bounce()).
     */
    public function returnUrl(string $guard, string $provider): string
    {
        return route($this->routeName($guard, 'return'), ['provider' => $provider]);
    }

    public function loginUrl(string $guard): string
    {
        return $this->url($guard, (string) $this->config($guard, 'login_route'));
    }

    /**
     * The e-mail domains whose verified addresses may open an existing
     * account of the guard; empty means any. The panel's setting wins.
     *
     * @return list<string>
     */
    public function allowedDomains(string $guard): array
    {
        $setting = Setting::get("social_login.{$guard}_domains");
        $domains = is_string($setting) && trim($setting) !== ''
            ? preg_split('/[\s,;]+/', $setting) ?: []
            : (array) $this->config($guard, 'allowed_domains', []);

        return array_values(array_unique(array_filter(array_map(
            fn ($domain): string => mb_strtolower(trim((string) $domain, " \t\n\r\0\x0B@")),
            $domains,
        ))));
    }

    public function domainAllowed(string $guard, string $email): bool
    {
        $domains = $this->allowedDomains($guard);

        return $domains === [] || in_array(mb_strtolower((string) substr(strrchr($email, '@') ?: '', 1)), $domains, true);
    }

    /**
     * A person of the guard by id, active ones only.
     */
    public function find(string $guard, int|string $id): ?Authenticatable
    {
        $user = Auth::createUserProvider(config("auth.guards.{$guard}.provider"))?->retrieveById($id);

        return $user !== null && $this->active($guard, $user) ? $user : null;
    }

    /**
     * A person of the guard by e-mail, whether active or not: an inactive
     * one is refused, not taken for a stranger.
     */
    public function findByEmail(string $guard, string $email): ?Authenticatable
    {
        return Auth::createUserProvider(config("auth.guards.{$guard}.provider"))
            ?->retrieveByCredentials(['email' => mb_strtolower(trim($email))]);
    }

    public function active(string $guard, Authenticatable $user): bool
    {
        $column = $this->config($guard, 'active_column');

        return $column === null || data_get($user, (string) $column);
    }
}
