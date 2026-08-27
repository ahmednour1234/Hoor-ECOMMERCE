{{--
    Admin shell.

    The sidebar is fixed on large screens and becomes an overlay drawer below
    lg; both share one component so navigation has a single definition.
--}}
<!DOCTYPE html>
<html lang="{{ \App\Support\Locale::htmlLang() }}" dir="{{ $direction }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', __('admin.title')) — {{ __('admin.title') }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/hoor-icon-blue.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-hoor-cream-100" x-data="{ sidebarOpen: false }">

    <x-admin.sidebar />

    {{-- Content column is offset by the fixed sidebar width on lg and up. --}}
    <div class="lg:ms-64">
        <x-admin.navbar />

        <main class="p-4 sm:p-6 lg:p-8">
            @if (session('status'))
                <div class="mb-6 rounded-sm border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
