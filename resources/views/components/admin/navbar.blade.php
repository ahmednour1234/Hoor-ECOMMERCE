@php
    $user = auth()->user();
@endphp

<header class="sticky top-0 z-30 border-b border-hoor-cream-300 bg-white/95 backdrop-blur">
    <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">

        <div class="flex items-center gap-3">
            <button type="button"
                    class="-ms-2 rounded-sm p-2 text-hoor-navy-600 lg:hidden"
                    @click="sidebarOpen = true"
                    aria-label="{{ __('nav.menu') }}">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                </svg>
            </button>

            <h1 class="font-display text-lg text-hoor-navy-700">
                @yield('page-title', __('admin.nav.dashboard'))
            </h1>
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
            <x-store.language-switcher class="hidden sm:flex" />

            <a href="{{ route('store.home') }}" target="_blank" rel="noopener"
               class="btn-ghost btn-sm hidden sm:inline-flex">
                {{ __('admin.nav.view_store') }}
            </a>

            {{-- Account menu --}}
            <div x-data="{ open: false }" class="relative">
                <button type="button"
                        class="flex items-center gap-2 rounded-sm px-2 py-1.5 transition hover:bg-hoor-cream-100"
                        @click="open = !open"
                        :aria-expanded="open"
                        aria-haspopup="true">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full
                                 bg-hoor-navy-500 text-xs font-semibold text-hoor-cream-50">
                        {{ \Illuminate\Support\Str::of($user->name)->substr(0, 1)->upper() }}
                    </span>
                    <span class="hidden text-sm font-medium text-hoor-navy-700 sm:inline">
                        {{ $user->name }}
                    </span>
                </button>

                <div x-show="open" x-cloak @click.outside="open = false"
                     x-transition.origin.top
                     class="absolute end-0 z-40 mt-2 w-52 overflow-hidden rounded-sm border
                            border-hoor-cream-300 bg-white shadow-soft">

                    <div class="border-b border-hoor-cream-300 px-4 py-3">
                        <p class="truncate text-sm font-medium text-hoor-navy-700">{{ $user->name }}</p>
                        <p class="truncate text-xs text-hoor-muted">{{ $user->email }}</p>
                        <span class="badge-gold mt-2">{{ $user->role->label() }}</span>
                    </div>

                    <a href="{{ route('store.account.profile.edit') }}"
                       class="block px-4 py-2.5 text-sm text-hoor-navy-600 transition hover:bg-hoor-cream-100">
                        {{ __('nav.profile') }}
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="block w-full px-4 py-2.5 text-start text-sm text-red-600 transition hover:bg-red-50">
                            {{ __('nav.logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
