{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{-- A page of the site: inside the layout of the personal account, or of the theme. --}}
@extends(view()->exists('atrium-account::layout') ? 'atrium-account::layout' : config('atrium-account.layout', 'atrium-theme::layout'))

@push('head')
    <meta name="robots" content="noindex, nofollow">
    @include('atrium-social::partials.styles')
@endpush

@section('content')
    <div class="asl asl-site">
        @yield('asl')
    </div>
@endsection
