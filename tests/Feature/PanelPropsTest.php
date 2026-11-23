<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Tests\Feature;

use Atrium\SocialLogin\Models\SocialProvider;
use Atrium\SocialLogin\Providers;
use Atrium\SocialLogin\Tests\TestCase;

/**
 * What the panel's pages are handed, word for word: the Vue pages show it as
 * it comes, so every prop is the page's contract.
 */
final class PanelPropsTest extends TestCase
{
    /**
     * @return array<string,mixed>
     */
    private function props(string $url): array
    {
        return $this->actingAs($this->admin(), 'admin')->get($url)->assertOk()->viewData('page')['props'];
    }

    public function test_an_account_is_unlinked_by_its_own_address_whatever_its_number(): void
    {
        $someone = $this->person();
        $this->link($someone, 'people', 'github', '2002');
        $admin = $this->admin();
        $this->provider('github');
        $account = $this->link($admin);
        $this->assertNotSame(1, $account->id);

        $props = $this->actingAs($admin, 'admin')->get(route('admin.social.accounts'))->viewData('page')['props'];

        $this->assertSame(route('admin.social.unlink', ['account' => $account->id]), $props['linked'][0]['unlink_url']);
        $this->assertSame(trans('atrium-social::social'), $props['t']);
    }

    public function test_the_providers_page_lists_the_guards_each_is_on_for_as_a_plain_list(): void
    {
        $this->provider('github', ['people']);

        $github = collect($this->props(route('admin.social.providers'))['providers'])->firstWhere('key', 'github');

        $this->assertSame(['state' => 'on', 'guards' => [__('atrium-social::social.guards.people')]], ['state' => $github['state'], 'guards' => $github['guards']]);
    }

    public function test_the_form_hands_over_what_is_saved_and_the_guards_in_words(): void
    {
        $this->provider('entra', ['people'], ['tenant' => 'contoso.onmicrosoft.com'], 'very-secret-value');

        $props = $this->withSession(['atrium-social.status' => 'Saved.'])->props(route('admin.social.providers.edit', ['provider' => 'entra']));

        $this->assertSame(trans('atrium-social::social'), $props['t']);
        $this->assertSame('Saved.', $props['status']);
        $this->assertSame('Microsoft Entra ID', $props['provider']['label']);
        $this->assertSame(['people'], $props['provider']['guards']);
        $this->assertSame([
            ['name' => 'admin', 'label' => __('atrium-social::social.guards.admin'), 'redirect' => url('admin/auth/social/callback/entra')],
            ['name' => 'people', 'label' => __('atrium-social::social.guards.people'), 'redirect' => url('people/auth/social/callback/entra')],
        ], $props['names']);
    }

    public function test_a_provider_saved_without_any_secret_has_none_to_keep(): void
    {
        SocialProvider::query()->create(['key' => 'github', 'guards' => ['admin'], 'client_id' => 'test-client']);
        $this->app->make(Providers::class)->refresh();

        $this->assertFalse($this->props(route('admin.social.providers.edit', ['provider' => 'github']))['provider']['has_secret']);
    }
}
