<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin;

use Atrium\SocialLogin\Drivers\OidcProvider;
use Atrium\SocialLogin\Drivers\Telegram;
use Atrium\SocialLogin\Drivers\VkIdProvider;
use Atrium\SocialLogin\Models\SocialProvider;
use GuzzleHttp\Client;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\FacebookProvider;
use Laravel\Socialite\Two\GithubProvider;
use Laravel\Socialite\Two\GitlabProvider;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\LinkedInOpenIdProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\Manager\OAuth2\AbstractProvider as ProvidersAbstract;
use SocialiteProviders\Microsoft\Provider;

/**
 * The providers of config/atrium-social.php, with what the panel says
 * about each: where it is on, and the app registered at the provider.
 */
final class Providers
{
    /** The scopes asked for: an id, a name and the e-mail, nothing more. */
    private const SCOPES = [
        'google' => ['openid', 'email', 'profile'],
        'github' => ['read:user', 'user:email'],
        'gitlab' => ['read_user'],
        'facebook' => ['email'],
        'linkedin' => ['openid', 'profile', 'email'],
        'microsoft' => ['openid', 'email', 'profile', 'User.Read'],
        'entra' => ['openid', 'email', 'profile', 'User.Read'],
        'yandex' => ['login:email', 'login:info'],
        'mailru' => ['userinfo'],
        'apple' => ['name', 'email'],
        'vkid' => ['email'],
        'oidc' => ['openid', 'email', 'profile'],
    ];

    /** @var array<string,SocialProvider>|null */
    private ?array $models = null;

    private ?Client $http = null;

    /**
     * The keys of the providers config/atrium-social.php knows.
     *
     * @return list<string>
     */
    public function keys(): array
    {
        return array_map('strval', array_keys((array) config('atrium-social.providers', [])));
    }

    public function known(string $key): bool
    {
        return in_array($key, $this->keys(), true);
    }

    public function driverOf(string $key): string
    {
        return (string) config("atrium-social.providers.{$key}.driver", $key);
    }

    /**
     * @return list<string>
     */
    public function settingsOf(string $key): array
    {
        return array_values(array_map('strval', (array) config("atrium-social.providers.{$key}.settings", [])));
    }

    /**
     * The name on the button: an OpenID Connect server may be given its own.
     */
    public function label(string $key): string
    {
        $own = $this->model($key)?->setting('label') ?? '';

        return $own !== '' ? $own : (string) config("atrium-social.providers.{$key}.label", ucfirst($key));
    }

    public function model(string $key): ?SocialProvider
    {
        if ($this->models === null) {
            $this->models = SocialProvider::query()->get()->keyBy('key')->all();
        }

        return $this->models[$key] ?? null;
    }

    /**
     * Forget what was read, after the panel saved a provider.
     */
    public function refresh(): void
    {
        $this->models = null;
    }

    /**
     * On for the guard and set up: a client id and a secret entered.
     */
    public function enabled(string $key, string $guard): bool
    {
        $model = $this->known($key) ? $this->model($key) : null;

        return $model !== null
            && $model->enabledFor($guard)
            && trim((string) $model->client_id) !== ''
            && (string) $model->client_secret !== '';
    }

    /**
     * @return list<string>
     */
    public function enabledFor(string $guard): array
    {
        return array_values(array_filter($this->keys(), fn (string $key): bool => $this->enabled($key, $guard)));
    }

    /**
     * The tests answer for the providers.
     */
    public function useHttpClient(?Client $client): void
    {
        $this->http = $client;
    }

    /**
     * The Socialite provider of a key, ready to redirect or read the user.
     */
    public function make(string $key, string $redirect): AbstractProvider
    {
        $model = $this->model($key);
        $id = trim((string) $model?->client_id);
        $secret = (string) $model?->client_secret;
        $driver = $this->driverOf($key);

        $class = match ($driver) {
            'google' => GoogleProvider::class,
            'github' => GithubProvider::class,
            'gitlab' => GitlabProvider::class,
            'facebook' => FacebookProvider::class,
            'linkedin' => LinkedInOpenIdProvider::class,
            'microsoft', 'entra' => Provider::class,
            'yandex' => \SocialiteProviders\Yandex\Provider::class,
            'mailru' => \SocialiteProviders\Mailru\Provider::class,
            'apple' => \SocialiteProviders\Apple\Provider::class,
            'vkid' => VkIdProvider::class,
            'oidc' => OidcProvider::class,
            default => throw new \InvalidArgumentException("No OAuth driver [{$driver}]."),
        };

        $provider = new $class(app('request'), $id, $secret, $redirect);

        if ($provider instanceof ProvidersAbstract) {
            $provider->setConfig(new Config($id, $secret, $redirect, match ($driver) {
                // One organisation only, or any; Microsoft's own tenant
                // ("common") takes personal and work accounts alike.
                'entra' => ['tenant' => $model?->setting('tenant') ?: 'organizations'],
                'oidc' => ['issuer' => $model?->setting('issuer')],
                default => [],
            }));
        }

        if ($provider instanceof GitlabProvider) {
            // gitlab.com when no host is set; a trailing slash is dropped.
            $provider->setHost($model?->setting('host'));
        }

        if ($this->http !== null) {
            $provider->setHttpClient($this->http);
        }

        return $provider->scopes(self::SCOPES[$driver])->stateless();
    }

    /**
     * Telegram's sign-in, checked with the bot token entered as the secret.
     */
    public function telegram(string $key): Telegram
    {
        return new Telegram((string) $this->model($key)?->client_secret);
    }

    /**
     * Whether the provider vouches that the address belongs to the person.
     * Only such an address may find an existing account; Microsoft and
     * Entra ID let the owner of a tenant write any address, VK ID and
     * Telegram give none that is checked.
     */
    public function verifiedEmail(string $key, SocialiteUser $user): bool
    {
        $raw = $user->getRaw();

        if ((string) $user->getEmail() === '') {
            return false;
        }

        return match ($this->driverOf($key)) {
            'google', 'linkedin', 'apple', 'oidc' => in_array($raw['email_verified'] ?? null, [true, 'true', 1, '1'], true),
            // Socialite takes the primary address GitHub has verified, or none.
            'github' => true,
            'gitlab' => ! empty($raw['confirmed_at']),
            // Facebook gives only confirmed addresses.
            'facebook' => true,
            // A mailbox of the provider itself.
            'yandex' => (bool) preg_match('/@(yandex\.(ru|com|by|kz|ua|com\.tr)|ya\.ru|narod\.ru)$/i', (string) $user->getEmail()),
            'mailru' => (bool) preg_match('/@(mail\.ru|inbox\.ru|list\.ru|bk\.ru|internet\.ru)$/i', (string) $user->getEmail()),
            default => false,
        };
    }
}
