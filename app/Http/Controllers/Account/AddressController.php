<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\CustomerAddressRequest;
use App\Models\CustomerAddress;
use App\Services\ShippingService;
use App\Services\CustomerAddressService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The customer's saved addresses.
 */
class AddressController extends Controller
{
    public function __construct(
        private readonly CustomerAddressService $addresses,
        private readonly ShippingService $shipping,
    ) {
    }

    public function index(Request $request): View
    {
        return view('store.account.addresses.index', [
            'addresses' => $this->addresses->forCustomer($request->user()),
        ]);
    }

    public function create(): View
    {
        return view('store.account.addresses.create', [
            'governorates' => $this->shipping->deliverableGovernorates(),
        ]);
    }

    public function store(CustomerAddressRequest $request): RedirectResponse
    {
        $this->addresses->create($request->user(), $request->addressData());

        return redirect()
            ->route('store.account.addresses.index')
            ->with('status', __('account.addresses.saved'));
    }

    public function edit(CustomerAddress $address): View
    {
        $this->authorize('update', $address);

        return view('store.account.addresses.edit', [
            'address'      => $address,
            'governorates' => $this->shipping->deliverableGovernorates(),
            'areas'        => $this->shipping->areasFor($address->governorate),
        ]);
    }

    public function update(CustomerAddressRequest $request, CustomerAddress $address): RedirectResponse
    {
        $this->authorize('update', $address);

        $this->addresses->update($address, $request->addressData());

        return redirect()
            ->route('store.account.addresses.index')
            ->with('status', __('account.addresses.updated'));
    }

    public function destroy(CustomerAddress $address): RedirectResponse
    {
        $this->authorize('delete', $address);

        $this->addresses->delete($address);

        return back()->with('status', __('account.addresses.deleted'));
    }

    /**
     * Promote an address to the one checkout prefills.
     */
    public function makeDefault(CustomerAddress $address): RedirectResponse
    {
        $this->authorize('update', $address);

        $this->addresses->makeDefault($address);

        return back()->with('status', __('account.addresses.default_set'));
    }
}
