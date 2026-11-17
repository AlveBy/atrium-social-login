<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Http\Controllers;

use Atrium\Admin\Auth\Permissions;
use Atrium\Admin\Models\AdminUser;
use Atrium\SocialLogin\Guards;
use Atrium\SocialLogin\Models\SocialProvider;
use Atrium\SocialLogin\Providers;
use Atrium\SocialLogin\Services\Accounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * For whoever may set the module up: each provider switched on per guard,
 * with the client id and secret its owner registered at the provider. The
 * secret is kept encrypted and never shown again; an empty field keeps it.
 */
final class ProvidersController extends Controller
{
    public function __construct(
        Guards $guards,
        Providers $providers,
        private readonly Permissions $permissions,
        private readonly Accounts $accounts,
    ) {
        parent::__construct($guards, $providers);
    }

    public function index(Request $request): Response
    {
        $this->authorize($request);

        return response($this->view($request, 'providers.index', [
            'keys' => $this->providers->keys(),
            'names' => $this->guards->names(),
        ]));
    }

    public function edit(Request $request, string $provider): Response
    {
        $this->authorize($request);
        abort_unless($this->providers->known($provider), 404);

        return response($this->view($request, 'providers.edit', [
            'key' => $provider,
            'model' => $this->providers->model($provider) ?? new SocialProvider,
            'names' => $this->guards->names(),
            'fields' => $this->providers->settingsOf($provider),
        ]));
    }

    public function update(Request $request, string $provider): RedirectResponse
    {
        $admin = $this->authorize($request);
        abort_unless($this->providers->known($provider), 404);
        $fields = $this->providers->settingsOf($provider);

        $data = $request->validate([
            'guards' => ['array'],
            'guards.*' => ['string', Rule::in($this->guards->names())],
            'client_id' => ['nullable', 'string', 'max:500'],
            'client_secret' => ['nullable', 'string', 'max:10000'],
            'settings' => ['array'],
            'settings.tenant' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9.\-]+$/'],
            'settings.host' => ['nullable', 'url:https', 'max:255'],
            'settings.issuer' => ['nullable', 'url:https,http', 'max:255'],
            'settings.label' => ['nullable', 'string', 'max:60'],
        ]);

        $model = $this->providers->model($provider) ?? new SocialProvider(['key' => $provider]);
        $model->guards = array_values(array_unique((array) ($data['guards'] ?? [])));
        $model->client_id = trim((string) ($data['client_id'] ?? ''));
        $model->settings = array_filter(
            array_intersect_key((array) ($data['settings'] ?? []), array_flip($fields)),
            fn ($value): bool => is_string($value) && trim($value) !== '',
        );

        if (trim((string) ($data['client_secret'] ?? '')) !== '') {
            $model->client_secret = trim((string) $data['client_secret']);
        }

        $model->save();
        $this->providers->refresh();
        $this->accounts->log($this->guards->adminGuard(), $admin, 'social_provider_saved', $provider, ['guards' => $model->guards]);

        return redirect()->to($this->guards->route($this->guard($request), 'providers'))
            ->with('atrium-social.status', __('atrium-social::social.providers.saved', ['provider' => $this->providers->label($provider)]));
    }

    private function authorize(Request $request): AdminUser
    {
        $admin = $this->user($request);
        abort_unless($admin instanceof AdminUser && $this->permissions->canSetUp($admin, 'social_login'), 403, __('atrium::permissions.forbidden'));

        return $admin;
    }
}
