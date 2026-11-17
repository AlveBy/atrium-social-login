<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => 'Social sign-in',
    'back_to_panel' => 'Back to the panel',

    'guards' => [
        'admin' => 'Admin panel',
        'account' => 'Personal account',
    ],

    'login' => [
        'button' => 'Sign in with :provider',
    ],

    'menu' => [
        'title' => 'Social sign-in',
        'accounts' => 'Linked accounts',
        'providers' => 'Providers',
    ],

    'settings' => [
        'description' => 'Which e-mail domains may open an existing account with an address a provider has verified. The providers are set up under Social sign-in → Providers.',
        'domains' => 'Allowed e-mail domains: :who',
        'domains_hint' => 'One per line or separated by commas, e.g. example.com. Empty: any domain. Nobody is ever created in the admin panel this way.',
    ],

    'accounts' => [
        'title' => 'Linked accounts',
        'hint' => 'Sign in with an account at another service instead of the password. A second sign-in step, if you have one, still applies.',
        'linked_title' => 'Linked',
        'none' => 'No account is linked yet.',
        'available' => 'Link another account',
        'link' => 'Link :provider',
        'unlink' => 'Unlink',
        'linked' => ':provider is linked. You can sign in with it now.',
        'unlinked' => ':provider is unlinked.',
        'last_method' => 'This is your only way to sign in. Set a password or link another account first.',
        'last_used' => 'Last used',
        'never' => 'never',
    ],

    'providers' => [
        'title' => 'Social sign-in providers',
        'hint' => 'Register an application at the provider, enter its client ID and secret here, and switch it on where people should see its button.',
        'provider' => 'Provider',
        'status' => 'Status',
        'on' => 'On',
        'off' => 'Off',
        'not_set_up' => 'Not set up',
        'edit' => 'Set up',
        'save' => 'Save',
        'saved' => ':provider is saved.',
        'enabled_for' => 'Show the button on the login screen of',
        'client_id' => 'Client ID',
        'client_secret' => 'Client secret',
        'secret_kept' => 'A secret is saved. Leave the field empty to keep it.',
        'secret_none' => 'No secret is saved yet.',
        'redirect' => 'Redirect URLs',
        'redirect_hint' => 'Enter these addresses at the provider as the allowed redirect (callback) URLs, exactly as shown.',
        'settings' => [
            'tenant' => 'Tenant',
            'tenant_hint' => 'The directory (tenant) ID or domain of your organisation. Empty: any work or school account.',
            'host' => 'GitLab address',
            'host_hint' => 'For a self-hosted GitLab, e.g. https://gitlab.example.com. Empty: gitlab.com.',
            'issuer' => 'Issuer URL',
            'issuer_hint' => 'The address of the OpenID Connect server; /.well-known/openid-configuration must open under it.',
            'label' => 'Button name',
            'label_hint' => 'What people see on the button, e.g. the name of your company login.',
        ],
        'hints' => [
            'apple' => 'Client ID: the Services ID. Client secret: the signed JWT made from your key; Apple accepts one for at most six months, so renew it in time.',
            'telegram' => 'Client ID: the bot username. Client secret: the bot token. Set the site domain for the bot with /setdomain in BotFather.',
            'entra' => 'Only work and school accounts of the tenant entered below can sign in. Left empty: any work or school account.',
            'microsoft' => 'Personal, work and school Microsoft accounts. Their e-mail addresses are not taken as verified, so an account has to be linked from the profile first.',
            'vkid' => 'Client ID: the app ID. Client secret: the protected key. VK ID does not vouch for e-mail addresses, so an account has to be linked from the profile first.',
            'oidc' => 'Keycloak, Okta, Auth0, Authentik and any other OpenID Connect server.',
        ],
    ],

    'refused' => [
        'state' => 'The sign-in with :provider has expired or was started elsewhere. Please try again.',
        'cancelled' => 'The sign-in with :provider was cancelled.',
        'failed' => ':provider did not confirm the sign-in. Please try again.',
        'no_email' => ':provider did not share an e-mail address, and no account is linked to it. Sign in another way and link it in your profile.',
        'unverified' => ':provider did not confirm the e-mail address, and no account is linked to it. Sign in another way and link it in your profile.',
        'inactive' => 'This account is switched off.',
        'domain' => 'Addresses of this domain cannot sign in with :provider here.',
        'no_account' => 'There is no account for this :provider address.',
        'taken' => 'This :provider account is already linked to someone else.',
    ],

    'bounce' => [
        'title' => 'Signing in…',
        'working' => 'One moment, finishing the sign-in.',
        'continue' => 'Continue',
    ],
];
