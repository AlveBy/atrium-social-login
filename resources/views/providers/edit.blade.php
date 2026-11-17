{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@extends($layout)

@section('title', $providers->label($key))

@section('asl')
<div class="asl-card asl-wide" dusk="asl-provider-form">
    <p><a class="asl-link" href="{{ $guards->route($guard, 'providers') }}">← {{ __('atrium-social::social.providers.title') }}</a></p>
    <h1>{{ $providers->label($key) }}</h1>
    @if (__('atrium-social::social.providers.hints.'.$key) !== 'atrium-social::social.providers.hints.'.$key)
        <p class="asl-muted">{{ __('atrium-social::social.providers.hints.'.$key) }}</p>
    @endif

    @include('atrium-social::partials.status')

    <section class="asl-stack" aria-labelledby="asl-redirects">
        <h2 id="asl-redirects">{{ __('atrium-social::social.providers.redirect') }}</h2>
        <p class="asl-small asl-muted">{{ __('atrium-social::social.providers.redirect_hint') }}</p>
        @foreach ($names as $name)
            <div>
                <label for="asl-redirect-{{ $name }}">{{ __('atrium-social::social.guards.'.$name) }}</label>
                <input id="asl-redirect-{{ $name }}" type="text" readonly value="{{ $guards->callbackUrl($name, $key) }}" class="asl-secret" dusk="asl-redirect-{{ $name }}">
            </div>
        @endforeach
    </section>

    <form method="POST" action="{{ $guards->route($guard, 'providers.update', ['provider' => $key]) }}" class="asl-section asl-stack">
        @csrf
        @method('PUT')

        <fieldset style="border:0;padding:0;margin:0">
            <legend style="font-weight:600;margin-bottom:.5rem">{{ __('atrium-social::social.providers.enabled_for') }}</legend>
            @foreach ($names as $name)
                <label class="asl-check">
                    <input type="checkbox" name="guards[]" value="{{ $name }}" @checked($model->enabledFor($name)) dusk="asl-guard-{{ $name }}">
                    {{ __('atrium-social::social.guards.'.$name) }}
                </label>
            @endforeach
        </fieldset>

        <div>
            <label for="asl-client-id">{{ __('atrium-social::social.providers.client_id') }}</label>
            <input id="asl-client-id" type="text" name="client_id" value="{{ old('client_id', $model->client_id) }}" autocomplete="off" spellcheck="false" dusk="asl-client-id">
        </div>

        <div>
            <label for="asl-client-secret">{{ __('atrium-social::social.providers.client_secret') }}</label>
            <input id="asl-client-secret" type="password" name="client_secret" value="" autocomplete="new-password" spellcheck="false" aria-describedby="asl-secret-hint" dusk="asl-client-secret">
            <p id="asl-secret-hint" class="asl-small asl-muted" style="margin:.25rem 0 0">
                {{ $model->exists && (string) $model->client_secret !== '' ? __('atrium-social::social.providers.secret_kept') : __('atrium-social::social.providers.secret_none') }}
            </p>
        </div>

        @foreach ($fields as $field)
            <div>
                <label for="asl-setting-{{ $field }}">{{ __('atrium-social::social.providers.settings.'.$field) }}</label>
                <input id="asl-setting-{{ $field }}" type="text" name="settings[{{ $field }}]" value="{{ old('settings.'.$field, $model->setting($field)) }}" aria-describedby="asl-setting-{{ $field }}-hint" spellcheck="false" dusk="asl-setting-{{ $field }}">
                <p id="asl-setting-{{ $field }}-hint" class="asl-small asl-muted" style="margin:.25rem 0 0">{{ __('atrium-social::social.providers.settings.'.$field.'_hint') }}</p>
            </div>
        @endforeach

        <button type="submit" class="asl-btn asl-primary" dusk="asl-save">{{ __('atrium-social::social.providers.save') }}</button>
    </form>
</div>
@endsection
