<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The providers as set up in the panel: where they are on, and the
        // app registered at the provider. The secret is encrypted.
        Schema::create('auth_social_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 40)->unique();
            $table->json('guards')->nullable();
            $table->string('client_id', 500)->nullable();
            $table->text('client_secret')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // Accounts at providers, linked to a person of a guard.
        Schema::create('auth_social_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('guard', 40);
            $table->unsignedBigInteger('user_id');
            $table->string('provider', 40);
            $table->string('provider_user_id', 191);
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['guard', 'provider', 'provider_user_id']);
            $table->index(['guard', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_social_accounts');
        Schema::dropIfExists('auth_social_providers');
    }
};
