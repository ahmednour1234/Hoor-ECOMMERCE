{{--
    Newsletter signup.

    Heading and copy come from settings, so the shop can reword its own pitch.
    The whole section is switched off from the admin rather than commented out
    here — see SettingsRegistry::newsletter.
--}}
@php
    $heading = $settings->translated('newsletter.heading') ?: __('store.newsletter.title');
    $body = $settings->translated('newsletter.body') ?: __('store.newsletter.lead');
@endphp

<section class="bg-hoor-beige-100">
    <div class="hoor-container py-16 lg:py-20">
        <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow">{{ __('store.footer.newsletter') }}</p>

            <h2 class="mt-3 section-title">{{ $heading }}</h2>

            <p class="mt-3 text-sm leading-relaxed text-hoor-muted">{{ $body }}</p>

            <form method="POST" action="{{ route('store.newsletter.subscribe') }}"
                  class="mx-auto mt-8 flex max-w-md flex-col gap-3 sm:flex-row">
                @csrf

                <label for="newsletter-email" class="sr-only">
                    {{ __('store.footer.email_placeholder') }}
                </label>

                <input type="email"
                       id="newsletter-email"
                       name="email"
                       required
                       autocomplete="email"
                       dir="ltr"
                       value="{{ old('email') }}"
                       placeholder="{{ __('store.footer.email_placeholder') }}"
                       class="form-input flex-1">

                {{-- A honeypot: nobody sees it, so anything filling it is a bot.
                     Cheaper and less hostile than a captcha. --}}
                <input type="text" name="website" tabindex="-1" autocomplete="off"
                       class="hidden" aria-hidden="true">

                <x-ui.button type="submit" variant="primary">
                    {{ __('store.footer.subscribe') }}
                </x-ui.button>
            </form>

            @error('email')
                <p class="mt-4 text-sm text-red-600">{{ $message }}</p>
            @enderror

            @if (session('status'))
                <p class="mt-4 text-sm text-emerald-700">{{ session('status') }}</p>
            @endif

            <p class="mt-4 text-xs text-hoor-muted">{{ __('store.newsletter.privacy') }}</p>
        </div>
    </div>
</section>
