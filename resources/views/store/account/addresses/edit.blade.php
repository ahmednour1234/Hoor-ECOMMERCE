<x-layouts.account :title="__('account.addresses.edit')">
    @include('store.account.addresses._form', ['address' => $address, 'governorates' => $governorates])
</x-layouts.account>
