{{--
    The welcome discount, offered to a guest at checkout.

    Every figure comes from the coupon rather than the copy: change the campaign
    in the admin and this follows, or disappears. A banner that promises 5% when
    the coupon says 10% would be worse than no banner.

    The `redirect` parameter brings her back to checkout afterwards rather than
    to her account, so she does not lose the basket she was paying for.
--}}
@props(['offer', 'saving' => 0])

@php
    $percent = $offer->type === \App\Enums\CouponType::Percentage ? $offer->value : null;
@endphp

@if ($offer && $percent)
    <div {{ $attributes->merge(['class' => 'rounded-md border border-hoor-gold-500/40 bg-hoor-beige-100 p-5']) }}>
        <div class="flex flex-wrap items-start justify-between gap-4">

            <div class="min-w-0 flex-1">
                <p class="font-display text-base italic text-hoor-navy-700">
                    {{ __('checkout.welcome_offer.title', ['percent' => $percent]) }}
                </p>

                <p class="mt-1.5 text-sm leading-relaxed text-hoor-navy-600/85">
                    {{ __('checkout.welcome_offer.body', ['percent' => $percent]) }}
                </p>

                {{-- The figure in money, so she does not have to compute a
                     percentage of her own basket. It is the same figure
                     checkout will apply, from the same method. --}}
                @if ($saving > 0)
                    <p class="mt-2 text-sm font-medium text-hoor-gold-600">
                        {{ __('checkout.welcome_offer.saving', [
                            'amount' => \App\Casts\Money::format($saving),
                        ]) }}
                    </p>
                @endif

                <p class="mt-2 text-xs text-hoor-muted">
                    {{ __('checkout.welcome_offer.note') }}
                </p>
            </div>

            {{-- Straight to Google, returning here. --}}
            <a href="{{ route('social.redirect', [
                   'provider' => 'google',
                   'redirect' => route('store.checkout.index'),
               ]) }}"
               class="flex shrink-0 items-center justify-center gap-2.5 rounded-md border
                      border-hoor-cream-300 bg-white px-4 py-2.5 text-sm font-medium
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
        </div>
    </div>
@endif
