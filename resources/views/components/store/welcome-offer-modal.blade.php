{{--
    The welcome offer, as a dialog.

    Shown once per visit, a few seconds in — long enough that she is not
    interrupted mid-thought, early enough to matter before she finishes the
    form. Dismissing it is remembered for the session, so it never nags.

    The banner above the form stays either way: a customer who closes this and
    changes her mind still has somewhere to say yes.
--}}
@props(['offer', 'saving' => 0])

@php
    $percent = $offer->type === \App\Enums\CouponType::Percentage ? $offer->value : null;
@endphp

@if ($offer && $percent)
    <div x-data="welcomeOfferModal()"
         x-show="open"
         x-cloak
         x-on:keydown.escape.window="dismiss()"
         class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
         role="dialog"
         aria-modal="true"
         aria-labelledby="welcome-offer-title">

        {{-- The backdrop. Clicking it dismisses, as a dialog should. --}}
        <div x-show="open"
             x-transition:enter="transition ease-hoor duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-hoor duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             x-on:click="dismiss()"
             class="absolute inset-0 bg-hoor-navy-900/50 backdrop-blur-[2px]"></div>

        {{-- The card. Rises from the bottom on a phone, where a centred dialog
             fights the keyboard; settles in the middle on a larger screen. --}}
        <div x-show="open"
             x-transition:enter="transition ease-hoor duration-300"
             x-transition:enter-start="translate-y-8 scale-95 opacity-0"
             x-transition:enter-end="translate-y-0 scale-100 opacity-100"
             x-transition:leave="transition ease-hoor duration-200"
             x-transition:leave-start="translate-y-0 scale-100 opacity-100"
             x-transition:leave-end="translate-y-4 scale-95 opacity-0"
             class="relative w-full max-w-sm overflow-hidden rounded-lg bg-white shadow-soft">

            {{-- A navy header carrying the figure, so the offer reads before
                 any of the words do. --}}
            <div class="relative overflow-hidden bg-hoor-navy-500 px-6 pb-7 pt-8 text-center">

                {{-- A soft gold bloom behind the number, rather than a flat
                     block of colour. --}}
                <span class="pointer-events-none absolute -top-16 start-1/2 h-40 w-40 -translate-x-1/2
                             rounded-full bg-hoor-gold-500/20 blur-2xl rtl:translate-x-1/2"
                      aria-hidden="true"></span>

                <button type="button"
                        x-on:click="dismiss()"
                        class="absolute end-3 top-3 rounded-full p-1.5 text-hoor-cream-50/60
                               transition hover:bg-white/10 hover:text-hoor-cream-50
                               focus-visible:outline focus-visible:outline-2
                               focus-visible:outline-offset-2 focus-visible:outline-hoor-gold-500"
                        aria-label="{{ __('common.actions.cancel') }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>

                <p class="relative text-xs uppercase tracking-editorial text-hoor-gold-500">
                    {{ __('checkout.welcome_offer.eyebrow') }}
                </p>

                <p class="relative mt-2 font-display text-5xl leading-none text-hoor-cream-50" dir="ltr">
                    {{ $percent }}%
                </p>

                <p class="relative mt-2 text-sm text-hoor-cream-50/80">
                    {{ __('checkout.welcome_offer.off_this_order') }}
                </p>
            </div>

            <div class="px-6 py-6 text-center">
                <h2 id="welcome-offer-title" class="font-display text-lg italic text-hoor-navy-700">
                    {{ __('checkout.welcome_offer.modal_title') }}
                </h2>

                <p class="mt-2 text-sm leading-relaxed text-hoor-navy-600/85">
                    {{ __('checkout.welcome_offer.modal_body') }}
                </p>

                {{-- The saving in money, so she is not asked to compute a
                     percentage of her own basket. --}}
                @if ($saving > 0)
                    <p class="mt-4 rounded-md bg-hoor-beige-100 px-4 py-2.5 text-sm font-medium text-hoor-gold-600">
                        {{ __('checkout.welcome_offer.saving', [
                            'amount' => \App\Casts\Money::format($saving),
                        ]) }}
                    </p>
                @endif

                <a href="{{ route('social.redirect', [
                       'provider' => 'google',
                       'redirect' => route('store.checkout.index'),
                   ]) }}"
                   class="mt-5 flex w-full items-center justify-center gap-3 rounded-md
                          border border-hoor-cream-300 bg-white px-4 py-3 text-sm font-medium
                          text-hoor-navy-700 transition
                          hover:border-hoor-navy-300 hover:shadow-card
                          focus-visible:outline focus-visible:outline-2
                          focus-visible:outline-offset-2 focus-visible:outline-hoor-denim-500">

                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.5a5.6 5.6 0 01-2.4 3.6v3h3.9c2.3-2.1 3.5-5.2 3.5-8.8z"/>
                        <path fill="#34A853" d="M12 24c3.2 0 5.9-1.1 7.9-2.9l-3.9-3c-1.1.7-2.4 1.2-4 1.2-3.1 0-5.7-2.1-6.6-4.9H1.4v3.1A12 12 0 0012 24z"/>
                        <path fill="#FBBC05" d="M5.4 14.4a7.2 7.2 0 010-4.6V6.7H1.4a12 12 0 000 10.8l4-3.1z"/>
                        <path fill="#EA4335" d="M12 4.8c1.8 0 3.4.6 4.6 1.8l3.5-3.5A12 12 0 001.4 6.7l4 3.1C6.3 6.9 8.9 4.8 12 4.8z"/>
                    </svg>

                    <span>{{ __('checkout.welcome_offer.cta') }}</span>
                </a>

                {{-- Declining has to be as easy as accepting, and plainly
                     worded — not a guilt-trip. --}}
                <button type="button"
                        x-on:click="dismiss()"
                        class="mt-3 w-full text-sm text-hoor-muted transition hover:text-hoor-navy-600">
                    {{ __('checkout.welcome_offer.decline') }}
                </button>

                <p class="mt-4 text-xs text-hoor-muted">
                    {{ __('checkout.welcome_offer.note') }}
                </p>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            /**
             * The welcome dialog.
             *
             * Opens once, a few seconds in. Dismissal is remembered for the
             * session so it cannot nag: sessionStorage rather than
             * localStorage, because a customer returning next week should see
             * the offer again, but one reloading the page should not.
             */
            function welcomeOfferModal() {
                const KEY = 'hoor.welcome_offer.dismissed';

                return {
                    open: false,

                    init() {
                        let dismissed = false;

                        // Storage throws in some private modes; a blocked read
                        // should show the dialog, not break the page.
                        try {
                            dismissed = sessionStorage.getItem(KEY) === '1';
                        } catch { /* treat as not dismissed */ }

                        if (dismissed) {
                            return;
                        }

                        // Late enough not to interrupt her as she starts
                        // filling the form, early enough to matter.
                        setTimeout(() => { this.open = true; }, 4000);
                    },

                    dismiss() {
                        this.open = false;

                        try {
                            sessionStorage.setItem(KEY, '1');
                        } catch { /* nothing to remember it with */ }
                    },
                };
            }
        </script>
    @endpush
@endif
