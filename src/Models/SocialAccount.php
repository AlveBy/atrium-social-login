<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * An account at a provider, linked to a person of a guard.
 *
 * @property int $id
 * @property string $guard
 * @property int $user_id
 * @property string $provider
 * @property string $provider_user_id
 * @property string|null $email
 * @property string|null $name
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 */
final class SocialAccount extends Model
{
    protected $table = 'auth_social_accounts';

    protected $fillable = ['guard', 'user_id', 'provider', 'provider_user_id', 'email', 'name', 'last_used_at'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'last_used_at' => 'datetime'];
    }
}
