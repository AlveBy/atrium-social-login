{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@if (session('atrium-social.status'))
    <div class="asl-alert asl-ok" role="status" dusk="asl-status">{{ session('atrium-social.status') }}</div>
@endif
@foreach ($errors->getMessages() as $messages)
    @foreach ($messages as $message)
        <div class="asl-alert asl-error" role="alert" dusk="asl-error">{{ $message }}</div>
    @endforeach
@endforeach
