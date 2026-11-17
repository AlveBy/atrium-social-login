<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Tests\Feature;

use Atrium\Admin\Facades\Hook;
use Atrium\SocialLogin\Tests\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * Before `atrium:module:install alveby/atrium-social-login` the package
 * offers no way in: no routes, no buttons, no menu.
 */
final class NotInstalledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('atrium.modules', []);
    }

    public function test_nothing_is_wired_until_the_module_is_installed(): void
    {
        $this->assertFalse(Route::has('admin.social.redirect'));
        $this->assertSame([], Hook::apply('atrium.auth.methods', [], 'admin'));
        $this->assertSame([], Hook::apply('atrium.admin.menu', [], $this->admin()));
        $this->get('/admin/auth/social/redirect/github')->assertNotFound();
    }
}
