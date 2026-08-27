{{--
    Admin navigation.

    Sections are declared as data so adding a module later means adding one
    array entry rather than duplicating markup. Items without a `route` render
    as disabled until their phase ships.
--}}
@php
    $sections = [
        [
            'label' => 'overview',
            'items' => [
                ['key' => 'dashboard', 'route' => 'admin.dashboard'],
            ],
        ],
        [
            'label' => 'catalog',
            'items' => [
                ['key' => 'products',   'route' => 'admin.products.index'],
                ['key' => 'categories', 'route' => 'admin.categories.index'],
                ['key' => 'inventory'],
                ['key' => 'colors',    'route' => 'admin.colors.index'],
                ['key' => 'sizes',     'route' => 'admin.sizes.index'],
            ],
        ],
        [
            'label' => 'sales',
            'items' => [
                ['key' => 'orders', 'route' => 'admin.orders.index'],
                ['key' => 'returns', 'route' => 'admin.returns.index'],
                ['key' => 'customers'],
            ],
        ],
        [
            'label' => 'marketing',
            'items' => [
                ['key' => 'coupons',    'route' => 'admin.coupons.index'],
                ['key' => 'banners',    'route' => 'admin.banners.index'],
                ['key' => 'sliders',    'route' => 'admin.slides.index'],
                ['key' => 'newsletter', 'route' => 'admin.newsletter.index'],
            ],
        ],
        [
            'label' => 'settings',
            'items' => [
                ['key' => 'shipping',   'route' => 'admin.governorates.index'],
                ['key' => 'messages',   'route' => 'admin.messages.index'],
                ['key' => 'faqs',       'route' => 'admin.faqs.index'],
                ['key' => 'pages',      'route' => 'admin.settings.edit'],
                ['key' => 'general',    'route' => 'admin.settings.edit'],
            ],
        ],
    ];
@endphp

{{-- Mobile scrim --}}
<div x-show="sidebarOpen" x-cloak
     class="fixed inset-0 z-40 bg-hoor-navy-900/50 lg:hidden"
     @click="sidebarOpen = false"></div>

<aside class="fixed inset-y-0 start-0 z-50 w-64 -translate-x-full overflow-y-auto
              bg-hoor-navy-700 text-hoor-cream-50 transition-transform duration-300 ease-hoor
              rtl:translate-x-full lg:translate-x-0 rtl:lg:translate-x-0"
       :class="sidebarOpen && '!translate-x-0'">

    <div class="flex h-16 items-center justify-between border-b border-hoor-cream-50/10 px-5">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center">
            <img src="{{ asset('images/brand/hoor-horizontal-white.svg') }}"
                 alt="{{ __('common.brand') }}" class="h-7 w-auto">
        </a>
        <button type="button" class="rounded-sm p-1.5 text-hoor-cream-50/70 lg:hidden"
                @click="sidebarOpen = false" aria-label="{{ __('nav.close') }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
            </svg>
        </button>
    </div>

    <nav class="p-3 pb-8" aria-label="{{ __('admin.nav.dashboard') }}">
        @foreach ($sections as $section)
            <p class="px-3 pb-2 pt-4 text-[0.65rem] font-semibold uppercase tracking-editorial text-hoor-cream-50/40">
                {{ __("admin.nav.{$section['label']}") }}
            </p>

            <ul class="space-y-0.5">
                @foreach ($section['items'] as $item)
                    @php
                        $hasRoute = isset($item['route']) && Route::has($item['route']);
                        $isActive = $hasRoute && request()->routeIs($item['route']);
                    @endphp

                    <li>
                        @if ($hasRoute)
                            <a href="{{ route($item['route']) }}"
                               @class([
                                   'flex items-center gap-3 rounded-sm px-3 py-2.5 text-sm transition',
                                   'bg-hoor-gold-500 font-medium text-hoor-navy-700' => $isActive,
                                   'text-hoor-cream-50/75 hover:bg-hoor-cream-50/10 hover:text-hoor-cream-50' => ! $isActive,
                               ])
                               @if ($isActive) aria-current="page" @endif>
                                {{ __("admin.nav.{$item['key']}") }}
                            </a>
                        @else
                            <span class="flex items-center justify-between gap-3 rounded-sm px-3 py-2.5
                                         text-sm text-hoor-cream-50/35"
                                  title="{{ __('common.states.coming_soon') }}">
                                {{ __("admin.nav.{$item['key']}") }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endforeach
    </nav>
</aside>
