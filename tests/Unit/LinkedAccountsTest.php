<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Tests\Unit;

use Atrium\SocialLogin\Models\SocialAccount;
use Atrium\SocialLogin\Services\Accounts;
use Atrium\SocialLogin\Tests\TestCase;
use Laravel\Socialite\Two\User;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * What is kept of an account at a provider, and how many ways in a person
 * has besides one of them.
 */
final class LinkedAccountsTest extends TestCase
{
    /**
     * @param  array<string,mixed>  $attributes
     */
    private function at(array $attributes): User
    {
        return (new User)->setRaw([])->map(['id' => 1001, 'email' => null, 'name' => null, 'nickname' => null, ...$attributes]);
    }

    private function accounts(): Accounts
    {
        return $this->app->make(Accounts::class);
    }

    public function test_linking_keeps_the_id_as_text_and_the_address_and_name_without_spaces(): void
    {
        $person = $this->person();

        $account = $this->accounts()->link('people', $person, 'github', $this->at(['email' => ' ann@example.com ', 'name' => '  Ann Lee  ', 'nickname' => 'ann']));

        $this->assertSame('1001', $account->provider_user_id);
        $fresh = SocialAccount::query()->sole();
        $this->assertSame(['people', $person->id, 'github', '1001', 'ann@example.com', 'Ann Lee'], [$fresh->guard, $fresh->user_id, $fresh->provider, $fresh->provider_user_id, $fresh->email, $fresh->name]);
        $this->assertNotNull($fresh->last_used_at);
    }

    /**
     * @return array<string,array{string|null,string|null,string|null}>
     */
    public static function names(): array
    {
        return [
            'a name' => ['Ann', 'ann', 'Ann'],
            'no name: the nickname' => [null, 'ann', 'ann'],
            'an empty name: the nickname' => ['', 'ann', 'ann'],
            'only spaces: nothing' => ['   ', 'ann', null],
            'neither' => [null, null, null],
        ];
    }

    #[DataProvider('names')]
    public function test_the_name_kept_is_the_name_else_the_nickname(?string $name, ?string $nickname, ?string $kept): void
    {
        $account = $this->accounts()->link('people', $this->person(), 'github', $this->at(['name' => $name, 'nickname' => $nickname]));

        $this->assertSame($kept, $account->fresh()?->name);
    }

    public function test_a_long_name_is_cut_to_250_characters_and_one_of_250_is_kept_whole(): void
    {
        $person = $this->person();

        $long = $this->accounts()->link('people', $person, 'github', $this->at(['name' => str_repeat('a', 300), 'email' => str_repeat('b', 250)]));
        $this->assertSame([str_repeat('a', 250), str_repeat('b', 250)], [$long->fresh()?->name, $long->fresh()?->email]);
    }

    public function test_each_sign_in_refreshes_the_address_and_the_name(): void
    {
        $account = $this->link($this->person(), 'people');
        $account->forceFill(['email' => 'old@example.com', 'name' => 'Old'])->save();

        $this->accounts()->touch($account, $this->at(['email' => 'new@example.com', 'name' => 'New']));

        $fresh = $account->fresh();
        $this->assertSame(['new@example.com', 'New'], [$fresh?->email, $fresh?->name]);
        $this->assertNotNull($fresh?->last_used_at);
    }

    public function test_a_sign_in_that_says_nothing_of_the_person_keeps_what_was_known(): void
    {
        $account = $this->link($this->person(), 'people');
        $account->forceFill(['email' => 'old@example.com', 'name' => 'Old'])->save();

        $this->accounts()->touch($account, $this->at([]));

        $fresh = $account->fresh();
        $this->assertSame(['old@example.com', 'Old'], [$fresh?->email, $fresh?->name]);
        $this->assertNotNull($fresh?->last_used_at);
    }

    /**
     * @return array<string,array{bool,int,int}>
     */
    public static function ways(): array
    {
        return [
            'a password alone' => [true, 0, 1],
            'a password and another account' => [true, 1, 2],
            'two other accounts' => [false, 2, 2],
            'nothing else' => [false, 0, 0],
        ];
    }

    #[DataProvider('ways')]
    public function test_the_other_ways_in_are_counted(bool $password, int $others, int $ways): void
    {
        $person = $this->person(['password' => $password ? 'person-password' : null]);
        $this->link($this->person(['email' => 'someone@example.org']), 'people', 'gitlab', '9');
        $this->link($person, 'admin', 'gitlab', '8');
        $account = $this->link($person, 'people', 'github', '1');

        for ($i = 0; $i < $others; $i++) {
            $this->link($person, 'people', 'other-'.$i, (string) $i);
        }

        $this->assertSame($ways, $this->accounts()->otherWaysIn('people', $person, $account));
    }

    public function test_the_person_s_id_reads_as_a_number(): void
    {
        $this->assertSame(5, (new SocialAccount(['user_id' => '5']))->user_id);
    }
}
