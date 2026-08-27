{{--
    Brand benefits strip: the four facts that drive conversion in Egypt.
    Sits directly under the hero so the COD promise is seen immediately.
--}}
@php
    $benefits = ['cod', 'shipping', 'quality', 'exchange'];

    $icons = [
        // Banknote
        'cod' => 'M2.25 8.25h19.5M2.25 8.25a2.25 2.25 0 012.25-2.25h15a2.25 2.25 0 012.25 2.25m-19.5 0v7.5a2.25 2.25 0 002.25 2.25h15a2.25 2.25 0 002.25-2.25v-7.5M12 15a2 2 0 100-4 2 2 0 000 4z',
        // Delivery van
        'shipping' => 'M8.25 18.75a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM18.75 18.75a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM2.25 6h10.5v10.5H2.25zM12.75 9.75h3.75L20.25 13.5v3h-7.5z',
        // Ribbon / quality seal
        'quality' => 'M11.48 3.5a.75.75 0 011.04 0l2.02 1.93 2.78-.26a.75.75 0 01.82.68l.26 2.78 1.93 2.02a.75.75 0 010 1.04l-1.93 2.02-.26 2.78a.75.75 0 01-.82.68l-2.78-.26-2.02 1.93a.75.75 0 01-1.04 0l-2.02-1.93-2.78.26a.75.75 0 01-.82-.68l-.26-2.78-1.93-2.02a.75.75 0 010-1.04l1.93-2.02.26-2.78a.75.75 0 01.82-.68l2.78.26z',
        // Exchange arrows
        'exchange' => 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5a4.5 4.5 0 004.5-4.5M16.5 3L21 7.5m0 0L16.5 12M21 7.5H7.5A4.5 4.5 0 003 12',
    ];
@endphp

<section class="border-y border-hoor-cream-300 bg-hoor-cream-50">
    <div class="reveal-group hoor-container grid gap-6 py-10 sm:grid-cols-2 lg:grid-cols-4 lg:py-12">
        @foreach ($benefits as $benefit)
            <div class="reveal flex items-start gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full
                             bg-hoor-navy-50 text-hoor-navy-500">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                         viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$benefit] }}" />
                    </svg>
                </span>

                <div>
                    <h3 class="font-sans text-sm font-semibold text-hoor-navy-700">
                        {{ __("store.promise.{$benefit}.title") }}
                    </h3>
                    <p class="mt-1 text-sm leading-relaxed text-hoor-muted">
                        {{ __("store.promise.{$benefit}.body") }}
                    </p>
                </div>
            </div>
        @endforeach
    </div>
</section>
