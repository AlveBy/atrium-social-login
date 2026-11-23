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
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

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

    public function index(Request $request): InertiaResponse
    {
        $this->authorize($request);

        $names = $this->guards->names();
        $guard = $this->guard($request);

        return Inertia::render('Admin/SocialLogin/Providers', [
            't' => trans('atrium-social::social'),
            'status' => $request->session()->get('atrium-social.status'),
            'providers' => collect($this->providers->keys())->map(function (string $key) use ($names, $guard): array {
                $on = array_values(array_filter($names, fn (string $name): bool => $this->providers->enabled($key, $name)));

                return [
                    'key' => $key,
                    'label' => $this->providers->label($key),
                    'state' => $on !== [] ? 'on' : ($this->providers->model($key)?->client_id ? 'off' : 'not_set_up'),
                    'guards' => array_map(fn (string $name): string => __('atrium-social::social.guards.'.$name), $on),
                    'edit_url' => $this->guards->route($guard, 'providers.edit', ['provider' => $key]),
                ];
            })->values(),
        ]);
    }

    public function edit(Request $request, string $provider): InertiaResponse
    {
        $this->authorize($request);
        abort_unless($this->providers->known($provider), 404);

        $guard = $this->guard($request);
        $model = $this->providers->model($provider) ?? new SocialProvider;
        $fields = $this->providers->settingsOf($provider);
        $names = $this->guards->names();
        $hint = 'atrium-social::social.providers.hints.'.$provider;

        return Inertia::render('Admin/SocialLogin/Provider', [
            't' => trans('atrium-social::social'),
            'status' => $request->session()->get('atrium-social.status'),
            'provider' => [
                'key' => $provider,
                'label' => $this->providers->label($provider),
                'hint' => __($hint) === $hint ? null : __($hint),
                'client_id' => (string) $model->client_id,
                'has_secret' => $model->exists && (string) $model->client_secret !== '',
                'guards' => array_values(array_filter($names, fn (string $name): bool => $model->enabledFor($name))),
                'settings' => collect($fields)->mapWithKeys(fn (string $field): array => [$field => $model->setting($field)])->all(),
            ],
            'names' => collect($names)->map(fn (string $name): array => [
                'name' => $name,
                'label' => __('atrium-social::social.guards.'.$name),
                'redirect' => $this->guards->callbackUrl($name, $provider),
            ])->values(),
            'fields' => $fields,
            'urls' => [
                'index' => $this->guards->route($guard, 'providers'),
                'update' => $this->guards->route($guard, 'providers.update', ['provider' => $provider]),
            ],
        ]);
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
