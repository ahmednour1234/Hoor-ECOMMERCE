{{--
    Storefront shell.

    `dir` and `lang` are driven by the resolved locale so Arabic renders RTL and
    English renders LTR without any per-page branching.
--}}
<!DOCTYPE html>
<html lang="{{ \App\Support\Locale::htmlLang() }}" dir="{{ $direction }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', __('store.meta.home_title'))</title>
    <meta name="description" content="@yield('description', __('store.meta.home_description'))">

    {{-- Tell search engines about the sibling-language version of this page. --}}
    @foreach (\App\Support\Locale::codes() as $code)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ \App\Support\Locale::urlFor($code) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ \App\Support\Locale::urlFor(\App\Support\Locale::FALLBACK) }}">

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/hoor-icon-blue.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="flex min-h-screen flex-col bg-hoor-cream-50">

    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:start-4 focus:z-50
              focus:rounded-sm focus:bg-hoor-navy-500 focus:px-4 focus:py-2 focus:text-hoor-cream-50">
        {{ __('nav.menu') }}
    </a>

    <x-store.header />

    <main id="main" class="flex-1">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <x-store.footer />

    <x-store.toasts />

    {{-- Shared behaviour used by components that repeat on a page. --}}
    @include('store.partials.wishlist-script')
    @include('store.partials.reveal-script')
    @include('store.partials.cart-script')

    @stack('scripts')
</body>
</html>
