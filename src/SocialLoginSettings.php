<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin;

use Atrium\Admin\Fields\TextareaField;
use Atrium\Admin\Modules\ModuleSettings;

/**
 * Modules → Social sign-in: which e-mail domains may open an existing
 * account with a provider's verified address. An empty field keeps what
 * config/atrium-social.php says. The providers themselves are set up on
 * their own page (Social sign-in → Providers).
 */
final class SocialLoginSettings extends ModuleSettings
{
    public function __construct(private readonly Guards $guards) {}

    public function description(): string
    {
        return __('atrium-social::social.settings.description');
    }

    public function fields(): array
    {
        $fields = [];

        foreach ($this->guards->names() as $guard) {
            $fields[] = TextareaField::make("{$guard}_domains")
                ->label(__('atrium-social::social.settings.domains', ['who' => __('atrium-social::social.guards.'.$guard)]))
                ->hint(__('atrium-social::social.settings.domains_hint'))
                ->rules(['max:2000']);
        }

        return $fields;
    }
}
