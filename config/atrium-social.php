<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Where people sign in with another account
    |--------------------------------------------------------------------------
    |
    | One entry per auth guard. "allowed_domains" (panel only): an e-mail
    | found at the provider opens an existing administrator's account only
    | when its domain is listed; empty = any domain. Nobody is ever created
    | in the panel. "register": the personal account may create a person
    | from a verified e-mail, when its owner registers a way to do so
    | (SocialLogin::registerUsing()).
    |
    */
    'guards' => [
        'admin' => [
            'enabled' => true,
            'prefix' => 'admin/auth/social',
            'middleware' => ['web', 'atrium.site', 'atrium.inertia'],
            'localized' => false,
            'auth_middleware' => 'admin.auth',
            'login_route' => 'admin.login',
            'active_column' => 'is_active',
            'allowed_domains' => [],
            'register' => false,
        ],
        'account' => [
            'enabled' => true,
            'prefix' => 'account/auth/social',
            'middleware' => ['web'],
            'localized' => true,
            'auth_middleware' => 'account.auth',
            'layout' => 'atrium-social::layouts.site',
            'login_route' => 'atrium.account.login',
            'active_column' => 'active',
            'allowed_domains' => [],
            'register' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | The providers
    |--------------------------------------------------------------------------
    |
    | Each is switched on, per guard, in the panel (Social sign-in), where its
    | client id and secret are entered; the secret is kept encrypted. More
    | OpenID Connect servers are added here with "driver" => "oidc".
    | "settings": the extra fields the provider asks for.
    |
    */
    'providers' => [
        'google' => ['driver' => 'google', 'label' => 'Google'],
        'microsoft' => ['driver' => 'microsoft', 'label' => 'Microsoft'],
        'entra' => ['driver' => 'entra', 'label' => 'Microsoft Entra ID', 'settings' => ['tenant']],
        'yandex' => ['driver' => 'yandex', 'label' => 'Yandex ID'],
        'apple' => ['driver' => 'apple', 'label' => 'Apple'],
        'github' => ['driver' => 'github', 'label' => 'GitHub'],
        'gitlab' => ['driver' => 'gitlab', 'label' => 'GitLab', 'settings' => ['host']],
        'facebook' => ['driver' => 'facebook', 'label' => 'Facebook'],
        'vkid' => ['driver' => 'vkid', 'label' => 'VK ID'],
        'mailru' => ['driver' => 'mailru', 'label' => 'Mail.ru'],
        'telegram' => ['driver' => 'telegram', 'label' => 'Telegram'],
        'linkedin' => ['driver' => 'linkedin', 'label' => 'LinkedIn'],
        'oidc' => ['driver' => 'oidc', 'label' => 'OpenID Connect', 'settings' => ['issuer', 'label']],
    ],

    // How long a started sign-in may take at the provider, in minutes.
    'state_ttl' => 10,
];
