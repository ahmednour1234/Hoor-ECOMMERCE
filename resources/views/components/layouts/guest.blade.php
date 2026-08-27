{{--
    Authentication shell (login, register, password reset).

    Named `guest` because Breeze's auth views reference <x-guest-layout>.
--}}
<!DOCTYPE html>
<html lang="{{ \App\Support\Locale::htmlLang() }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">

    <title>{{ __('common.brand') }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/hoor-icon-blue.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col items-center justify-center bg-hoor-cream-100 px-4 py-10">

    <a href="{{ route('store.home') }}" class="mb-8">
        <img src="{{ asset('images/brand/hoor-horizontal-blue.svg') }}"
             alt="{{ __('common.brand') }}" class="h-10 w-auto">
    </a>

    <div class="w-full max-w-md rounded-md border border-hoor-cream-300 bg-white p-7 shadow-card sm:p-8">
        {{ $slot }}
    </div>

    <div class="mt-6">
        <x-store.language-switcher />
    </div>
</body>
</html>
