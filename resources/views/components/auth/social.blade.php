{{--
    Sign in with an outside provider.

    Renders nothing at all when no provider is configured, rather than a button
    that leads to an error page — a shop that has not set its Google
    credentials simply shows the ordinary form.
--}}
@php
    $social = app(\App\Services\SocialAuthService::class);

    $enabled = collect(\App\Services\SocialAuthService::PROVIDERS)
        ->filter(fn (string $provider): bool => $social->isEnabled($provider));
@endphp

@if ($enabled->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'mt-6']) }}>

        {{-- A labelled rule, so the two ways in read as alternatives rather
             than as a form with a stray button under it. --}}
        <div class="flex items-center gap-3">
            <span class="h-px flex-1 bg-hoor-cream-300"></span>
            <span class="text-xs uppercase tracking-editorial text-hoor-muted">
                {{ __('auth.social.or') }}
            </span>
            <span class="h-px flex-1 bg-hoor-cream-300"></span>
        </div>

        <div class="mt-5 space-y-3">
            @foreach ($enabled as $provider)
                <a href="{{ route('social.redirect', ['provider' => $provider]) }}"
                   class="flex w-full items-center justify-center gap-3 rounded-md border
                          border-hoor-cream-300 bg-white px-4 py-2.5 text-sm font-medium
                          text-hoor-navy-700 transition
                          hover:border-hoor-navy-300 hover:bg-hoor-cream-50
                          focus-visible:outline focus-visible:outline-2
                          focus-visible:outline-offset-2 focus-visible:outline-hoor-denim-500">

                    {{-- Google's mark, drawn inline. Their brand guidelines
                         require the four-colour G, so this one keeps its own
                         fills rather than following currentColor. --}}
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.5a5.6 5.6 0 01-2.4 3.6v3h3.9c2.3-2.1 3.5-5.2 3.5-8.8z"/>
                        <path fill="#34A853" d="M12 24c3.2 0 5.9-1.1 7.9-2.9l-3.9-3c-1.1.7-2.4 1.2-4 1.2-3.1 0-5.7-2.1-6.6-4.9H1.4v3.1A12 12 0 0012 24z"/>
                        <path fill="#FBBC05" d="M5.4 14.4a7.2 7.2 0 010-4.6V6.7H1.4a12 12 0 000 10.8l4-3.1z"/>
                        <path fill="#EA4335" d="M12 4.8c1.8 0 3.4.6 4.6 1.8l3.5-3.5A12 12 0 001.4 6.7l4 3.1C6.3 6.9 8.9 4.8 12 4.8z"/>
                    </svg>

                    <span>{{ __('auth.social.'.$provider) }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endif
