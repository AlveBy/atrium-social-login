<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Tests\Unit;

use Atrium\SocialLogin\Drivers\Telegram;
use Atrium\SocialLogin\Tests\TestCase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;

final class TelegramTest extends TestCase
{
    private const BOT = '123456:test-bot-token';

    /**
     * @param  array<string,int|string>  $data
     * @return array<string,string>
     */
    private function signed(array $data, string $bot = self::BOT): array
    {
        ksort($data);
        $check = implode("\n", array_map(fn ($k, $v): string => $k.'='.$v, array_keys($data), $data));
        $data['hash'] = hash_hmac('sha256', $check, hash('sha256', $bot, true));

        return array_map('strval', $data);
    }

    public function test_signed_fresh_data_is_the_person(): void
    {
        $now = Carbon::now()->getTimestamp();
        $user = (new Telegram(self::BOT))->user($this->signed(['id' => 42, 'first_name' => 'Ann', 'last_name' => 'Lee', 'username' => 'ann', 'photo_url' => 'https://t.me/i/a.jpg', 'auth_date' => $now]));

        $this->assertNotNull($user);
        $this->assertSame(['42', 'Ann Lee', 'ann', null, 'https://t.me/i/a.jpg'], [$user->getId(), $user->getName(), $user->getNickname(), $user->getEmail(), $user->getAvatar()]);
    }

    /**
     * @return array<string,array{int,bool}>
     */
    public static function ages(): array
    {
        return [
            'just now' => [0, true],
            'ten minutes' => [600, true],
            'ten minutes and a second' => [601, false],
            'a clock a minute fast' => [-60, true],
            'from the future' => [-61, false],
        ];
    }

    #[DataProvider('ages')]
    public function test_only_fresh_data_is_accepted(int $age, bool $accepted): void
    {
        $data = $this->signed(['id' => 42, 'auth_date' => Carbon::now()->getTimestamp() - $age]);

        $this->assertSame($accepted, (new Telegram(self::BOT))->user($data) !== null);
    }

    /**
     * @return array<string,array{\Closure(array<string,string>):array<string,mixed>, string}>
     */
    public static function broken(): array
    {
        return [
            'no hash' => [fn (array $d): array => array_diff_key($d, ['hash' => 1]), self::BOT],
            'hash not a string' => [fn (array $d): array => [...$d, 'hash' => ['x']], self::BOT],
            'no id' => [fn (array $d): array => array_diff_key($d, ['id' => 1]), self::BOT],
            'no date' => [fn (array $d): array => array_diff_key($d, ['auth_date' => 1]), self::BOT],
            'a field changed' => [fn (array $d): array => [...$d, 'username' => 'eve'], self::BOT],
            'a field added' => [fn (array $d): array => [...$d, 'admin' => '1'], self::BOT],
            'no bot token set' => [fn (array $d): array => $d, ''],
            'another bot' => [fn (array $d): array => $d, '999:other'],
        ];
    }

    /**
     * @param  \Closure(array<string,string>):array<string,mixed>  $break
     */
    #[DataProvider('broken')]
    public function test_broken_or_foreign_data_is_nobody(\Closure $break, string $bot): void
    {
        $data = $this->signed(['id' => 42, 'username' => 'ann', 'auth_date' => Carbon::now()->getTimestamp()]);

        $this->assertNull((new Telegram($bot))->user($break($data)));
    }

    public function test_data_signed_with_an_empty_token_is_nobody_when_no_token_is_set(): void
    {
        $data = $this->signed(['id' => 42, 'auth_date' => Carbon::now()->getTimestamp()], '');

        $this->assertNull((new Telegram(''))->user($data));
    }

    public function test_the_fields_may_come_in_any_order(): void
    {
        $data = $this->signed(['id' => 42, 'username' => 'ann', 'first_name' => 'Ann', 'auth_date' => Carbon::now()->getTimestamp()]);

        $this->assertNotNull((new Telegram(self::BOT))->user(array_reverse($data, true)));
    }

    public function test_numbers_and_yes_or_no_as_json_gives_them_are_signed_as_text(): void
    {
        $now = Carbon::now()->getTimestamp();
        $hash = $this->signed(['id' => '42', 'auth_date' => (string) $now, 'allows_write_to_pm' => 'true', 'is_premium' => 'false'])['hash'];

        $user = (new Telegram(self::BOT))->user(['id' => 42, 'auth_date' => $now, 'allows_write_to_pm' => true, 'is_premium' => false, 'hash' => $hash]);

        $this->assertNotNull($user);
        $this->assertSame('42', $user->getId());
        $this->assertNull((new Telegram(self::BOT))->user(['id' => 42, 'auth_date' => $now, 'allows_write_to_pm' => false, 'is_premium' => true, 'hash' => $hash]));
    }

    public function test_a_date_that_is_not_a_number_is_too_old(): void
    {
        $data = $this->signed(['id' => 42, 'auth_date' => 'yesterday']);

        $this->assertNull((new Telegram(self::BOT))->user($data));
    }

    public function test_a_first_name_alone_is_the_whole_name(): void
    {
        $user = (new Telegram(self::BOT))->user($this->signed(['id' => 42, 'first_name' => 'Ann', 'auth_date' => Carbon::now()->getTimestamp()]));

        $this->assertSame('Ann', $user?->getName());
        $this->assertSame('Lee', (new Telegram(self::BOT))->user($this->signed(['id' => 42, 'last_name' => 'Lee', 'auth_date' => Carbon::now()->getTimestamp()]))?->getName());
    }

    public function test_the_page_at_telegram_gets_the_bot_number_not_its_token(): void
    {
        $url = (new Telegram(self::BOT))->authUrl('https://site.example.test', 'https://site.example.test/back?state=s');
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame(['123456', 'https://site.example.test', 'https://site.example.test/back?state=s', 'write'], [$query['bot_id'], $query['origin'], $query['return_to'], $query['request_access']]);
        $this->assertStringNotContainsString('test-bot-token', $url);
    }
}
