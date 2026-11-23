<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Tests\Feature;

use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Models\AdminRole;
use Atrium\Admin\Models\AdminUser;
use Atrium\SocialLogin\Models\SocialProvider;
use Atrium\SocialLogin\Providers;
use Atrium\SocialLogin\SocialLoginSettings;
use Atrium\SocialLogin\Tests\TestCase;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Social sign-in → Providers: only for whoever may set the module up.
 */
final class SetupPageTest extends TestCase
{
    /**
     * @param  list<string>  $permissions
     */
    private function editor(array $permissions = []): AdminUser
    {
        $editor = $this->admin(['email' => 'editor@example.com']);
        $editor->forceFill(['is_superadmin' => false])->save();
        $role = AdminRole::query()->create(['name' => 'Editors', 'permissions' => $permissions]);
        $editor->roles()->attach($role);

        return $editor->fresh();
    }

    public function test_without_the_permission_the_pages_are_forbidden_and_the_menu_shows_only_one_s_accounts(): void
    {
        $editor = $this->editor();
        $this->actingAs($editor, 'admin');

        $this->get(route('admin.social.providers'))->assertForbidden();
        $this->get(route('admin.social.providers.edit', ['provider' => 'github']))->assertForbidden();
        $this->put(route('admin.social.providers.update', ['provider' => 'github']), ['client_id' => 'x'])->assertForbidden();
        $this->assertSame(0, SocialProvider::query()->count());

        $menu = Hook::apply('atrium.admin.menu', [], $editor);
        $this->assertSame(['admin.social.accounts'], array_column(end($menu)['children'], 'route'));
    }

    public function test_with_the_permission_the_providers_page_opens_in_the_panel_and_is_in_the_menu(): void
    {
        $editor = $this->editor(['social_login.settings']);
        $this->provider('github', ['admin']);
        $this->provider('gitlab', [], secret: '');

        $this->actingAs($editor, 'admin')->get(route('admin.social.providers'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/SocialLogin/Providers', false)
                ->where('t.providers.on', __('atrium-social::social.providers.on'))
                ->where('status', null)
                ->where('providers.0.key', 'google')
                ->where('providers.0.label', 'Google')
                ->where('providers.0.state', 'not_set_up')
                ->where('providers.0.guards', [])
                ->where('providers.0.edit_url', route('admin.social.providers.edit', ['provider' => 'google']))
                ->where('providers.5.key', 'github')
                ->where('providers.5.state', 'on')
                ->where('providers.5.guards', [__('atrium-social::social.guards.admin')])
                ->where('providers.6.key', 'gitlab')
                ->where('providers.6.state', 'off')
                ->where('providers.6.guards', [])
                ->where('providers.6.edit_url', route('admin.social.providers.edit', ['provider' => 'gitlab']))
                ->where('providers', fn ($providers) => count($providers) === count(config('atrium-social.providers'))));

        $menu = Hook::apply('atrium.admin.menu', [], $editor);
        $this->assertSame(['admin.social.accounts', 'admin.social.providers'], array_column(end($menu)['children'], 'route'));
        $this->assertSame([], Hook::apply('atrium.admin.menu', [], null));
    }

    public function test_the_form_shows_the_redirect_urls_to_register_but_never_the_secret(): void
    {
        $this->provider('entra', ['admin'], ['tenant' => 'contoso.onmicrosoft.com'], 'very-secret-value');

        $response = $this->actingAs($this->admin(), 'admin')->get(route('admin.social.providers.edit', ['provider' => 'entra']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/SocialLogin/Provider', false)
                ->where('provider.key', 'entra')
                ->where('provider.client_id', 'test-client')
                ->where('provider.has_secret', true)
                ->where('provider.guards', ['admin'])
                ->where('provider.settings', ['tenant' => 'contoso.onmicrosoft.com'])
                ->where('provider.hint', __('atrium-social::social.providers.hints.entra'))
                ->where('names.0.name', 'admin')
                ->where('names.0.redirect', url('admin/auth/social/callback/entra'))
                ->where('names.1.redirect', url('people/auth/social/callback/entra'))
                ->where('fields', ['tenant'])
                ->where('urls.index', route('admin.social.providers'))
                ->where('urls.update', route('admin.social.providers.update', ['provider' => 'entra'])));
        $this->assertStringNotContainsString('very-secret-value', $response->getContent());
    }

    public function test_a_provider_not_set_up_yet_opens_an_empty_form_without_a_hint(): void
    {
        $this->actingAs($this->admin(), 'admin')->get(route('admin.social.providers.edit', ['provider' => 'github']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('provider.client_id', '')
                ->where('provider.has_secret', false)
                ->where('provider.guards', [])
                ->where('provider.settings', [])
                ->where('provider.hint', null));
    }

    public function test_a_provider_saved_without_a_secret_has_none_to_keep(): void
    {
        $this->provider('github', ['admin'], secret: '');

        $this->actingAs($this->admin(), 'admin')->get(route('admin.social.providers.edit', ['provider' => 'github']))
            ->assertInertia(fn (Assert $page) => $page->where('provider.has_secret', false));
    }

    public function test_the_saved_message_reaches_the_providers_page(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->withSession(['atrium-social.status' => 'Saved.'])
            ->get(route('admin.social.providers'))
            ->assertInertia(fn (Assert $page) => $page->where('status', 'Saved.'));
    }

    public function test_an_unknown_provider_has_no_form(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->get(route('admin.social.providers.edit', ['provider' => 'myspace']))->assertNotFound();
        $this->put(route('admin.social.providers.update', ['provider' => 'myspace']))->assertNotFound();
    }

    public function test_saving_switches_the_provider_on_and_keeps_the_secret_encrypted(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->put(route('admin.social.providers.update', ['provider' => 'gitlab']), [
            'guards' => ['admin', 'people', 'admin'],
            'client_id' => '  app-id  ',
            'client_secret' => 'first-secret',
            'settings' => ['host' => 'https://git.example.test', 'tenant' => 'ignored', 'label' => 'ignored too'],
        ])->assertRedirect(route('admin.social.providers'))
            ->assertSessionHas('atrium-social.status', __('atrium-social::social.providers.saved', ['provider' => 'GitLab']));

        $model = SocialProvider::query()->sole();
        $this->assertSame(['admin', 'people'], $model->guards);
        $this->assertSame('app-id', $model->client_id);
        $this->assertSame(['host' => 'https://git.example.test'], $model->settings);
        $this->assertSame('first-secret', $model->client_secret);
        $this->assertStringNotContainsString('first-secret', (string) DB::table('auth_social_providers')->value('client_secret'));
        $this->assertTrue($this->app->make(Providers::class)->enabled('gitlab', 'people'));

        // An empty secret keeps the saved one; unticking switches it off.
        $this->put(route('admin.social.providers.update', ['provider' => 'gitlab']), ['client_id' => 'app-id', 'client_secret' => '   '])
            ->assertSessionHasNoErrors();
        $model->refresh();
        $this->assertSame('first-secret', $model->client_secret);
        $this->assertSame([], $model->guards);
        $this->assertSame([], $model->settings);
        $this->assertFalse($this->app->make(Providers::class)->enabled('gitlab', 'admin'));
    }

    /**
     * @return array<string,array{array<string,mixed>,string}>
     */
    public static function invalid(): array
    {
        return [
            'unknown guard' => [['guards' => ['root']], 'guards.0'],
            'tenant with a slash' => [['settings' => ['tenant' => 'a/b']], 'settings.tenant'],
            'gitlab over http' => [['settings' => ['host' => 'http://git.example.test']], 'settings.host'],
            'issuer not a url' => [['settings' => ['issuer' => 'sso']], 'settings.issuer'],
            'client id too long' => [['client_id' => str_repeat('a', 501)], 'client_id'],
            'label too long' => [['settings' => ['label' => str_repeat('a', 61)]], 'settings.label'],
            'guards as text' => [['guards' => 'admin'], 'guards'],
            'settings as text' => [['settings' => 'https://git.example.test'], 'settings'],
        ];
    }

    /**
     * @param  array<string,mixed>  $data
     */
    #[DataProvider('invalid')]
    public function test_wrong_values_are_refused(array $data, string $field): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.social.providers.update', ['provider' => 'oidc']), $data)
            ->assertSessionHasErrors($field);

        $this->assertSame(0, SocialProvider::query()->count());
    }

    public function test_a_guard_that_is_not_text_is_told_so(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.social.providers.update', ['provider' => 'github']), ['guards' => [['admin']]])
            ->assertSessionHasErrors(['guards.0' => __('validation.string', ['attribute' => 'guards.0'])]);

        $this->assertSame(0, SocialProvider::query()->count());
    }

    public function test_every_field_may_be_left_empty(): void
    {
        $this->actingAs($this->admin(), 'admin')->put(route('admin.social.providers.update', ['provider' => 'oidc']), [
            'client_id' => null,
            'client_secret' => null,
            'settings' => ['tenant' => null, 'host' => null, 'issuer' => null, 'label' => null],
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.social.providers'));

        $model = SocialProvider::query()->sole();
        $this->assertSame(['', [], [], null], [$model->client_id, $model->guards, $model->settings, $model->client_secret]);
    }

    public function test_a_guard_ticked_twice_is_kept_once_in_a_list(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.social.providers.update', ['provider' => 'github']), ['guards' => ['admin', 'admin', 'people']])
            ->assertSessionHasNoErrors();

        $this->assertSame(['admin', 'people'], SocialProvider::query()->sole()->guards);
    }

    public function test_spaces_are_trimmed_even_where_the_site_does_not_trim_its_input(): void
    {
        $this->withoutMiddleware([TrimStrings::class, ConvertEmptyStringsToNull::class]);
        $this->actingAs($this->admin(), 'admin');
        $url = route('admin.social.providers.update', ['provider' => 'oidc']);

        $this->put($url, [
            'client_id' => '  app-id  ',
            'client_secret' => '  first-secret  ',
            'settings' => ['issuer' => 'https://sso.example.test', 'label' => '   '],
        ])->assertSessionHasNoErrors();
        $model = SocialProvider::query()->sole();
        $this->assertSame(['app-id', 'first-secret', ['issuer' => 'https://sso.example.test']], [$model->client_id, $model->client_secret, $model->settings]);

        $this->put($url, ['client_id' => 'app-id', 'client_secret' => '   '])->assertSessionHasNoErrors();
        $this->assertSame('first-secret', $model->refresh()->client_secret);
    }

    public function test_saving_is_logged(): void
    {
        $events = [];
        Hook::on('atrium.auth.event', function (...$args) use (&$events): void {
            $events[] = [$args[2], $args[3], $args[4]];
        });

        $this->actingAs($this->admin(), 'admin')->put(route('admin.social.providers.update', ['provider' => 'github']), ['guards' => ['admin']]);

        $this->assertSame([['social_provider_saved', 'social:github', ['guards' => ['admin']]]], $events);
    }

    public function test_the_module_settings_ask_for_the_domains_of_every_guard(): void
    {
        $settings = $this->app->make(SocialLoginSettings::class);

        $this->assertSame(__('atrium-social::social.settings.description'), $settings->description());
        $this->assertSame(['admin_domains', 'people_domains'], array_map(fn ($field) => $field->getName(), $settings->fields()));
        $admin = $settings->fields()[0];
        $this->assertSame(__('atrium-social::social.settings.domains', ['who' => __('atrium-social::social.guards.admin')]), $admin->getLabel());
        $this->assertSame('Allowed e-mail domains: Admin panel', $admin->getLabel());
        $this->assertSame(['nullable', 'max:2000', 'string'], $admin->getValidationRules());
    }
}
