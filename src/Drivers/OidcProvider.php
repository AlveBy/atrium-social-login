<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Drivers;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Two\User;
use RuntimeException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

/**
 * Any OpenID Connect server (Keycloak, Okta, Auth0, Authentik, Zitadel…):
 * the endpoints come from its discovery document, the person from its
 * userinfo endpoint.
 */
final class OidcProvider extends AbstractProvider
{
    public const IDENTIFIER = 'OIDC';

    protected $usesPKCE = true;

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->endpoint('authorization_endpoint'), $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->endpoint('token_endpoint');
    }

    /**
     * @return array<string,mixed>
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get($this->endpoint('userinfo_endpoint'), [
            RequestOptions::HEADERS => ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'],
        ]);

        return (array) json_decode((string) $response->getBody(), true);
    }

    /**
     * @param  array<string,mixed>  $user
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id' => (string) Arr::get($user, 'sub'),
            'nickname' => Arr::get($user, 'preferred_username'),
            'name' => Arr::get($user, 'name') ?? trim(Arr::get($user, 'given_name', '').' '.Arr::get($user, 'family_name', '')),
            'email' => Arr::get($user, 'email'),
            'avatar' => Arr::get($user, 'picture'),
        ]);
    }

    private function endpoint(string $name): string
    {
        $issuer = rtrim((string) $this->getConfig('issuer', ''), '/');

        if (! str_starts_with($issuer, 'https://') && ! str_starts_with($issuer, 'http://localhost')) {
            throw new RuntimeException('The OpenID Connect issuer must be an https:// address.');
        }

        $document = Cache::remember('atrium-social.oidc.'.sha1($issuer), 3600, function () use ($issuer): array {
            $response = $this->getHttpClient()->get($issuer.'/.well-known/openid-configuration');

            return (array) json_decode((string) $response->getBody(), true);
        });

        $url = (string) ($document[$name] ?? '');

        if (! str_starts_with($url, 'https://') && ! str_starts_with($url, 'http://localhost')) {
            throw new RuntimeException("The OpenID Connect server lists no {$name}.");
        }

        return $url;
    }
}
