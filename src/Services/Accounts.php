<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Services;

use Atrium\Admin\Facades\Hook;
use Atrium\SocialLogin\Models\SocialAccount;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * Linked accounts and the sign-in log.
 */
final class Accounts
{
    public function find(string $guard, string $provider, string $id): ?SocialAccount
    {
        return SocialAccount::query()->where('guard', $guard)->where('provider', $provider)->where('provider_user_id', $id)->first();
    }

    public function link(string $guard, Authenticatable $user, string $provider, SocialiteUser $at): SocialAccount
    {
        return SocialAccount::query()->create([
            'guard' => $guard,
            'user_id' => $user->getAuthIdentifier(),
            'provider' => $provider,
            'provider_user_id' => (string) $at->getId(),
            'email' => $this->clip($at->getEmail()),
            'name' => $this->clip($at->getName() ?: $at->getNickname()),
            'last_used_at' => Carbon::now(),
        ]);
    }

    public function touch(SocialAccount $account, SocialiteUser $at): void
    {
        $account->forceFill([
            'email' => $this->clip($at->getEmail()) ?? $account->email,
            'name' => $this->clip($at->getName() ?: $at->getNickname()) ?? $account->name,
            'last_used_at' => Carbon::now(),
        ])->save();
    }

    /**
     * @return Collection<int,SocialAccount>
     */
    public function of(string $guard, Authenticatable $user): Collection
    {
        return SocialAccount::query()->where('guard', $guard)->where('user_id', $user->getAuthIdentifier())->orderBy('id')->get();
    }

    /**
     * The ways in a person keeps without this account: a password, other
     * linked accounts, and whatever other sign-in modules report (a code by
     * e-mail, a passkey).
     */
    public function otherWaysIn(string $guard, Authenticatable $user, SocialAccount $without): int
    {
        $ways = (string) $user->getAuthPassword() !== '' ? 1 : 0;
        $ways += SocialAccount::query()->where('guard', $guard)->where('user_id', $user->getAuthIdentifier())->whereKeyNot($without->id)->count();

        return (int) Hook::apply('atrium.auth.other_ways_in', $ways, $user, $guard);
    }

    /**
     * @param  array<string,mixed>  $meta
     */
    public function log(string $guard, Authenticatable|int|null $user, string $event, string $provider, array $meta = []): void
    {
        Hook::fire('atrium.auth.event', $guard, $user, $event, 'social:'.$provider, $meta);
    }

    private function clip(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : Str::limit($value, 250, '');
    }
}
