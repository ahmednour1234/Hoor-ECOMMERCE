<x-layouts.account :title="__('account.addresses.add')">
    @include('store.account.addresses._form', ['address' => null, 'governorates' => $governorates])
</x-layouts.account>
