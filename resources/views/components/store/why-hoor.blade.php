{{--
    Why Choose HOOR: the brand's case, told as numbered points against a cream
    ground so it reads as editorial rather than as another feature grid.
--}}
@php($reasons = ['modesty', 'fit', 'fabric', 'egypt'])

<section class="bg-hoor-cream-100">
    <div class="hoor-container py-16 lg:py-20">
        <x-store.section-title
            align="center"
            :eyebrow="__('store.why.eyebrow')"
            :title="__('store.why.title')"
            :lead="__('store.why.lead')"
            class="reveal reveal-soft mx-auto" />

        <div class="reveal-group grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($reasons as $index => $reason)
                <div class="reveal relative">
                    <span class="font-display text-4xl text-hoor-gold-500/40">
                        {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                    </span>

                    <h3 class="mt-3 font-display text-lg text-hoor-navy-700">
                        {{ __("store.why.reasons.{$reason}.title") }}
                    </h3>

                    <p class="mt-2 text-sm leading-relaxed text-hoor-muted">
                        {{ __("store.why.reasons.{$reason}.body") }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
</section>
