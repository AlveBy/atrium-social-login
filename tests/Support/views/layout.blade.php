{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{-- The site's own layout, as a theme would bring it: no panel in it. --}}
<!DOCTYPE html>
<html lang="en">
<head>@stack('head')</head>
<body class="site">@yield('content')</body>
</html>
