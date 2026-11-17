<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

use Atrium\SocialLogin\Http\Controllers\AccountsController;
use Atrium\SocialLogin\Http\Controllers\ProvidersController;
use Atrium\SocialLogin\Http\Controllers\SocialController;
use Illuminate\Support\Facades\Route;

/*
 * The routes of one guard; SocialLoginServiceProvider wraps them in its
 * prefix, middleware and route names. $authMiddleware and $isAdmin come
 * from there. The POST a provider sends from its own site is registered
 * there too, without a session.
 */

Route::get('redirect/{provider}', [SocialController::class, 'redirect'])->name('redirect');
Route::get('callback/{provider}', [SocialController::class, 'callback'])->name('callback');
Route::post('callback/{provider}/return', [SocialController::class, 'callback'])->name('return');

Route::middleware($authMiddleware)->group(function () use ($isAdmin): void {
    Route::get('accounts', [AccountsController::class, 'index'])->name('accounts');
    Route::get('link/{provider}', [SocialController::class, 'link'])->name('link');
    Route::delete('accounts/{account}', [AccountsController::class, 'destroy'])->whereNumber('account')->name('unlink');

    if ($isAdmin) {
        Route::get('providers', [ProvidersController::class, 'index'])->name('providers');
        Route::get('providers/{provider}', [ProvidersController::class, 'edit'])->name('providers.edit');
        Route::put('providers/{provider}', [ProvidersController::class, 'update'])->name('providers.update');
    }
});
