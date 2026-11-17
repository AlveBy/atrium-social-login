<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Tests\Feature;

use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Models\AdminRole;
use Atrium\SocialLogin\Tests\TestCase;

/**
 * What the module adds to the login buttons and to the menu, and that it
 * keeps whatever other packages put there first.
 */
final class HooksTest extends TestCase
{
    private const OTHERS = [['key' => 'code', 'label' => 'Code'], ['key' => 'passkey', 'label' => 'Passkey']];

    private const MENU = [['label' => 'Pages', 'route' => 'admin.pages'], ['label' => 'Users', 'route' => 'admin.users']];

    public function test_the_buttons_of_other_packages_stay_in_front_of_the_social_ones(): void
    {
        $this->provider('github', ['admin']);

        $this->assertSame([...self::OTHERS, [
            'key' => 'social-github',
            'label' => __('atrium-social::social.login.button', ['provider' => 'GitHub']),
            'url' => route('admin.social.redirect', ['provider' => 'github']),
            'kind' => 'link',
        ]], Hook::apply('atrium.auth.methods', self::OTHERS, 'admin'));
    }

    public function test_a_guard_the_module_does_not_know_gets_its_buttons_back_untouched(): void
    {
        $this->provider('github', ['admin', 'people']);

        $this->assertSame(self::OTHERS, Hook::apply('atrium.auth.methods', self::OTHERS, 'ghost'));
    }

    public function test_nobody_signed_in_gets_the_menu_back_untouched(): void
    {
        $this->assertSame(self::MENU, Hook::apply('atrium.admin.menu', self::MENU, null));
    }

    public function test_one_who_may_not_set_it_up_gets_only_their_accounts_after_the_other_items(): void
    {
        $editor = $this->admin(['email' => 'editor@example.com']);
        $editor->forceFill(['is_superadmin' => false])->save();
        $editor->roles()->attach(AdminRole::query()->create(['name' => 'Editors', 'permissions' => []]));

        $this->assertSame([...self::MENU, [
            'label' => 'atrium-social::social.menu.title',
            'icon' => 'globe',
            'children' => [
                ['label' => 'atrium-social::social.menu.accounts', 'route' => 'admin.social.accounts', 'icon' => 'globe'],
            ],
        ]], Hook::apply('atrium.admin.menu', self::MENU, $editor->fresh()));
    }

    public function test_one_who_may_set_it_up_also_gets_the_providers(): void
    {
        $this->assertSame([...self::MENU, [
            'label' => 'atrium-social::social.menu.title',
            'icon' => 'globe',
            'children' => [
                ['label' => 'atrium-social::social.menu.accounts', 'route' => 'admin.social.accounts', 'icon' => 'globe'],
                ['label' => 'atrium-social::social.menu.providers', 'route' => 'admin.social.providers', 'icon' => 'settings'],
            ],
        ]], Hook::apply('atrium.admin.menu', self::MENU, $this->admin()));
    }
}
