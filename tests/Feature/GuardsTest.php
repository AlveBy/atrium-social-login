<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Tests\Feature;

use Atrium\Admin\Facades\Setting;
use Atrium\SocialLogin\Guards;
use Atrium\SocialLogin\Tests\TestCase;

/**
 * The guards as config/atrium-social.php describes them: which count, their
 * addresses, the e-mail domains they let in and the people they find.
 */
final class GuardsTest extends TestCase
{
    private function guards(): Guards
    {
        return $this->app->make(Guards::class);
    }

    public function test_no_guards_set_means_none_count(): void
    {
        config(['atrium-social.guards' => null]);

        $this->assertSame([], $this->guards()->names());
    }

    public function test_a_guard_that_does_not_say_it_is_enabled_does_not_count(): void
    {
        config([
            'auth.guards.club' => ['driver' => 'session', 'provider' => 'people'],
            'atrium-social.guards.club' => ['prefix' => 'club/social'],
        ]);

        $this->assertSame(['admin', 'people'], $this->guards()->names());
        $this->assertFalse($this->guards()->has('club'));
    }

    public function test_a_guard_named_by_digits_is_still_a_name(): void
    {
        config([
            'auth.guards.7' => ['driver' => 'session', 'provider' => 'people'],
            'atrium-social.guards' => ['7' => ['enabled' => true]],
        ]);

        $this->assertSame(['7'], $this->guards()->names());
        $this->assertTrue($this->guards()->has('7'));
    }

    public function test_a_guard_off_the_multilingual_site_has_one_address_in_every_language(): void
    {
        $this->app->setLocale('de');

        $this->assertSame(route('people.login'), $this->guards()->url('people', 'people.login'));
        $this->assertSame(route('people.login'), $this->guards()->loginUrl('people'));
    }

    public function test_a_guard_that_does_not_say_whether_it_is_localized_is_not(): void
    {
        config(['atrium-social.guards.people' => array_diff_key((array) config('atrium-social.guards.people'), ['localized' => true])]);
        $this->app->setLocale('de');

        $this->assertSame(route('people.login'), $this->guards()->url('people', 'people.login'));
    }

    public function test_the_domains_are_read_from_a_list_or_text_cleaned_and_each_kept_once(): void
    {
        Setting::save('social_login', ['admin_domains' => " , EXAMPLE.com;example.com\n@Corp.Test, ПРИМЕР.РФ"]);

        $this->assertSame(['example.com', 'corp.test', 'пример.рф'], $this->guards()->allowedDomains('admin'));
    }

    public function test_a_setting_of_only_spaces_keeps_the_domains_of_config(): void
    {
        config(['atrium-social.guards.admin.allowed_domains' => ['corp.test']]);
        Setting::save('social_login', ['admin_domains' => "  \n "]);
        $this->assertSame("  \n ", Setting::get('social_login.admin_domains'));

        $this->assertSame(['corp.test'], $this->guards()->allowedDomains('admin'));
        $this->assertFalse($this->guards()->domainAllowed('admin', 'a@example.com'));
    }

    public function test_one_domain_in_config_may_be_given_as_text(): void
    {
        config(['atrium-social.guards.admin.allowed_domains' => 'Corp.Test']);

        $this->assertSame(['corp.test'], $this->guards()->allowedDomains('admin'));
        $this->assertTrue($this->guards()->domainAllowed('admin', 'a@corp.test'));
    }

    public function test_a_domain_in_another_alphabet_matches_in_any_case(): void
    {
        config(['atrium-social.guards.admin.allowed_domains' => ['пример.рф']]);

        $this->assertTrue($this->guards()->domainAllowed('admin', 'Anna@ПРИМЕР.РФ'));
        $this->assertFalse($this->guards()->domainAllowed('admin', 'anna@example.рф'));
        $this->assertFalse($this->guards()->domainAllowed('admin', 'no address at all'));
    }

    public function test_a_guard_whose_people_are_nowhere_finds_nobody(): void
    {
        config(['auth.guards.club' => ['driver' => 'session', 'provider' => 'nowhere']]);
        $this->person();

        $this->assertNull($this->guards()->find('club', 1));
        $this->assertNull($this->guards()->findByEmail('club', 'olga@example.org'));
    }

    public function test_a_person_is_found_by_an_address_with_spaces_and_capitals_in_any_alphabet(): void
    {
        $olga = $this->person();
        $anna = $this->person(['name' => 'Anna', 'email' => 'анна@пример.рф']);

        $this->assertTrue($olga->is($this->guards()->findByEmail('people', '  OLGA@Example.org ')));
        $this->assertTrue($anna->is($this->guards()->findByEmail('people', 'АННА@ПРИМЕР.РФ')));
        $this->assertNull($this->guards()->findByEmail('people', 'ivan@example.org'));
    }

    public function test_only_an_active_person_is_found_by_id(): void
    {
        $active = $this->person(['active' => true]);
        $inactive = $this->person(['email' => 'off@example.org', 'active' => false]);

        $this->assertTrue($active->is($this->guards()->find('people', $active->id)));
        $this->assertNull($this->guards()->find('people', $inactive->id));
        $this->assertSame([true, false], [$this->guards()->active('people', $active), $this->guards()->active('people', $inactive)]);
    }
}
