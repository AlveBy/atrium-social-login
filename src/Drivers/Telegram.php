<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Drivers;

use Illuminate\Support\Carbon;
use Laravel\Socialite\Two\User;

/**
 * Telegram Login: not OAuth. Telegram signs what it tells about the person
 * with a key made from the bot's token; the signature and its age are
 * checked here (core.telegram.org/widgets/login#checking-authorization).
 */
final class Telegram
{
    /** A sign-in older than this is not accepted, in seconds. */
    public const MAX_AGE = 600;

    public function __construct(
        private readonly string $botToken,
    ) {}

    /**
     * The page at Telegram that asks the person and sends them back.
     */
    public function authUrl(string $origin, string $returnTo): string
    {
        return 'https://oauth.telegram.org/auth?'.http_build_query([
            'bot_id' => strtok($this->botToken, ':'),
            'origin' => $origin,
            'return_to' => $returnTo,
            'request_access' => 'write',
        ]);
    }

    /**
     * The person, when the data is signed by this bot and fresh; null otherwise.
     *
     * @param  array<string,mixed>  $data
     */
    public function user(array $data): ?User
    {
        $hash = $data['hash'] ?? null;
        unset($data['hash']);

        if (! is_string($hash) || ! isset($data['id'], $data['auth_date']) || $this->botToken === '') {
            return null;
        }

        ksort($data);
        $check = implode("\n", array_map(fn ($k, $v): string => $k.'='.(is_bool($v) ? ($v ? 'true' : 'false') : $v), array_keys($data), $data));
        $expected = hash_hmac('sha256', $check, hash('sha256', $this->botToken, true));

        if (! hash_equals($expected, $hash)) {
            return null;
        }

        $age = Carbon::now()->getTimestamp() - (int) $data['auth_date'];

        if ($age > self::MAX_AGE || $age < -60) {
            return null;
        }

        return (new User)->setRaw($data)->map([
            'id' => (string) $data['id'],
            'nickname' => $data['username'] ?? null,
            'name' => trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')),
            'email' => null,
            'avatar' => $data['photo_url'] ?? null,
        ]);
    }
}
