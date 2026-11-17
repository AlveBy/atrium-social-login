{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@extends($layout)

@section('title', __('atrium-social::social.accounts.title'))

@section('asl')
<div class="asl-card" dusk="asl-accounts">
    <h1>{{ __('atrium-social::social.accounts.title') }}</h1>
    <p class="asl-muted">{{ __('atrium-social::social.accounts.hint') }}</p>

    @include('atrium-social::partials.status')

    <h2>{{ __('atrium-social::social.accounts.linked_title') }}</h2>
    @if ($linked->isEmpty())
        <p class="asl-muted" dusk="asl-none">{{ __('atrium-social::social.accounts.none') }}</p>
    @else
        <ul class="asl-list">
            @foreach ($linked as $account)
                <li dusk="asl-linked-{{ $account->provider }}">
                    <div>
                        <strong>{{ $providers->label($account->provider) }}</strong>
                        @if ($account->email || $account->name)
                            <div class="asl-small">{{ $account->email ?: $account->name }}</div>
                        @endif
                        <div class="asl-small asl-muted">
                            {{ __('atrium-social::social.accounts.last_used') }}:
                            {{ $account->last_used_at?->isoFormat('LLL') ?? __('atrium-social::social.accounts.never') }}
                        </div>
                    </div>
                    <form method="POST" action="{{ $guards->route($guard, 'unlink', ['account' => $account->id]) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="asl-btn" dusk="asl-unlink-{{ $account->provider }}">
                            {{ __('atrium-social::social.accounts.unlink') }}<span class="asl-sr"> {{ $providers->label($account->provider) }}</span>
                        </button>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($available !== [])
        <div class="asl-section">
            <h2>{{ __('atrium-social::social.accounts.available') }}</h2>
            <ul class="asl-methods">
                @foreach ($available as $key)
                    <li>
                        <a class="asl-btn asl-block" href="{{ $guards->route($guard, 'link', ['provider' => $key]) }}" dusk="asl-link-{{ $key }}">
                            {{ __('atrium-social::social.accounts.link', ['provider' => $providers->label($key)]) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection
