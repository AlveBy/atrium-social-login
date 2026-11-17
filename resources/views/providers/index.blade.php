{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@extends($layout)

@section('title', __('atrium-social::social.providers.title'))

@section('asl')
<div class="asl-card asl-wide" dusk="asl-providers">
    <h1>{{ __('atrium-social::social.providers.title') }}</h1>
    <p class="asl-muted">{{ __('atrium-social::social.providers.hint') }}</p>

    @include('atrium-social::partials.status')

    <div style="overflow-x:auto">
        <table class="asl-table">
            <thead>
                <tr>
                    <th scope="col">{{ __('atrium-social::social.providers.provider') }}</th>
                    <th scope="col">{{ __('atrium-social::social.providers.status') }}</th>
                    <th scope="col"><span class="asl-sr">{{ __('atrium-social::social.providers.edit') }}</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($keys as $key)
                    @php($on = array_values(array_filter($names, fn ($name) => $providers->enabled($key, $name))))
                    <tr dusk="asl-provider-{{ $key }}">
                        <td><strong>{{ $providers->label($key) }}</strong></td>
                        <td>
                            @if ($on === [])
                                <span class="asl-muted">{{ $providers->model($key)?->client_id ? __('atrium-social::social.providers.off') : __('atrium-social::social.providers.not_set_up') }}</span>
                            @else
                                <strong>✓ {{ __('atrium-social::social.providers.on') }}:</strong>
                                {{ collect($on)->map(fn ($name) => __('atrium-social::social.guards.'.$name))->join(', ') }}
                            @endif
                        </td>
                        <td style="text-align:right">
                            <a class="asl-btn" href="{{ $guards->route($guard, 'providers.edit', ['provider' => $key]) }}" dusk="asl-edit-{{ $key }}">
                                {{ __('atrium-social::social.providers.edit') }}<span class="asl-sr"> {{ $providers->label($key) }}</span>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
