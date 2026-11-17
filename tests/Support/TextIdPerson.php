<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Tests\Support;

use Illuminate\Foundation\Auth\User;

/**
 * Somebody whose id the guard reads as text, as some databases give it.
 *
 * @property string $id
 * @property string $email
 * @property bool $active
 */
final class TextIdPerson extends User
{
    protected $table = 'test_people';

    protected $keyType = 'string';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'active' => 'boolean'];
    }
}
