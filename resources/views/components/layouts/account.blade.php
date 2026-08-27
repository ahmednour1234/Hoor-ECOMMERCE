{{--
    The shell every account page sits in: the storefront chrome, plus the
    account's own sidebar.
--}}
@props(['title' => null])

<x-layouts.store>
    @isset($title)
        @section('title', $title)
    @endisset

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="mb-8">
            <h1 class="font-display text-3xl text-hoor-navy-700">{{ $title ?? __('account.title') }}</h1>

            @isset($subtitle)
                <p class="mt-2 text-sm text-hoor-muted">{{ $subtitle }}</p>
            @endisset
        </header>

        @if (session('status'))
            <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
        @endif

        <div class="grid gap-8 lg:grid-cols-[16rem_1fr]">
            {{-- Sidebar: a real nav on desktop, a horizontal strip on mobile. --}}
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <x-store.account-nav class="flex gap-1 overflow-x-auto no-scrollbar lg:block lg:space-y-1 lg:overflow-visible" />
            </aside>

            <div>{{ $slot }}</div>
        </div>
    </div>
</x-layouts.store>
