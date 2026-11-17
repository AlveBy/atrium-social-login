<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A provider as set up in the panel.
 *
 * @property int $id
 * @property string $key
 * @property list<string>|null $guards
 * @property string|null $client_id
 * @property string|null $client_secret
 * @property array<string,string>|null $settings
 */
final class SocialProvider extends Model
{
    protected $table = 'auth_social_providers';

    protected $fillable = ['key', 'guards', 'client_id', 'client_secret', 'settings'];

    protected $hidden = ['client_secret'];

    protected function casts(): array
    {
        return [
            'guards' => 'array',
            'settings' => 'array',
            // Encrypted with the application key: a copy of the database
            // alone does not give the secret away.
            'client_secret' => 'encrypted',
        ];
    }

    public function enabledFor(string $guard): bool
    {
        return in_array($guard, (array) $this->guards, true);
    }

    public function setting(string $name): string
    {
        return trim((string) ($this->settings[$name] ?? ''));
    }
}
