<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Tests\Feature;

use Atrium\Admin\Facades\Hook;
use Atrium\SocialLogin\Tests\Support\TextIdPerson;
use Atrium\SocialLogin\Tests\TestCase;

/**
 * A guard set up carelessly: no auth middleware in front of its pages, and
 * people whose ids come as text.
 */
final class UnusualGuardsTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $config = $app['config'];
        $config->set('auth.guards.club', ['driver' => 'session', 'provider' => 'club_people']);
        $config->set('auth.providers.club_people', ['driver' => 'eloquent', 'model' => TextIdPerson::class]);
        $config->set('atrium-social.guards.club', [
            'enabled' => true,
            'prefix' => 'club/auth/social',
            'middleware' => ['web'],
            'localized' => false,
            'auth_middleware' => [],
            'login_route' => 'people.login',
            'active_column' => 'active',
        ]);
    }

    public function test_a_guest_is_refused_even_without_an_auth_middleware(): void
    {
        $this->provider('github', ['club']);

        $this->get(route('atrium-social.club.accounts'))->assertForbidden();
        $this->get(route('atrium-social.club.link', ['provider' => 'github']))->assertForbidden();
        $this->assertNull(session('atrium-social.pending'));
    }

    public function test_a_refusal_logs_a_person_whose_id_is_text_by_number(): void
    {
        $events = [];
        Hook::on('atrium.auth.event', function (string $guard, mixed $user, string $event, ?string $method = null, array $meta = []) use (&$events): void {
            $events[] = [$guard, $user, $event, $meta];
        });
        $person = $this->person(['active' => false]);
        $this->provider('github', ['club']);
        $state = $this->startAt(route('atrium-social.club.redirect', ['provider' => 'github']));
        $this->github(1001, [['email' => 'olga@example.org', 'primary' => true, 'verified' => true]]);

        $this->back('atrium-social.club.', $state)
            ->assertRedirect(route('people.login'))
            ->assertSessionHasErrors(['email' => __('atrium-social::social.refused.inactive', ['provider' => 'GitHub'])]);

        $this->assertSame([['club', $person->id, 'social_refused', ['reason' => 'inactive']]], $events);
        $this->assertGuest('club');
    }
}
