{{--
    Authentication shell (login, register, password reset).

    Named `guest` because Breeze's auth views reference <x-guest-layout>.

    The brand plate is the page: cream silk, navy fabric at the foot, and the
    wordmark already set into the artwork. Nothing is drawn over it except the
    card, which is why no separate logo is rendered above — the photograph
    carries it, and a second one would read as a duplicate.
--}}
<!DOCTYPE html>
<html lang="{{ \App\Support\Locale::htmlLang() }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">

    <title>{{ $title ?? __('common.brand') }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/hoor-icon-blue.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    {{-- The backdrop is the largest thing on the page and the first thing
         seen, so it is fetched alongside the stylesheet rather than after it. --}}
    <link rel="preload" as="image" href="{{ asset('images/auth/auth-backdrop.jpg') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-hoor-cream-100">

    <div class="relative flex min-h-screen flex-col">

        {{--
            The plate.

            Fixed rather than absolute, so a tall form on a short screen
            scrolls over a still background instead of dragging it along.
            `object-cover` from the top keeps the wordmark in frame at every
            aspect ratio, since the artwork places it in the upper band.
        --}}
        <img src="{{ asset('images/auth/auth-backdrop.jpg') }}"
             alt=""
             width="1907" height="825"
             fetchpriority="high" decoding="async"
             class="fixed inset-0 -z-20 h-full w-full object-cover object-top">

        {{-- A wash over the plate: the artwork is busiest at the lower start
             corner, and the card needs quiet ground behind it wherever the
             viewport puts it. --}}
        <div class="fixed inset-0 -z-10 bg-hoor-cream-50/35 backdrop-blur-[1px]" aria-hidden="true"></div>

        {{-- The card --}}
        <main class="flex flex-1 items-center justify-center px-4 py-10 sm:py-14">
            <div class="w-full max-w-md">

                {{-- On narrow screens the artwork's own wordmark is cropped
                     out by object-cover, so the mark is drawn here instead. --}}
                <a href="{{ route('store.home') }}"
                   class="mb-7 flex justify-center lg:hidden">
                    <img src="{{ asset('images/brand/hoor-primary-blue.svg') }}"
                         alt="{{ __('common.brand') }}"
                         class="h-16 w-auto">
                </a>

                <div class="rounded-lg border border-white/60 bg-white/95 p-7 shadow-soft backdrop-blur-sm sm:p-9">
                    {{ $slot }}
                </div>
            </div>
        </main>

        {{-- Footer bar --}}
        <footer class="relative border-t border-hoor-cream-300/50 bg-white/45 backdrop-blur-sm">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3
                        px-4 py-4 text-xs text-hoor-navy-600/70 sm:flex-row sm:px-6">
                <p>&copy; {{ date('Y') }} {{ __('common.brand') }}. {{ __('store.footer.rights') }}</p>

                <x-store.language-switcher />
            </div>
        </footer>
    </div>
</body>
</html>
