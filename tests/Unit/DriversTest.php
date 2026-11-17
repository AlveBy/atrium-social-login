<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Tests\Unit;

use Atrium\SocialLogin\Providers;
use Atrium\SocialLogin\Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use Laravel\Socialite\Two\User;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * What the OpenID Connect and VK ID drivers make of the person a server
 * describes.
 */
final class DriversTest extends TestCase
{
    /**
     * @return array<string,array{array<string,mixed>,string,string}>
     */
    public static function oidcPeople(): array
    {
        return [
            'a number for an id, a given and a family name' => [['sub' => 42, 'given_name' => 'Ann', 'family_name' => 'Lee'], '42', 'Ann Lee'],
            'a given name only' => [['sub' => 'u-1', 'given_name' => 'Ann'], 'u-1', 'Ann'],
            'a family name only' => [['sub' => 'u-1', 'family_name' => 'Lee'], 'u-1', 'Lee'],
            'a full name wins' => [['sub' => 'u-1', 'name' => 'Ann B. Lee', 'given_name' => 'Ann', 'family_name' => 'Lee'], 'u-1', 'Ann B. Lee'],
        ];
    }

    /**
     * @param  array<string,mixed>  $info
     */
    #[DataProvider('oidcPeople')]
    public function test_an_openid_connect_server_describes_the_person(array $info, string $id, string $name): void
    {
        $this->provider('oidc', settings: ['issuer' => 'https://sso.example.test']);
        $this->http->append(
            new Response(200, [], (string) json_encode(['userinfo_endpoint' => 'https://sso.example.test/userinfo'])),
            new Response(200, [], (string) json_encode([...$info, 'email' => 'ann@example.com', 'preferred_username' => 'ann', 'picture' => 'https://sso.example.test/a.png'])),
        );

        $user = $this->app->make(Providers::class)->make('oidc', 'https://site.example.test/back')->userFromToken('oidc-token');

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame([$id, $name, 'ann', 'ann@example.com', 'https://sso.example.test/a.png'], [$user->getId(), $user->getName(), $user->getNickname(), $user->getEmail(), $user->getAvatar()]);
        $this->assertSame('Bearer oidc-token', $this->sent[1]['request']->getHeaderLine('Authorization'));
    }

    /**
     * @return array<string,array{array<string,mixed>,string,string}>
     */
    public static function vkPeople(): array
    {
        return [
            'a number for an id and both names' => [['user_id' => 777, 'first_name' => 'Ivan', 'last_name' => 'P'], '777', 'Ivan P'],
            'a first name only' => [['user_id' => '777', 'first_name' => 'Ivan'], '777', 'Ivan'],
            'a last name only' => [['user_id' => '777', 'last_name' => 'P'], '777', 'P'],
        ];
    }

    /**
     * @param  array<string,mixed>  $info
     */
    #[DataProvider('vkPeople')]
    public function test_vk_id_describes_the_person(array $info, string $id, string $name): void
    {
        $this->provider('vkid');
        $this->http->append(new Response(200, [], (string) json_encode(['user' => [...$info, 'email' => 'ivan@example.com', 'avatar' => 'https://vk.example.test/a.png']])));

        $user = $this->app->make(Providers::class)->make('vkid', 'https://site.example.test/back')->userFromToken('vk-token');

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame([$id, $name, 'ivan@example.com', 'https://vk.example.test/a.png'], [$user->getId(), $user->getName(), $user->getEmail(), $user->getAvatar()]);
    }

    public function test_vk_id_saying_nothing_of_the_person_is_nobody_in_particular(): void
    {
        $this->provider('vkid');
        $this->http->append(new Response(200, [], (string) json_encode(['error' => 'invalid_token'])));

        $user = $this->app->make(Providers::class)->make('vkid', 'https://site.example.test/back')->userFromToken('vk-token');

        $this->assertSame(['', ''], [$user->getId(), $user->getName()]);
    }
}
