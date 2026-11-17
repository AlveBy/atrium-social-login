<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Http\Controllers;

use Atrium\SocialLogin\Guards;
use Atrium\SocialLogin\Models\SocialAccount;
use Atrium\SocialLogin\Providers;
use Atrium\SocialLogin\Services\Accounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The accounts at other services linked to one's own: link one more, or
 * unlink one, but never the last way in.
 */
final class AccountsController extends Controller
{
    public function __construct(
        Guards $guards,
        Providers $providers,
        private readonly Accounts $accounts,
    ) {
        parent::__construct($guards, $providers);
    }

    public function index(Request $request): Response
    {
        $guard = $this->guard($request);
        $linked = $this->accounts->of($guard, $this->user($request));

        return response($this->view($request, 'accounts', [
            'linked' => $linked,
            'available' => array_diff($this->providers->enabledFor($guard), $linked->pluck('provider')->all()),
        ]));
    }

    public function destroy(Request $request, int $account): RedirectResponse
    {
        $guard = $this->guard($request);
        $user = $this->user($request);
        $found = SocialAccount::query()
            ->where('guard', $guard)
            ->where('user_id', $user->getAuthIdentifier())
            ->findOrFail($account);
        $back = redirect()->to($this->guards->route($guard, 'accounts'));

        if ($this->accounts->otherWaysIn($guard, $user, $found) === 0) {
            return $back->withErrors(['social' => __('atrium-social::social.accounts.last_method')]);
        }

        $found->delete();
        $this->accounts->log($guard, $user, 'social_unlinked', $found->provider);

        return $back->with('atrium-social.status', __('atrium-social::social.accounts.unlinked', ['provider' => $this->providers->label($found->provider)]));
    }
}
