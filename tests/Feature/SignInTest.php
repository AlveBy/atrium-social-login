<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Tests\Feature;

use Atrium\Admin\Auth\SignIn;
use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Facades\Setting;
use Atrium\SocialLogin\Models\SocialAccount;
use Atrium\SocialLogin\SocialLogin;
use Atrium\SocialLogin\Tests\Support\Person;
use Atrium\SocialLogin\Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Two\User;
use PHPUnit\Framework\Attributes\DataProvider;

final class SignInTest extends TestCase
{
    /** @var list<array{string,mixed,string,string}> */
    private array $events = [];

    protected function setUp(): void
    {
        parent::setUp();

        Hook::on('atrium.auth.event', function (string $guard, mixed $user, string $event, ?string $method = null, array $meta = []): void {
            $this->events[] = [$guard, $user instanceof Authenticatable ? $user->getAuthIdentifier() : $user, $event, $method, $meta];
        });
    }

    public function test_a_linked_account_signs_the_administrator_in(): void
    {
        $admin = $this->admin();
        $this->link($admin);
        $this->provider();
        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));
        $this->github(1001, []);

        $this->back('admin.social.', $state)->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertNotNull(SocialAccount::query()->sole()->last_used_at);
    }

    public function test_the_provider_is_sent_the_client_the_scopes_and_the_exact_return_address(): void
    {
        $this->provider();

        $location = (string) $this->get(route('admin.social.redirect', ['provider' => 'github']))->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertStringStartsWith('https://github.com/login/oauth/authorize?', $location);
        $this->assertSame('test-client', $query['client_id']);
        $this->assertSame(url('admin/auth/social/callback/github'), $query['redirect_uri']);
        $this->assertStringContainsString('user:email', $query['scope']);
        $this->assertSame(40, strlen($query['state']));
    }

    public function test_a_verified_address_finds_the_administrator_and_links_the_account(): void
    {
        $admin = $this->admin();
        $this->provider();
        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));
        $this->github(2002, [['email' => 'other@example.com', 'primary' => false, 'verified' => true], ['email' => 'Admin@Example.com', 'primary' => true, 'verified' => true]]);

        $this->back('admin.social.', $state)->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $account = SocialAccount::query()->sole();
        $this->assertSame(['admin', $admin->id, 'github', '2002'], [$account->guard, (int) $account->user_id, $account->provider, $account->provider_user_id]);
        $this->assertContains(['admin', $admin->id, 'social_linked', 'social:github', ['by' => 'verified_email']], $this->events);
    }

    /**
     * @return array<string,array{list<array{email:string,primary:bool,verified:bool}>,string}>
     */
    public static function unverified(): array
    {
        return [
            'primary address not verified' => [[['email' => 'admin@example.com', 'primary' => true, 'verified' => false]], 'no_email'],
            'verified address not primary' => [[['email' => 'admin@example.com', 'primary' => false, 'verified' => true]], 'no_email'],
            'no address at all' => [[], 'no_email'],
        ];
    }

    /**
     * @param  list<array{email:string,primary:bool,verified:bool}>  $emails
     */
    #[DataProvider('unverified')]
    public function test_an_address_the_provider_does_not_vouch_for_never_opens_an_account(array $emails, string $reason): void
    {
        $this->admin();
        $this->provider();
        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));
        $this->github(3003, $emails);

        $this->back('admin.social.', $state)
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors(['email' => __('atrium-social::social.refused.'.$reason, ['provider' => 'GitHub'])]);

        $this->assertGuest('admin');
        $this->assertSame(0, SocialAccount::query()->count());
        $this->assertSame('social_refused', $this->events[0][2]);
        $this->assertSame(['reason' => $reason], $this->events[0][4]);
    }

    /**
     * @return array<string,array{string|null,string|null,bool}>
     */
    public static function domains(): array
    {
        return [
            'any domain by default' => [null, null, true],
            'listed in config' => ['example.com', null, true],
            'listed among others, any case' => ['corp.test, EXAMPLE.com', null, true],
            'not listed in config' => ['corp.test', null, false],
            'the panel setting wins over config' => ['example.com', 'corp.test', false],
            'the panel setting lets in' => ['corp.test', "corp.test\n@example.com", true],
            'a subdomain is another domain' => ['mail.example.com', null, false],
        ];
    }

    #[DataProvider('domains')]
    public function test_the_panel_lets_in_only_the_allowed_domains(?string $config, ?string $setting, bool $allowed): void
    {
        config(['atrium-social.guards.admin.allowed_domains' => $config === null ? [] : explode(',', $config)]);

        if ($setting !== null) {
            Setting::save('social_login', ['admin_domains' => $setting]);
        }

        $admin = $this->admin();
        $this->provider();
        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));
        $this->github();

        $response = $this->back('admin.social.', $state);

        if ($allowed) {
            $response->assertRedirect(route('admin.dashboard'));
            $this->assertAuthenticatedAs($admin, 'admin');
        } else {
            $response->assertSessionHasErrors(['email' => __('atrium-social::social.refused.domain', ['provider' => 'GitHub'])]);
            $this->assertGuest('admin');
            $this->assertSame(0, SocialAccount::query()->count());
        }
    }

    public function test_a_foreign_domain_gets_the_same_answer_whether_or_not_the_address_has_an_account(): void
    {
        config(['atrium-social.guards.admin.allowed_domains' => ['corp.test']]);
        $this->admin();
        $this->provider();
        $answers = [];

        foreach (['admin@example.com', 'nobody@example.com'] as $email) {
            $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));
            $this->github(4004, [['email' => $email, 'primary' => true, 'verified' => true]]);
            $this->back('admin.social.', $state)->assertRedirect(route('admin.login'));
            $answers[] = session('errors')->first('email');
        }

        $this->assertSame($answers[0], $answers[1]);
    }

    public function test_nobody_is_ever_created_in_the_panel(): void
    {
        $created = false;
        $this->app->make(SocialLogin::class)->registerUsing('admin', function () use (&$created) {
            $created = true;

            return null;
        });
        config(['atrium-social.guards.admin.register' => true]);
        $this->provider();
        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));
        $this->github(5005, [['email' => 'stranger@example.com', 'primary' => true, 'verified' => true]]);

        $this->back('admin.social.', $state)->assertSessionHasErrors(['email' => __('atrium-social::social.refused.no_account', ['provider' => 'GitHub'])]);

        $this->assertFalse($created);
        $this->assertGuest('admin');
    }

    public function test_a_switched_off_administrator_is_refused_with_or_without_a_link(): void
    {
        $admin = $this->admin(['is_active' => false]);
        $this->provider();

        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));
        $this->github();
        $this->back('admin.social.', $state)->assertSessionHasErrors(['email' => __('atrium-social::social.refused.inactive', ['provider' => 'GitHub'])]);

        $this->link($admin, id: '1001');
        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));
        $this->github();
        $this->back('admin.social.', $state)->assertSessionHasErrors(['email' => __('atrium-social::social.refused.inactive', ['provider' => 'GitHub'])]);

        $this->assertGuest('admin');
    }

    /**
     * @return array<string,array{\Closure(string):array<string,string>}>
     */
    public static function badStates(): array
    {
        return [
            'another state' => [fn (string $state): array => ['state' => str_repeat('x', 40)]],
            'one character off' => [fn (string $state): array => ['state' => substr($state, 0, -1).($state[39] === 'a' ? 'b' : 'a')]],
            'no state' => [fn (string $state): array => ['state' => '']],
            'a state as a list' => [fn (string $state): array => ['state[]' => $state]],
        ];
    }

    /**
     * @param  \Closure(string):array<string,string>  $tamper
     */
    #[DataProvider('badStates')]
    public function test_a_state_that_does_not_match_is_refused_before_asking_the_provider(\Closure $tamper): void
    {
        $admin = $this->admin();
        $this->link($admin);
        $this->provider();
        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));

        $query = ['provider' => 'github', 'code' => 'code-1', ...$tamper($state)];
        $this->get(route('admin.social.callback', $query))
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors(['email' => __('atrium-social::social.refused.state', ['provider' => 'GitHub'])]);

        $this->assertGuest('admin');
        $this->assertCount(0, $this->sent, 'the code is not exchanged');
    }

    public function test_a_state_works_once(): void
    {
        $admin = $this->admin();
        $this->link($admin);
        $this->provider();
        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));
        $this->github();
        $this->back('admin.social.', $state)->assertRedirect(route('admin.dashboard'));
        $this->post(route('admin.logout'));

        $this->back('admin.social.', $state)->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    }

    public function test_a_state_expires_after_the_configured_minutes(): void
    {
        $admin = $this->admin();
        $this->link($admin);
        $this->provider();

        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));
        $this->travel(10)->minutes();
        $this->github();
        $this->back('admin.social.', $state)->assertRedirect(route('admin.dashboard'));
        $this->post(route('admin.logout'));

        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));
        $this->travel(601)->seconds();
        $this->back('admin.social.', $state)->assertSessionHasErrors(['email' => __('atrium-social::social.refused.state', ['provider' => 'GitHub'])]);
    }

    public function test_a_state_for_another_provider_or_guard_is_refused(): void
    {
        $admin = $this->admin();
        $this->link($admin);
        $this->provider();
        $this->provider('gitlab');

        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'gitlab']));
        $this->back('admin.social.', $state)->assertSessionHasErrors('email');

        $state = $this->startAt(route('atrium-social.people.redirect', ['provider' => 'github']));
        $this->back('admin.social.', $state)->assertSessionHasErrors('email');
        $this->assertGuest('admin');
        $this->assertCount(0, $this->sent);
    }

    public function test_a_cancelled_sign_in_says_so(): void
    {
        $this->provider();
        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));

        $this->get(route('admin.social.callback', ['provider' => 'github', 'state' => $state, 'error' => 'access_denied']))
            ->assertSessionHasErrors(['email' => __('atrium-social::social.refused.cancelled', ['provider' => 'GitHub'])]);
        $this->assertCount(0, $this->sent);
    }

    public function test_a_provider_that_fails_to_answer_is_a_refusal_not_an_error_page(): void
    {
        Log::spy();
        $this->provider();
        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));
        $this->http->append(new Response(500, [], 'down'));

        $this->back('admin.social.', $state)->assertSessionHasErrors(['email' => __('atrium-social::social.refused.failed', ['provider' => 'GitHub'])]);

        Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context): bool => $message === 'A social sign-in failed at the provider'
            && array_keys($context) === ['provider', 'exception']
            && $context['provider'] === 'github'
            && str_contains($context['exception'], '500 Internal Server Error'));
    }

    public function test_an_answer_without_an_id_is_a_refusal_whatever_address_comes_with_it(): void
    {
        $this->admin();
        $this->provider();
        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));
        $this->http->append(
            new Response(200, [], (string) json_encode(['access_token' => 'token-1'])),
            new Response(200, [], (string) json_encode(['id' => null, 'node_id' => 'N0', 'login' => 'octo', 'name' => 'Octo Cat', 'email' => null, 'avatar_url' => null])),
            new Response(200, [], (string) json_encode([['email' => 'admin@example.com', 'primary' => true, 'verified' => true]])),
        );

        $this->back('admin.social.', $state)->assertSessionHasErrors(['email' => __('atrium-social::social.refused.failed', ['provider' => 'GitHub'])]);

        $this->assertGuest('admin');
        $this->assertSame(0, SocialAccount::query()->count());
    }

    /**
     * @return array<string,array{mixed,string}>
     */
    public static function strangeSessions(): array
    {
        return [
            'not a list' => ['garbage', 'state-1'],
            'a mode that is not text' => [['mode' => 7], 'state-1'],
            'no expiry' => [['state' => 'state-1', 'guard' => 'admin', 'provider' => 'github', 'mode' => 'login', 'user' => null], 'state-1'],
        ];
    }

    #[DataProvider('strangeSessions')]
    public function test_a_sign_in_kept_in_a_strange_shape_is_refused_not_an_error_page(mixed $pending, string $state): void
    {
        $admin = $this->admin();
        $this->link($admin);
        $this->provider();

        $this->withSession(['atrium-social.pending' => $pending])
            ->get(route('admin.social.callback', ['provider' => 'github', 'code' => 'code-1', 'state' => $state]))
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors(['email' => __('atrium-social::social.refused.state', ['provider' => 'GitHub'])]);

        $this->assertGuest('admin');
        $this->assertCount(0, $this->sent);
    }

    public function test_a_state_lives_at_least_a_minute_whatever_is_configured(): void
    {
        $this->freezeSecond();
        config(['atrium-social.state_ttl' => 0]);
        $admin = $this->admin();
        $this->link($admin);
        $this->provider();

        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));
        $this->travel(60)->seconds();
        $this->github();
        $this->back('admin.social.', $state)->assertRedirect(route('admin.dashboard'));
        $this->post(route('admin.logout'));

        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));
        $this->travel(61)->seconds();
        $this->back('admin.social.', $state)->assertSessionHasErrors(['email' => __('atrium-social::social.refused.state', ['provider' => 'GitHub'])]);
    }

    public function test_without_the_setting_a_state_lives_ten_minutes(): void
    {
        $this->freezeSecond();
        $config = (array) config('atrium-social');
        unset($config['state_ttl']);
        config(['atrium-social' => $config]);
        $admin = $this->admin();
        $this->link($admin);
        $this->provider();

        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));
        $this->travel(600)->seconds();
        $this->github();
        $this->back('admin.social.', $state)->assertRedirect(route('admin.dashboard'));
        $this->post(route('admin.logout'));

        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));
        $this->travel(601)->seconds();
        $this->back('admin.social.', $state)->assertSessionHasErrors(['email' => __('atrium-social::social.refused.state', ['provider' => 'GitHub'])]);
    }

    /**
     * @return array<string,array{string,list<string>,bool,bool}>
     */
    public static function switches(): array
    {
        return [
            'on for the guard' => ['test-secret', ['admin'], true, false],
            'on for the other guard only' => ['test-secret', ['people'], false, true],
            'off everywhere' => ['test-secret', [], false, false],
            'no secret entered' => ['', ['admin', 'people'], false, false],
        ];
    }

    /**
     * @param  list<string>  $guards
     */
    #[DataProvider('switches')]
    public function test_a_provider_works_only_where_it_is_switched_on_and_set_up(string $secret, array $guards, bool $admin, bool $people): void
    {
        $this->provider(guards: $guards, secret: $secret);

        $this->get(route('admin.social.redirect', ['provider' => 'github']))->assertStatus($admin ? 302 : 404);
        $this->get(route('atrium-social.people.redirect', ['provider' => 'github']))->assertStatus($people ? 302 : 404);
        $this->get(route('admin.social.callback', ['provider' => 'github']))->assertStatus($admin ? 302 : 404);
    }

    public function test_an_unknown_provider_is_not_found(): void
    {
        $this->get('/admin/auth/social/redirect/myspace')->assertNotFound();
        $this->get('/admin/auth/social/redirect/GitHub')->assertNotFound();
    }

    public function test_the_second_step_still_applies_after_a_social_sign_in(): void
    {
        $seen = [];
        $this->app->make(SignIn::class)->challengeUsing(function (Request $request, $user, string $guard, string $method) use (&$seen) {
            $seen[] = [$guard, $method];

            return redirect('/second-step');
        });
        $admin = $this->admin();
        $this->link($admin);
        $this->provider();
        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));
        $this->github();

        $this->back('admin.social.', $state)->assertRedirect('/second-step');

        $this->assertSame([['admin', 'social:github']], $seen);
        $this->assertGuest('admin');
    }

    public function test_the_personal_account_registers_a_newcomer_with_a_verified_address(): void
    {
        $this->app->make(SocialLogin::class)->registerUsing('people', fn (User $at, string $provider) => Person::query()->create([
            'name' => (string) $at->getName(),
            'email' => (string) $at->getEmail(),
            'password' => null,
        ]));
        $this->provider();
        $state = $this->startAt(route('atrium-social.people.redirect', ['provider' => 'github']));
        $this->github(6006, [['email' => 'new@example.org', 'primary' => true, 'verified' => true]]);

        $this->back('atrium-social.people.', $state)->assertRedirect(route('people.home'));

        $person = Person::query()->where('email', 'new@example.org')->sole();
        $this->assertAuthenticatedAs($person, 'people');
        $this->assertSame('6006', SocialAccount::query()->where('guard', 'people')->sole()->provider_user_id);
        $this->assertContains(['people', $person->id, 'social_linked', 'social:github', ['by' => 'registered']], $this->events);
    }

    /**
     * @return array<string,array{string}>
     */
    public static function waysToSignIn(): array
    {
        return ['a linked account' => ['linked'], 'a verified address' => ['address'], 'registering' => ['registered']];
    }

    #[DataProvider('waysToSignIn')]
    public function test_a_social_sign_in_is_never_remembered_and_says_which_provider(string $way): void
    {
        $finished = [];
        $this->app->make(SignIn::class)->finishUsing('people', function ($request, $user, bool $remember, string $method) use (&$finished) {
            $finished[] = [$user->email, $remember, $method];

            return redirect('/done');
        });
        $this->app->make(SocialLogin::class)->registerUsing('people', fn (User $at) => Person::query()->create(['name' => 'New', 'email' => (string) $at->getEmail()]));
        $this->provider();

        if ($way !== 'registered') {
            $person = $this->person();

            if ($way === 'linked') {
                $this->link($person, 'people', 'github', '6006');
            }
        }

        $state = $this->startAt(route('atrium-social.people.redirect', ['provider' => 'github']));
        $this->github(6006, [['email' => $way === 'registered' ? 'new@example.org' : 'olga@example.org', 'primary' => true, 'verified' => true]]);

        $this->back('atrium-social.people.', $state)->assertRedirect('/done');

        $this->assertSame([[$way === 'registered' ? 'new@example.org' : 'olga@example.org', false, 'social:github']], $finished);
    }

    public function test_a_guard_that_does_not_say_it_registers_does_not(): void
    {
        config(['atrium-social.guards.people' => array_diff_key((array) config('atrium-social.guards.people'), ['register' => true])]);
        $this->app->make(SocialLogin::class)->registerUsing('people', fn (User $at) => Person::query()->create(['name' => 'New', 'email' => (string) $at->getEmail()]));
        $this->provider();
        $state = $this->startAt(route('atrium-social.people.redirect', ['provider' => 'github']));
        $this->github(7007, [['email' => 'new@example.org', 'primary' => true, 'verified' => true]]);

        $this->back('atrium-social.people.', $state)
            ->assertSessionHasErrors(['email' => __('atrium-social::social.refused.no_account', ['provider' => 'GitHub'])]);

        $this->assertSame(0, Person::query()->count());
    }

    public function test_a_refusal_is_logged_with_the_person_when_there_is_one(): void
    {
        $admin = $this->admin(['is_active' => false]);
        $this->provider();

        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));
        $this->github();
        $this->back('admin.social.', $state)->assertSessionHasErrors('email');

        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));
        $this->github(9009, [['email' => 'stranger@example.com', 'primary' => true, 'verified' => true]]);
        $this->back('admin.social.', $state)->assertSessionHasErrors('email');

        $this->assertSame([
            ['admin', $admin->id, 'social_refused', 'social:github', ['reason' => 'inactive']],
            ['admin', null, 'social_refused', 'social:github', ['reason' => 'no_account']],
        ], $this->events);
    }

    public function test_without_a_registrar_or_with_register_off_a_newcomer_is_refused(): void
    {
        $this->provider();

        $state = $this->startAt(route('atrium-social.people.redirect', ['provider' => 'github']));
        $this->github(7007, [['email' => 'new@example.org', 'primary' => true, 'verified' => true]]);
        $this->back('atrium-social.people.', $state)->assertRedirect(route('people.login'))->assertSessionHasErrors('email');

        config(['atrium-social.guards.people.register' => false]);
        $this->app->make(SocialLogin::class)->registerUsing('people', fn () => $this->person(['email' => 'new@example.org']));
        $state = $this->startAt(route('atrium-social.people.redirect', ['provider' => 'github']));
        $this->github(7007, [['email' => 'new@example.org', 'primary' => true, 'verified' => true]]);
        $this->back('atrium-social.people.', $state)->assertSessionHasErrors('email');

        $this->assertSame(0, Person::query()->count());
        $this->assertGuest('people');
    }

    public function test_the_same_account_at_a_provider_is_separate_per_guard(): void
    {
        $admin = $this->admin();
        $person = $this->person();
        $this->link($admin, 'admin', 'github', '1001');
        $this->link($person, 'people', 'github', '1001');
        $this->provider();

        $state = $this->startAt(route('atrium-social.people.redirect', ['provider' => 'github']));
        $this->github();
        $this->back('atrium-social.people.', $state)->assertRedirect(route('people.home'));

        $this->assertAuthenticatedAs($person, 'people');
        $this->assertGuest('admin');
    }

    public function test_the_login_screen_shows_a_button_for_every_provider_switched_on(): void
    {
        $this->provider('github', ['admin']);
        $this->provider('google', ['people']);
        $this->provider('gitlab', ['admin'], secret: '');

        $methods = Hook::apply('atrium.auth.methods', [], 'admin');

        $this->assertSame([[
            'key' => 'social-github',
            'label' => __('atrium-social::social.login.button', ['provider' => 'GitHub']),
            'url' => route('admin.social.redirect', ['provider' => 'github']),
            'kind' => 'link',
        ]], $methods);
        $this->assertSame(['social-google'], array_column(Hook::apply('atrium.auth.methods', [], 'people'), 'key'));
        $this->assertSame([['key' => 'x']], Hook::apply('atrium.auth.methods', [['key' => 'x']], 'ghost'));
    }
}
