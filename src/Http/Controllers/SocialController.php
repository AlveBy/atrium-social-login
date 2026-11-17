<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Http\Controllers;

use Atrium\Admin\Auth\SignIn;
use Atrium\SocialLogin\Guards;
use Atrium\SocialLogin\Providers;
use Atrium\SocialLogin\Services\Accounts;
use Atrium\SocialLogin\SocialLogin;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\User as SocialiteUser;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Off to the provider and back: signing in, or linking an account from the
 * profile.
 *
 * The state that comes back has to be the one sent, for this guard and this
 * provider, within state_ttl minutes; it is kept in the session.
 *
 * A provider that answers with a POST from its own site (Apple) comes
 * without the session cookie, which is SameSite=Lax. That POST is taken by
 * a route without a session, whose page posts the same fields again from
 * this site (return), now with the cookie. Telegram's answer, after a # the
 * server never sees, is posted to the same place by a script.
 */
final class SocialController extends Controller
{
    private const SESSION = 'atrium-social.pending';

    public function __construct(
        Guards $guards,
        Providers $providers,
        private readonly Accounts $accounts,
        private readonly SocialLogin $social,
    ) {
        parent::__construct($guards, $providers);
    }

    public function redirect(Request $request, string $provider): Response
    {
        $guard = $this->enabledOrFail($request, $provider);

        return $this->start($request, $guard, $provider, 'login');
    }

    public function link(Request $request, string $provider): Response
    {
        $guard = $this->enabledOrFail($request, $provider);

        return $this->start($request, $guard, $provider, 'link', $this->user($request)->getAuthIdentifier());
    }

    /**
     * A POST from the provider's site: sent on from this site, where the
     * browser adds the session cookie. Runs without a session of its own,
     * which would replace the person's.
     */
    public function bounce(Request $request, string $provider): Response
    {
        $guard = $this->enabledOrFail($request, $provider);
        $fields = array_filter($request->post(), fn ($value): bool => is_string($value) && strlen($value) <= 8192);

        return response($this->view($request, 'bounce', [
            'action' => $this->guards->returnUrl($guard, $provider),
            'fields' => $fields,
            'fragment' => false,
        ]))->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function callback(Request $request, string $provider): Response
    {
        $guard = $this->enabledOrFail($request, $provider);
        $isTelegram = $this->providers->driverOf($provider) === 'telegram';

        // Telegram puts the answer after a # the server never sees: a script
        // on this page reads it and posts it on.
        if ($isTelegram && $request->isMethod('GET') && ! $request->has('hash')) {
            return response($this->view($request, 'bounce', [
                'action' => $this->guards->returnUrl($guard, $provider),
                'fields' => ['state' => $request->query('state', '')],
                'fragment' => true,
            ]))->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
        }

        $pending = (array) $request->session()->pull(self::SESSION, []);
        $mode = (string) ($pending['mode'] ?? 'login');

        if (! $this->stateMatches($pending, $request, $guard, $provider)) {
            return $this->refuse($request, $guard, $provider, $mode, 'state');
        }

        if ($request->filled('error')) {
            return $this->refuse($request, $guard, $provider, $mode, 'cancelled');
        }

        $at = $this->fetchUser($request, $guard, $provider, $isTelegram);

        if ($at === null || (string) $at->getId() === '') {
            return $this->refuse($request, $guard, $provider, $mode, 'failed');
        }

        return $mode === 'link'
            ? $this->linkTo($request, $guard, $provider, $at, $pending['user'] ?? null)
            : $this->signIn($request, $guard, $provider, $at);
    }

    private function start(Request $request, string $guard, string $provider, string $mode, int|string|null $user = null): Response
    {
        $state = Str::random(40);
        $pending = [
            'state' => $state,
            'guard' => $guard,
            'provider' => $provider,
            'mode' => $mode,
            'user' => $user,
            'expires' => Carbon::now()->addMinutes(max(1, (int) config('atrium-social.state_ttl', 10)))->getTimestamp(),
        ];
        $request->session()->put(self::SESSION, $pending);
        $callback = $this->guards->callbackUrl($guard, $provider);

        if ($this->providers->driverOf($provider) === 'telegram') {
            return redirect()->away($this->providers->telegram($provider)->authUrl($request->getSchemeAndHttpHost(), $callback.'?'.http_build_query(['state' => $state])));
        }

        try {
            return $this->providers->make($provider, $callback)->with(['state' => $state])->redirect();
        } catch (Throwable $e) {
            // A misconfigured server (an OpenID Connect issuer that does not
            // answer): a refusal on the login screen, not an error page.
            Log::warning('A social sign-in could not start', ['provider' => $provider, 'exception' => $e->getMessage()]);

            return $this->refuse($request, $guard, $provider, $mode, 'failed');
        }
    }

    /**
     * @param  array<string,mixed>  $pending
     */
    private function stateMatches(array $pending, Request $request, string $guard, string $provider): bool
    {
        $state = $request->input('state');

        return is_string($state) && $state !== ''
            && hash_equals((string) ($pending['state'] ?? ''), $state)
            && ($pending['guard'] ?? null) === $guard
            && ($pending['provider'] ?? null) === $provider
            && (int) ($pending['expires'] ?? null) >= Carbon::now()->getTimestamp();
    }

    private function fetchUser(Request $request, string $guard, string $provider, bool $isTelegram): ?SocialiteUser
    {
        try {
            if ($isTelegram) {
                $data = $request->except(['state']);

                return $this->providers->telegram($provider)->user($data);
            }

            $user = $this->providers->make($provider, $this->guards->callbackUrl($guard, $provider))->user();

            return $user instanceof SocialiteUser ? $user : null;
        } catch (Throwable $e) {
            Log::warning('A social sign-in failed at the provider', ['provider' => $provider, 'exception' => $e->getMessage()]);

            return null;
        }
    }

    private function signIn(Request $request, string $guard, string $provider, SocialiteUser $at): Response
    {
        $account = $this->accounts->find($guard, $provider, (string) $at->getId());

        if ($account !== null) {
            $user = $this->guards->find($guard, $account->user_id);

            if ($user === null) {
                return $this->refuse($request, $guard, $provider, 'login', 'inactive', $account->user_id);
            }

            $this->accounts->touch($account, $at);

            return app(SignIn::class)->start($request, $user, $guard, 'social:'.$provider, false);
        }

        // Nobody has linked this account: an address the provider vouches
        // for may find the person, nothing else may.
        $email = (string) $at->getEmail();

        if (! $this->providers->verifiedEmail($provider, $at)) {
            return $this->refuse($request, $guard, $provider, 'login', $email === '' ? 'no_email' : 'unverified');
        }

        $user = $this->guards->findByEmail($guard, $email);

        // The domain first, so the answer does not tell whether the address
        // has an account.
        if (! $this->guards->domainAllowed($guard, $email)) {
            return $this->refuse($request, $guard, $provider, 'login', 'domain', $user?->getAuthIdentifier());
        }

        if ($user !== null) {
            if (! $this->guards->active($guard, $user)) {
                return $this->refuse($request, $guard, $provider, 'login', 'inactive', $user->getAuthIdentifier());
            }

            $this->accounts->link($guard, $user, $provider, $at);
            $this->accounts->log($guard, $user, 'social_linked', $provider, ['by' => 'verified_email']);

            return app(SignIn::class)->start($request, $user, $guard, 'social:'.$provider, false);
        }

        // A newcomer: only where the guard registers people (never the panel).
        $user = $guard !== $this->guards->adminGuard() && $this->guards->config($guard, 'register', false)
                ? $this->social->register($guard, $at, $provider)
                : null;

        if ($user === null) {
            return $this->refuse($request, $guard, $provider, 'login', 'no_account');
        }

        $this->accounts->link($guard, $user, $provider, $at);
        $this->accounts->log($guard, $user, 'social_linked', $provider, ['by' => 'registered']);

        return app(SignIn::class)->start($request, $user, $guard, 'social:'.$provider, false);
    }

    private function linkTo(Request $request, string $guard, string $provider, SocialiteUser $at, int|string|null $userId): Response
    {
        $user = Auth::guard($guard)->user();

        // The person who started linking must be the one coming back.
        if (! $user instanceof Authenticatable || $userId === null || (string) $user->getAuthIdentifier() !== (string) $userId) {
            return $this->refuse($request, $guard, $provider, 'login', 'state');
        }

        $account = $this->accounts->find($guard, $provider, (string) $at->getId());

        if ($account !== null && (string) $account->user_id !== (string) $user->getAuthIdentifier()) {
            return $this->refuse($request, $guard, $provider, 'link', 'taken', $user->getAuthIdentifier());
        }

        if ($account === null) {
            $this->accounts->link($guard, $user, $provider, $at);
            $this->accounts->log($guard, $user, 'social_linked', $provider, ['by' => 'profile']);
        }

        return redirect()->to($this->guards->route($guard, 'accounts'))
            ->with('atrium-social.status', __('atrium-social::social.accounts.linked', ['provider' => $this->providers->label($provider)]));
    }

    private function refuse(Request $request, string $guard, string $provider, string $mode, string $reason, int|string|null $user = null): RedirectResponse
    {
        $this->accounts->log($guard, $user === null ? null : (int) $user, 'social_refused', $provider, ['reason' => $reason]);
        $message = __('atrium-social::social.refused.'.$reason, ['provider' => $this->providers->label($provider)]);

        if ($mode === 'link' && Auth::guard($guard)->check()) {
            return redirect()->to($this->guards->route($guard, 'accounts'))->withErrors(['social' => $message]);
        }

        return redirect()->to($this->guards->loginUrl($guard))->withErrors(['email' => $message]);
    }

    private function enabledOrFail(Request $request, string $provider): string
    {
        $guard = $this->guard($request);
        abort_unless($this->providers->enabled($provider, $guard), 404);

        return $guard;
    }
}
