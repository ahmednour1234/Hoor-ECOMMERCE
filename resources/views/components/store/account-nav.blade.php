{{--
    Account navigation.

    Declared as data so a new section is one array entry. The active item is
    matched on the route name prefix, so a nested page (an order's detail, a
    return being raised) keeps its parent highlighted.
--}}
@php
    $items = [
        ['key' => 'overview',  'route' => 'store.account.index',           'match' => 'store.account.index'],
        ['key' => 'orders',    'route' => 'store.account.orders.index',    'match' => 'store.account.orders.'],
        ['key' => 'returns',   'route' => 'store.account.returns.index',   'match' => 'store.account.returns.'],
        ['key' => 'wishlist',  'route' => 'store.account.wishlist.index',  'match' => 'store.account.wishlist.'],
        ['key' => 'addresses', 'route' => 'store.account.addresses.index', 'match' => 'store.account.addresses.'],
        ['key' => 'profile',   'route' => 'store.account.profile.edit',    'match' => 'store.account.profile.'],
    ];

    $current = request()->route()?->getName() ?? '';
@endphp

<nav {{ $attributes->merge(['class' => 'space-y-1']) }} aria-label="{{ __('account.title') }}">
    @foreach ($items as $item)
        @php
            $isActive = $item['match'] === $current || str_starts_with($current, $item['match']);
        @endphp

        <a href="{{ route($item['route']) }}"
           @if ($isActive) aria-current="page" @endif
           class="block rounded-sm px-4 py-2.5 text-sm transition
                  {{ $isActive
                      ? 'bg-hoor-navy-700 font-medium text-hoor-cream-50'
                      : 'text-hoor-navy-600 hover:bg-hoor-cream-100' }}">
            {{ __('account.nav.'.$item['key']) }}
        </a>
    @endforeach

    <form method="POST" action="{{ route('logout') }}" class="pt-2">
        @csrf
        <button type="submit"
                class="block w-full rounded-sm px-4 py-2.5 text-start text-sm text-hoor-muted
                       transition hover:bg-hoor-cream-100 hover:text-hoor-navy-700">
            {{ __('account.nav.logout') }}
        </button>
    </form>
</nav>
