<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Tests\Feature;

use Atrium\SocialLogin\Drivers\OidcProvider;
use Atrium\SocialLogin\Models\SocialAccount;
use Atrium\SocialLogin\Models\SocialProvider;
use Atrium\SocialLogin\Providers;
use Atrium\SocialLogin\Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Two\User as SocialiteUser;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The providers themselves: where each sends people, which addresses each
 * vouches for, and the generic OpenID Connect and VK ID drivers end to end.
 */
final class ProvidersTest extends TestCase
{
    /**
     * @return array<string,array{string,array<string,string>,string}>
     */
    public static function authorizeUrls(): array
    {
        return [
            'google' => ['google', [], 'https://accounts.google.com/o/oauth2/auth?'],
            'microsoft: personal and work' => ['microsoft', [], 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?'],
            'entra: one tenant' => ['entra', ['tenant' => 'contoso.onmicrosoft.com'], 'https://login.microsoftonline.com/contoso.onmicrosoft.com/oauth2/v2.0/authorize?'],
            'entra: any organisation' => ['entra', [], 'https://login.microsoftonline.com/organizations/oauth2/v2.0/authorize?'],
            'yandex' => ['yandex', [], 'https://oauth.yandex.ru/authorize?'],
            'github' => ['github', [], 'https://github.com/login/oauth/authorize?'],
            'gitlab.com' => ['gitlab', [], 'https://gitlab.com/oauth/authorize?'],
            'self-hosted gitlab' => ['gitlab', ['host' => 'https://git.example.test/'], 'https://git.example.test/oauth/authorize?'],
            'facebook' => ['facebook', [], 'https://www.facebook.com/'],
            'vk id' => ['vkid', [], 'https://id.vk.com/authorize?'],
            'mail.ru' => ['mailru', [], 'https://oauth.mail.ru/login?'],
            'linkedin' => ['linkedin', [], 'https://www.linkedin.com/oauth/v2/authorization?'],
            'apple' => ['apple', [], 'https://appleid.apple.com/auth/authorize?'],
        ];
    }

    /**
     * @param  array<string,string>  $settings
     */
    #[DataProvider('authorizeUrls')]
    public function test_each_provider_sends_people_to_its_own_page(string $key, array $settings, string $prefix): void
    {
        $this->provider($key, settings: $settings);

        $location = (string) $this->get(route('admin.social.redirect', ['provider' => $key]))->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertStringStartsWith($prefix, $location);
        $this->assertSame('test-client', $query['client_id']);
        $this->assertSame(route('admin.social.callback', ['provider' => $key]), $query['redirect_uri']);
        $this->assertNotEmpty($query['state']);
    }

    /**
     * @return array<string,array{string,array<string,mixed>,string|null,bool}>
     */
    public static function addresses(): array
    {
        return [
            'google verified' => ['google', ['email_verified' => true], 'a@example.com', true],
            'google verified as text' => ['google', ['email_verified' => 'true'], 'a@example.com', true],
            'google verified as 1' => ['google', ['email_verified' => 1], 'a@example.com', true],
            'google verified as "1"' => ['google', ['email_verified' => '1'], 'a@example.com', true],
            'google not verified as 0' => ['google', ['email_verified' => 0], 'a@example.com', false],
            'google not verified as 2' => ['google', ['email_verified' => 2], 'a@example.com', false],
            'google not verified' => ['google', ['email_verified' => false], 'a@example.com', false],
            'google silent' => ['google', [], 'a@example.com', false],
            'oidc verified' => ['oidc', ['email_verified' => true], 'a@example.com', true],
            'oidc not verified' => ['oidc', ['email_verified' => 'false'], 'a@example.com', false],
            'apple verified' => ['apple', ['email_verified' => 'true'], 'a@privaterelay.appleid.com', true],
            'linkedin verified' => ['linkedin', ['email_verified' => true], 'a@example.com', true],
            'github primary verified' => ['github', [], 'a@example.com', true],
            'gitlab confirmed' => ['gitlab', ['confirmed_at' => '2026-01-01T00:00:00Z'], 'a@example.com', true],
            'gitlab unconfirmed' => ['gitlab', ['confirmed_at' => null], 'a@example.com', false],
            'facebook' => ['facebook', [], 'a@example.com', true],
            'yandex mailbox' => ['yandex', [], 'a@yandex.ru', true],
            'yandex ya.ru' => ['yandex', [], 'a@YA.RU', true],
            'yandex foreign address' => ['yandex', [], 'a@example.com', false],
            'yandex lookalike' => ['yandex', [], 'a@yandex.ru.example.com', false],
            'mail.ru mailbox' => ['mailru', [], 'a@inbox.ru', true],
            'mail.ru foreign address' => ['mailru', [], 'a@gmail.com', false],
            'mail.ru in capitals' => ['mailru', [], 'a@INBOX.RU', true],
            'mail.ru lookalike' => ['mailru', [], 'a@mail.ru.example.com', false],
            'microsoft never' => ['microsoft', ['email_verified' => true], 'a@example.com', false],
            'entra never' => ['entra', ['email_verified' => true], 'a@example.com', false],
            'vk id never' => ['vkid', ['verified' => true], 'a@example.com', false],
            'no address' => ['google', ['email_verified' => true], null, false],
            'empty address' => ['github', [], '', false],
        ];
    }

    /**
     * @param  array<string,mixed>  $raw
     */
    #[DataProvider('addresses')]
    public function test_only_addresses_a_provider_vouches_for_count(string $key, array $raw, ?string $email, bool $verified): void
    {
        $user = (new SocialiteUser)->setRaw($raw)->map(['id' => '1', 'email' => $email]);

        $this->assertSame($verified, $this->app->make(Providers::class)->verifiedEmail($key, $user));
    }

    public function test_an_openid_connect_server_signs_in_by_its_discovery_document(): void
    {
        $admin = $this->admin();
        $this->provider('oidc', settings: ['issuer' => 'https://sso.example.test/realms/main', 'label' => 'Company SSO']);
        $discovery = new Response(200, [], (string) json_encode([
            'authorization_endpoint' => 'https://sso.example.test/auth',
            'token_endpoint' => 'https://sso.example.test/token',
            'userinfo_endpoint' => 'https://sso.example.test/userinfo',
        ]));
        $this->http->append($discovery);

        $location = (string) $this->get(route('admin.social.redirect', ['provider' => 'oidc']))->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertStringStartsWith('https://sso.example.test/auth?', $location);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame('openid email profile', $query['scope']);

        $this->http->append(
            new Response(200, [], (string) json_encode(['access_token' => 'oidc-token'])),
            new Response(200, [], (string) json_encode(['sub' => 'u-1', 'email' => 'admin@example.com', 'email_verified' => true, 'name' => 'Admin'])),
        );
        $this->back('admin.social.', $query['state'], 'oidc')->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertSame('u-1', SocialAccount::query()->sole()->provider_user_id);
        $this->assertSame('https://sso.example.test/realms/main/.well-known/openid-configuration', (string) $this->sent[0]['request']->getUri());
        $this->assertSame('Bearer oidc-token', $this->sent[2]['request']->getHeaderLine('Authorization'));
        parse_str((string) $this->sent[1]['request']->getBody(), $token);
        $this->assertGreaterThanOrEqual(43, strlen($token['code_verifier'] ?? ''), 'PKCE');
        $this->assertSame('Company SSO', $this->app->make(Providers::class)->label('oidc'));
    }

    /**
     * @return array<string,array{string,string,string,int}>
     */
    public static function badIssuers(): array
    {
        $good = '{"authorization_endpoint":"https://sso.example.test/auth"}';
        $unlisted = 'The OpenID Connect server lists no authorization_endpoint.';

        return [
            'plain http' => ['http://sso.example.test', $good, 'The OpenID Connect issuer must be an https:// address.', 0],
            'localhost lookalike' => ['https:/localhost', $good, 'The OpenID Connect issuer must be an https:// address.', 0],
            'no issuer' => ['', $good, 'The OpenID Connect issuer must be an https:// address.', 0],
            'no endpoint listed' => ['https://sso.example.test', '{}', $unlisted, 1],
            'endpoint over http' => ['https://sso.example.test', '{"authorization_endpoint":"http://sso.example.test/auth"}', $unlisted, 1],
            'not a document' => ['https://sso.example.test', 'down for maintenance', $unlisted, 1],
            'endpoint not an address' => ['https://sso.example.test', '{"authorization_endpoint":5}', $unlisted, 1],
        ];
    }

    #[DataProvider('badIssuers')]
    public function test_an_unsafe_openid_connect_server_is_refused_on_the_login_screen(string $issuer, string $document, string $why, int $asked): void
    {
        Log::spy();
        $this->provider('oidc', settings: ['issuer' => $issuer]);
        $this->http->append(new Response(200, [], $document));

        $this->get(route('admin.social.redirect', ['provider' => 'oidc']))
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors(['email' => __('atrium-social::social.refused.failed', ['provider' => 'OpenID Connect'])]);

        $this->assertCount($asked, $this->sent);
        Log::shouldHaveReceived('warning')->once()->with('A social sign-in could not start', ['provider' => 'oidc', 'exception' => $why]);
    }

    public function test_an_openid_connect_server_on_this_machine_may_go_without_https(): void
    {
        $this->provider('oidc', settings: ['issuer' => 'http://localhost:8080/realms/dev']);
        $this->http->append(new Response(200, [], (string) json_encode(['authorization_endpoint' => 'http://localhost:8080/auth'])));

        $location = (string) $this->get(route('admin.social.redirect', ['provider' => 'oidc']))->assertRedirect()->headers->get('Location');

        $this->assertStringStartsWith('http://localhost:8080/auth?', $location);
        $this->assertSame('http://localhost:8080/realms/dev/.well-known/openid-configuration', (string) $this->sent[0]['request']->getUri());
    }

    public function test_the_discovery_document_is_kept_for_an_hour_under_the_issuer_s_own_key(): void
    {
        $this->freezeSecond();
        $this->provider('oidc', settings: ['issuer' => 'https://sso.example.test/realms/main/']);
        $discovery = fn (): Response => new Response(200, [], (string) json_encode(['authorization_endpoint' => 'https://sso.example.test/auth']));
        $this->http->append($discovery(), $discovery());

        $this->get(route('admin.social.redirect', ['provider' => 'oidc']))->assertRedirect();
        $this->assertSame('https://sso.example.test/realms/main/.well-known/openid-configuration', (string) $this->sent[0]['request']->getUri());
        $this->assertTrue(Cache::has('atrium-social.oidc.'.sha1('https://sso.example.test/realms/main')));

        $this->travel(3599)->seconds();
        $this->get(route('admin.social.redirect', ['provider' => 'oidc']))->assertRedirect();
        $this->assertCount(1, $this->sent, 'still kept');

        $this->travel(1)->seconds();
        $this->get(route('admin.social.redirect', ['provider' => 'oidc']))->assertRedirect();
        $this->assertCount(2, $this->sent, 'asked again after an hour');
    }

    public function test_vk_id_gets_back_the_device_and_state_and_signs_in_a_linked_account(): void
    {
        $admin = $this->admin();
        $this->link($admin, provider: 'vkid', id: '777');
        $this->provider('vkid');
        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'vkid']));
        $this->http->append(
            new Response(200, [], (string) json_encode(['access_token' => 'vk-token', 'user_id' => 777])),
            new Response(200, [], (string) json_encode(['user' => ['user_id' => '777', 'first_name' => 'Ivan', 'last_name' => 'P', 'email' => 'admin@example.com']])),
        );

        $this->back('admin.social.', $state, 'vkid', ['device_id' => 'dev-1'])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        parse_str((string) $this->sent[0]['request']->getBody(), $token);
        $this->assertSame(['dev-1', $state, 'code-1'], [$token['device_id'], $token['state'], $token['code']]);
        parse_str((string) $this->sent[1]['request']->getBody(), $info);
        $this->assertSame(['vk-token', 'test-client'], [$info['access_token'], $info['client_id']]);
        $this->assertSame('Ivan P', SocialAccount::query()->sole()->name);
    }

    public function test_a_vk_id_device_sent_as_a_list_is_refused_and_never_passed_on(): void
    {
        $admin = $this->admin();
        $this->link($admin, provider: 'vkid', id: '777');
        $this->provider('vkid');
        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'vkid']));
        $this->http->append(
            new Response(200, [], (string) json_encode(['access_token' => 'vk-token', 'user_id' => 777])),
            new Response(200, [], (string) json_encode(['user' => ['user_id' => '777', 'first_name' => 'Ivan']])),
        );

        $this->back('admin.social.', $state, 'vkid', ['device_id' => ['dev-1']])
            ->assertSessionHasErrors(['email' => __('atrium-social::social.refused.failed', ['provider' => 'VK ID'])]);

        $this->assertGuest('admin');
        $this->assertCount(0, $this->sent);
    }

    public function test_vk_id_alone_never_finds_an_account_by_address(): void
    {
        $this->admin();
        $this->provider('vkid');
        $state = $this->startAt(route('admin.social.redirect', ['provider' => 'vkid']));
        $this->http->append(
            new Response(200, [], (string) json_encode(['access_token' => 'vk-token'])),
            new Response(200, [], (string) json_encode(['user' => ['user_id' => '778', 'email' => 'admin@example.com']])),
        );

        $this->back('admin.social.', $state, 'vkid')->assertSessionHasErrors(['email' => __('atrium-social::social.refused.unverified', ['provider' => 'VK ID'])]);
        $this->assertGuest('admin');
    }

    public function test_the_secret_is_stored_encrypted_and_never_sent_to_the_page(): void
    {
        $this->provider(secret: 'very-secret-value');

        $raw = (string) DB::table('auth_social_providers')->value('client_secret');
        $this->assertStringNotContainsString('very-secret-value', $raw);
        $this->assertSame('very-secret-value', $this->app->make(Providers::class)->model('github')?->client_secret);
        $this->assertArrayNotHasKey('client_secret', $this->app->make(Providers::class)->model('github')?->toArray() ?? []);
    }

    public function test_a_provider_with_a_driver_nobody_knows_is_refused_on_the_login_screen(): void
    {
        Log::spy();
        config(['atrium-social.providers.myoauth' => ['driver' => 'myoauth', 'label' => 'My OAuth']]);
        $this->provider('myoauth');

        $this->get(route('admin.social.redirect', ['provider' => 'myoauth']))
            ->assertSessionHasErrors(['email' => __('atrium-social::social.refused.failed', ['provider' => 'My OAuth'])]);

        Log::shouldHaveReceived('warning')->once()->with('A social sign-in could not start', ['provider' => 'myoauth', 'exception' => 'No OAuth driver [myoauth].']);
    }

    public function test_a_provider_nobody_has_set_up_yet_is_built_with_an_empty_client(): void
    {
        $providers = $this->app->make(Providers::class);

        $entra = $providers->make('entra', 'https://site.example.test/back')->redirect()->getTargetUrl();
        $gitlab = $providers->make('gitlab', 'https://site.example.test/back')->redirect()->getTargetUrl();
        $this->assertStringStartsWith('https://login.microsoftonline.com/organizations/', $entra);
        $this->assertStringStartsWith('https://gitlab.com/oauth/authorize?', $gitlab);
        $this->assertInstanceOf(OidcProvider::class, $providers->make('oidc', 'https://site.example.test/back'));

        $this->http->append(new Response(200, [], (string) json_encode(['access_token' => 'token-1'])));
        $providers->make('github', 'https://site.example.test/back')->getAccessTokenResponse('code-1');
        parse_str((string) $this->sent[0]['request']->getBody(), $token);
        $this->assertSame(['', ''], [$token['client_id'] ?? null, $token['client_secret'] ?? null]);
    }

    public function test_telegram_without_a_bot_token_accepts_nobody(): void
    {
        $data = ['id' => '42', 'auth_date' => (string) time()];
        $data['hash'] = hash_hmac('sha256', "auth_date={$data['auth_date']}\nid=42", hash('sha256', '', true));

        $this->assertNull($this->app->make(Providers::class)->telegram('telegram')->user($data));
    }

    public function test_the_client_is_sent_without_the_spaces_it_was_saved_with(): void
    {
        $this->provider()->forceFill(['client_id' => '  test-client  '])->save();
        $this->app->make(Providers::class)->refresh();

        $this->assertTrue($this->app->make(Providers::class)->enabled('github', 'admin'));
        $location = (string) $this->get(route('admin.social.redirect', ['provider' => 'github']))->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame('test-client', $query['client_id']);
    }

    /**
     * @return array<string,array{array<string,mixed>}>
     */
    public static function unfinished(): array
    {
        return [
            'a client id of spaces' => [['client_id' => '   ']],
            'no client id' => [['client_id' => null]],
            'no secret' => [['client_secret' => null]],
        ];
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    #[DataProvider('unfinished')]
    public function test_a_provider_not_fully_set_up_is_off(array $attributes): void
    {
        $this->provider()->forceFill($attributes)->save();
        $this->app->make(Providers::class)->refresh();

        $this->assertFalse($this->app->make(Providers::class)->enabled('github', 'admin'));
        $this->get(route('admin.social.redirect', ['provider' => 'github']))->assertNotFound();
    }

    public function test_a_setting_is_read_without_spaces_and_spaces_alone_are_no_setting(): void
    {
        $providers = $this->app->make(Providers::class);

        $this->provider('oidc', settings: ['label' => '  Company SSO  ']);
        $this->assertSame('Company SSO', $providers->label('oidc'));

        SocialProvider::query()->where('key', 'oidc')->sole()->forceFill(['settings' => ['label' => '   ']])->save();
        $providers->refresh();
        $this->assertSame('OpenID Connect', $providers->label('oidc'));
    }

    public function test_the_providers_switched_on_for_a_guard_come_as_a_list(): void
    {
        $this->provider('github', ['admin']);
        $this->provider('linkedin', ['admin']);
        $this->provider('google', ['people']);

        $this->assertSame(['github', 'linkedin'], $this->app->make(Providers::class)->enabledFor('admin'));
        $this->assertSame(['google'], $this->app->make(Providers::class)->enabledFor('people'));
    }

    public function test_keys_settings_and_labels_are_text_however_config_gives_them(): void
    {
        config([
            'atrium-social.providers.42' => ['driver' => 'oidc', 'label' => 42, 'settings' => ['issuer', 'x' => 'label', 7]],
            'atrium-social.providers.myoauth' => ['driver' => 'myoauth'],
        ]);
        $providers = $this->app->make(Providers::class);

        $this->assertContains('42', $providers->keys());
        $this->assertTrue($providers->known('42'));
        $this->assertSame(['issuer', 'label', '7'], $providers->settingsOf('42'));
        $this->assertSame('42', $providers->label('42'));
        $this->assertSame('Myoauth', $providers->label('myoauth'));
        $this->assertSame([], $providers->settingsOf('myoauth'));
    }

    public function test_an_unknown_or_unset_provider_is_off(): void
    {
        $providers = $this->app->make(Providers::class);

        $this->assertFalse($providers->enabled('myspace', 'admin'));
        $this->assertFalse($providers->enabled('github', 'admin'));
        $this->assertSame('GitHub', $providers->label('github'));
        $this->assertSame([], $providers->enabledFor('admin'));
    }
}
