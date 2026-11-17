<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Drivers;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\User;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

/**
 * VK ID (id.vk.com): OAuth 2.1 with PKCE. The community provider needs
 * PHP 8.4; this one is the few calls VK ID's documentation lists.
 */
final class VkIdProvider extends AbstractProvider
{
    public const IDENTIFIER = 'VKID';

    protected $usesPKCE = true;

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://id.vk.com/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://id.vk.com/oauth2/auth';
    }

    /**
     * VK ID wants back the device and the state it sent with the code.
     *
     * @return array<string,mixed>
     */
    protected function getTokenFields($code): array
    {
        return array_merge(parent::getTokenFields($code), [
            'device_id' => (string) $this->request->input('device_id', ''),
            // Checked by SocialController before the code is exchanged.
            'state' => $this->request->input('state'),
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->post('https://id.vk.com/oauth2/user_info', [
            RequestOptions::FORM_PARAMS => ['access_token' => $token, 'client_id' => $this->clientId],
        ]);

        return (array) (json_decode((string) $response->getBody(), true)['user'] ?? []);
    }

    /**
     * @param  array<string,mixed>  $user
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id' => (string) Arr::get($user, 'user_id'),
            'name' => trim(Arr::get($user, 'first_name', '').' '.Arr::get($user, 'last_name', '')),
            'email' => Arr::get($user, 'email'),
            'avatar' => Arr::get($user, 'avatar'),
        ]);
    }
}
