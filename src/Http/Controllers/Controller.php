<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\SocialLogin\Http\Controllers;

use Atrium\SocialLogin\Guards;
use Atrium\SocialLogin\Providers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    public function __construct(
        protected readonly Guards $guards,
        protected readonly Providers $providers,
    ) {}

    protected function guard(Request $request): string
    {
        return (string) $request->route('guard');
    }

    /**
     * The panel's own guard: its pages are Inertia pages in the panel's
     * layout, the other guards' are Blade pages in the site's.
     */
    protected function inPanel(Request $request): bool
    {
        return $this->guard($request) === $this->guards->adminGuard();
    }

    protected function user(Request $request): Authenticatable
    {
        $user = Auth::guard($this->guard($request))->user();
        abort_if($user === null, 403);

        return $user;
    }

    /**
     * @param  array<string,mixed>  $data
     */
    protected function view(Request $request, string $view, array $data = []): View
    {
        $guard = $this->guard($request);

        return view("atrium-social::{$view}", $data + [
            'guard' => $guard,
            'layout' => $this->guards->config($guard, 'layout', 'atrium-social::layouts.site'),
            'guards' => $this->guards,
            'providers' => $this->providers,
        ]);
    }
}
