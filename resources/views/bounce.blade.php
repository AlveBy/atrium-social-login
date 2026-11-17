{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{--
    The provider's answer, posted on from this site so the browser adds the
    session cookie. Telegram's answer is after a # in the address: the script
    reads it into the form. Without scripts the button sends the form.
--}}
@php($nonce = \Illuminate\Support\Facades\Vite::cspNonce())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('atrium-social::social.bounce.title') }}</title>
    @include('atrium-social::partials.styles')
</head>
<body class="asl" style="margin:0">
<main class="asl-page">
    <form method="POST" action="{{ $action }}" class="asl-card" id="asl-bounce" dusk="asl-bounce">
        <h1>{{ __('atrium-social::social.bounce.title') }}</h1>
        <p class="asl-muted" role="status">{{ __('atrium-social::social.bounce.working') }}</p>
        @foreach ($fields as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <button type="submit" class="asl-btn asl-primary asl-block">{{ __('atrium-social::social.bounce.continue') }}</button>
    </form>
</main>
<script @if ($nonce) nonce="{{ $nonce }}" @endif>
(function () {
    var form = document.getElementById('asl-bounce');
    @if ($fragment)
    var match = /tgAuthResult=([A-Za-z0-9_\-+\/=]+)/.exec(window.location.hash);
    if (!match) { return; }
    try {
        var json = atob(match[1].replace(/-/g, '+').replace(/_/g, '/'));
        var data = JSON.parse(decodeURIComponent(escape(json)));
        Object.keys(data).forEach(function (name) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = String(data[name]);
            form.appendChild(input);
        });
    } catch (e) { return; }
    history.replaceState(null, '', window.location.pathname + window.location.search);
    @endif
    form.submit();
})();
</script>
</body>
</html>
