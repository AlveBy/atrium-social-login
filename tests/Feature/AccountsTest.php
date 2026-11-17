<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Tests\Feature;

use Atrium\Admin\Facades\Hook;
use Atrium\SocialLogin\Models\SocialAccount;
use Atrium\SocialLogin\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class AccountsTest extends TestCase
{
    public function test_the_page_needs_a_signed_in_person(): void
    {
        $this->get(route('admin.social.accounts'))->assertRedirect(route('admin.login'));
        $this->get(route('atrium-social.people.accounts'))->assertRedirect(route('login'));
    }

    public function test_the_page_lists_the_linked_accounts_and_offers_the_others(): void
    {
        $admin = $this->admin();
        $this->provider('github');
        $this->provider('gitlab');
        $this->provider('google', ['people']);
        $account = $this->link($admin);
        $account->forceFill(['email' => 'octo@example.com'])->save();

        $this->actingAs($admin, 'admin')->get(route('admin.social.accounts'))
            ->assertOk()
            ->assertSee('dusk="asl-linked-github"', false)
            ->assertSee('octo@example.com')
            ->assertSee(__('atrium-social::social.accounts.never'))
            ->assertSee('dusk="asl-link-gitlab"', false)
            ->assertDontSee('dusk="asl-link-github"', false)
            ->assertDontSee('dusk="asl-link-google"', false);
    }

    public function test_an_inertia_visit_from_the_menu_loads_the_page_whole(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.social.accounts'), ['X-Inertia' => 'true'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', route('admin.social.accounts'));
    }

    public function test_linking_from_the_profile_adds_the_account_whatever_its_address(): void
    {
        $events = [];
        Hook::on('atrium.auth.event', function (...$args) use (&$events): void {
            $events[] = array_slice($args, 2);
        });
        $admin = $this->admin();
        $this->provider();
        $this->actingAs($admin, 'admin');
        $state = $this->startAt(route('admin.social.link', ['provider' => 'github']));
        // No verified address at all: linking from the profile needs none.
        $this->github(8008, []);

        $this->back('admin.social.', $state)
            ->assertRedirect(route('admin.social.accounts'))
            ->assertSessionHas('atrium-social.status', __('atrium-social::social.accounts.linked', ['provider' => 'GitHub']));

        $this->assertSame('8008', SocialAccount::query()->where('user_id', $admin->id)->sole()->provider_user_id);
        $this->assertSame([['social_linked', 'social:github', ['by' => 'profile']]], $events);
    }

    public function test_a_link_whose_person_signed_out_meanwhile_is_refused(): void
    {
        $admin = $this->admin();
        $this->provider();
        $this->actingAs($admin, 'admin');
        $state = $this->startAt(route('admin.social.link', ['provider' => 'github']));
        $this->app['auth']->guard('admin')->logout();
        $this->github();

        $this->back('admin.social.', $state)
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors(['email' => __('atrium-social::social.refused.state', ['provider' => 'GitHub'])]);
        $this->assertSame(0, SocialAccount::query()->count());
    }

    public function test_linking_needs_a_signed_in_person(): void
    {
        $this->provider();

        $this->get(route('admin.social.link', ['provider' => 'github']))->assertRedirect(route('admin.login'));
    }

    public function test_linking_twice_keeps_one_account(): void
    {
        $admin = $this->admin();
        $this->link($admin);
        $this->provider();
        $this->actingAs($admin, 'admin');
        $state = $this->startAt(route('admin.social.link', ['provider' => 'github']));
        $this->github();

        $this->back('admin.social.', $state)
            ->assertRedirect(route('admin.social.accounts'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('atrium-social.status', __('atrium-social::social.accounts.linked', ['provider' => 'GitHub']));
        $this->assertSame(1, SocialAccount::query()->count());
    }

    public function test_an_account_linked_to_someone_else_is_not_taken_over(): void
    {
        $owner = $this->admin();
        $this->link($owner);
        $other = $this->admin(['email' => 'second@example.com']);
        $this->provider();
        $this->actingAs($other, 'admin');
        $state = $this->startAt(route('admin.social.link', ['provider' => 'github']));
        $this->github();

        $this->back('admin.social.', $state)
            ->assertRedirect(route('admin.social.accounts'))
            ->assertSessionHasErrors(['social' => __('atrium-social::social.refused.taken', ['provider' => 'GitHub'])]);
        $this->assertSame($owner->id, (int) SocialAccount::query()->sole()->user_id);
    }

    public function test_a_link_started_by_one_person_does_not_finish_for_another(): void
    {
        $first = $this->admin();
        $second = $this->admin(['email' => 'second@example.com']);
        $this->provider();
        $this->actingAs($first, 'admin');
        $state = $this->startAt(route('admin.social.link', ['provider' => 'github']));
        $this->app['auth']->guard('admin')->setUser($second);
        $this->actingAs($second, 'admin');
        $this->github();

        $this->back('admin.social.', $state)->assertSessionHasErrors('email');
        $this->assertSame(0, SocialAccount::query()->count());
    }

    /**
     * @return array<string,array{bool,int,int,bool}>
     */
    public static function ways(): array
    {
        return [
            'a password stays' => [true, 1, 0, true],
            'another account stays' => [false, 2, 0, true],
            'another module reports a way' => [false, 1, 1, true],
            'nothing else' => [false, 1, 0, false],
        ];
    }

    #[DataProvider('ways')]
    public function test_the_last_way_in_is_never_unlinked(bool $password, int $accounts, int $fromModules, bool $allowed): void
    {
        $person = $this->person(['password' => $password ? 'person-password' : null]);
        $first = $this->link($person, 'people', 'github', '1');

        if ($accounts > 1) {
            $this->link($person, 'people', 'gitlab', '2');
        }

        Hook::filter('atrium.auth.other_ways_in', fn (int $ways, $user, string $guard): int => $guard === 'people' ? $ways + $fromModules : $ways);

        $response = $this->actingAs($person, 'people')->delete(route('atrium-social.people.unlink', $first->id));

        $response->assertRedirect(route('atrium-social.people.accounts'));

        if ($allowed) {
            $response->assertSessionHas('atrium-social.status', __('atrium-social::social.accounts.unlinked', ['provider' => 'GitHub']));
            $this->assertNull($first->fresh());
        } else {
            $response->assertSessionHasErrors(['social' => __('atrium-social::social.accounts.last_method')]);
            $this->assertNotNull($first->fresh());
        }
    }

    public function test_one_can_unlink_only_one_s_own_account(): void
    {
        $admin = $this->admin();
        $other = $this->admin(['email' => 'second@example.com']);
        $theirs = $this->link($other);
        $person = $this->person();
        $samePersonOtherGuard = $this->link($person, 'people', 'github', '5');

        $this->actingAs($admin, 'admin')->delete(route('admin.social.unlink', $theirs->id))->assertNotFound();
        $this->actingAs($admin, 'admin')->delete(route('admin.social.unlink', $samePersonOtherGuard->id))->assertNotFound();
        $this->assertSame(2, SocialAccount::query()->count());
    }

    public function test_unlinking_is_logged(): void
    {
        $events = [];
        Hook::on('atrium.auth.event', function (...$args) use (&$events): void {
            $events[] = array_slice($args, 2, 2);
        });
        $admin = $this->admin();
        $account = $this->link($admin);

        $this->actingAs($admin, 'admin')->delete(route('admin.social.unlink', $account->id));

        $this->assertSame([['social_unlinked', 'social:github']], $events);
    }
}
