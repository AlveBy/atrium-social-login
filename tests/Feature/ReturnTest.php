<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Tests\Feature;

use Atrium\SocialLogin\Models\SocialAccount;
use Atrium\SocialLogin\Tests\TestCase;
use Illuminate\Support\Carbon;

/**
 * Answers that come as a POST from the provider's site (Apple), and
 * Telegram's, which comes after a # the server never sees.
 */
final class ReturnTest extends TestCase
{
    private const BOT = '123456:test-bot-token';

    public function test_a_post_from_the_provider_is_sent_on_from_this_site_without_a_session(): void
    {
        $this->provider('apple');

        $response = $this->post(route('admin.social.bounce', ['provider' => 'apple']), [
            'code' => 'code-1',
            'state' => 'state-1',
            'user' => '{"name":{"firstName":"Ann"}}',
            'huge' => str_repeat('x', 9000),
            'largest' => str_repeat('y', 8192),
            'just_over' => str_repeat('z', 8193),
        ]);

        $response->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('action="'.route('admin.social.return', ['provider' => 'apple']).'"', false)
            ->assertSee('name="code" value="code-1"', false)
            ->assertSee('name="state" value="state-1"', false)
            ->assertSee('name="user" value="{&quot;name&quot;:{&quot;firstName&quot;:&quot;Ann&quot;}}"', false)
            ->assertDontSee('name="huge"', false)
            ->assertSee('name="largest" value="'.str_repeat('y', 8192).'"', false)
            ->assertDontSee('name="just_over"', false)
            ->assertDontSee('tgAuthResult', false)
            ->assertCookieMissing(config('session.cookie'));
    }

    public function test_the_post_needs_no_token_and_a_switched_off_provider_gets_nothing(): void
    {
        $this->post(route('admin.social.bounce', ['provider' => 'apple']))->assertNotFound();
        $this->provider('apple', ['people']);
        $this->post(route('admin.social.bounce', ['provider' => 'apple']))->assertNotFound();
    }

    public function test_the_answer_posted_on_signs_in_like_a_redirect_does(): void
    {
        $admin = $this->admin();
        $this->link($admin);
        $this->provider();
        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'github']));
        $this->github();

        $this->post(route('admin.social.return', ['provider' => 'github']), ['code' => 'code-1', 'state' => $state])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_an_answer_posted_on_with_a_foreign_state_is_refused(): void
    {
        $this->admin();
        $this->provider();
        $this->startAt(route('admin.social.redirect', ['provider' => 'github']));

        $this->post(route('admin.social.return', ['provider' => 'github']), ['code' => 'code-1', 'state' => str_repeat('z', 40)])
            ->assertSessionHasErrors('email');
        $this->assertGuest('admin');
        $this->assertCount(0, $this->sent);
    }

    public function test_telegram_is_asked_with_the_bot_this_site_and_the_way_back(): void
    {
        $this->provider('telegram', secret: self::BOT);

        $location = (string) $this->get(route('admin.social.redirect', ['provider' => 'telegram']))->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertStringStartsWith('https://oauth.telegram.org/auth?', $location);
        $this->assertSame('123456', $query['bot_id']);
        $this->assertSame('http://localhost', $query['origin']);
        $this->assertStringStartsWith(route('admin.social.callback', ['provider' => 'telegram']).'?state=', $query['return_to']);
        $this->assertStringNotContainsString('test-bot-token', $location);
    }

    public function test_telegram_s_answer_is_read_by_a_page_and_signs_in_a_linked_account(): void
    {
        $admin = $this->admin();
        $this->link($admin, provider: 'telegram', id: '424242');
        $this->provider('telegram', secret: self::BOT);
        $location = (string) $this->get(route('admin.social.redirect', ['provider' => 'telegram']))->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        parse_str((string) parse_url($query['return_to'], PHP_URL_QUERY), $back);

        $this->get($query['return_to'])
            ->assertOk()
            ->assertSee('tgAuthResult', false)
            ->assertSee('name="state" value="'.$back['state'].'"', false)
            ->assertSee('action="'.route('admin.social.return', ['provider' => 'telegram']).'"', false);
        $this->assertGuest('admin');

        $this->post(route('admin.social.return', ['provider' => 'telegram']), [...$this->signed(['id' => 424242, 'first_name' => 'Ann', 'username' => 'ann']), 'state' => $back['state']])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_telegram_data_with_a_wrong_signature_or_too_old_is_refused(): void
    {
        $admin = $this->admin();
        $this->link($admin, provider: 'telegram', id: '424242');
        $this->provider('telegram', secret: self::BOT);

        foreach ([
            'forged' => [...$this->signed(['id' => 424242]), 'id' => '424243'],
            'other bot' => $this->signed(['id' => 424242], '999:other'),
            'old' => $this->signed(['id' => 424242], auth: Carbon::now()->subSeconds(601)->getTimestamp()),
        ] as $case => $data) {
            $location = (string) $this->get(route('admin.social.redirect', ['provider' => 'telegram']))->headers->get('Location');
            parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
            parse_str((string) parse_url($query['return_to'], PHP_URL_QUERY), $back);

            $this->post(route('admin.social.return', ['provider' => 'telegram']), [...$data, 'state' => $back['state']])
                ->assertSessionHasErrors(['email' => __('atrium-social::social.refused.failed', ['provider' => 'Telegram'])]);
            $this->assertGuest('admin');
        }
    }

    public function test_an_unlinked_telegram_account_cannot_find_anybody_it_has_no_address(): void
    {
        $this->admin();
        $this->provider('telegram', secret: self::BOT);
        $location = (string) $this->get(route('admin.social.redirect', ['provider' => 'telegram']))->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        parse_str((string) parse_url($query['return_to'], PHP_URL_QUERY), $back);

        $this->post(route('admin.social.return', ['provider' => 'telegram']), [...$this->signed(['id' => 7]), 'state' => $back['state']])
            ->assertSessionHasErrors(['email' => __('atrium-social::social.refused.no_email', ['provider' => 'Telegram'])]);
        $this->assertSame(0, SocialAccount::query()->count());
    }

    /**
     * Data as Telegram signs it.
     *
     * @param  array<string,int|string>  $data
     * @return array<string,string>
     */
    private function signed(array $data, string $bot = self::BOT, ?int $auth = null): array
    {
        $data['auth_date'] = $auth ?? Carbon::now()->getTimestamp();
        ksort($data);
        $check = implode("\n", array_map(fn ($k, $v): string => $k.'='.$v, array_keys($data), $data));
        $data['hash'] = hash_hmac('sha256', $check, hash('sha256', $bot, true));

        return array_map('strval', $data);
    }
}
