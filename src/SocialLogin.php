<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * What the owner of a guard tells this package: how to create a person from
 * a verified address at a provider (the personal account registers people;
 * the panel never does).
 */
final class SocialLogin
{
    /** @var array<string,callable(SocialiteUser,string):?Authenticatable> */
    private array $registrars = [];

    /**
     * @param  callable(SocialiteUser,string):?Authenticatable  $registrar  (user at the provider, provider key)
     */
    public function registerUsing(string $guard, callable $registrar): void
    {
        $this->registrars[$guard] = $registrar;
    }

    public function register(string $guard, SocialiteUser $user, string $provider): ?Authenticatable
    {
        return isset($this->registrars[$guard]) ? ($this->registrars[$guard])($user, $provider) : null;
    }
}
