<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Casts\Money;
use App\Http\Controllers\Controller;
use App\Http\Requests\Governorate\StoreGovernorateRequest;
use App\Http\Requests\Governorate\UpdateGovernorateRequest;
use App\Models\Governorate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Governorate management.
 *
 * Fees are entered in EGP and stored as piastres, so no view or controller ever
 * handles a raw storage figure.
 */
class GovernorateController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Governorate::class);

        return view('admin.governorates.index', [
            'governorates' => Governorate::query()
                ->withCount(['areas', 'areas as active_areas_count' => fn ($query) => $query->where('is_active', true)])
                ->when(
                    filled($search = $request->string('search')->toString()),
                    fn ($query) => $query->searchTranslation('name', $search),
                )
                ->ordered()
                ->paginate(30)
                ->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Governorate::class);

        return view('admin.governorates.create', [
            'governorate' => new Governorate([
                'is_active'         => true,
                'delivery_days_min' => 2,
                'delivery_days_max' => 5,
                'sort_order'        => (int) Governorate::query()->max('sort_order') + 1,
            ]),
        ]);
    }

    public function store(StoreGovernorateRequest $request): RedirectResponse
    {
        $this->authorize('create', Governorate::class);

        $governorate = Governorate::query()->create($this->attributes($request->validated()));

        return redirect()
            ->route('admin.governorates.index')
            ->with('status', __('shipping.messages.governorate_created', ['name' => $governorate->name_en]));
    }

    public function edit(Governorate $governorate): View
    {
        $this->authorize('update', $governorate);

        return view('admin.governorates.edit', [
            'governorate' => $governorate->loadCount('areas'),
        ]);
    }

    public function update(UpdateGovernorateRequest $request, Governorate $governorate): RedirectResponse
    {
        $this->authorize('update', $governorate);

        $governorate->update($this->attributes($request->validated()));

        return redirect()
            ->route('admin.governorates.index')
            ->with('status', __('shipping.messages.governorate_updated', ['name' => $governorate->name_en]));
    }

    /**
     * Toggle availability without opening the form.
     *
     * Deactivating is how a governorate is taken out of service — it is never
     * deleted, because past orders reference it.
     */
    public function toggle(Governorate $governorate): RedirectResponse
    {
        $this->authorize('update', $governorate);

        $governorate->update(['is_active' => ! $governorate->is_active]);

        return back()->with('status', __(
            $governorate->is_active
                ? 'shipping.messages.governorate_activated'
                : 'shipping.messages.governorate_deactivated',
            ['name' => $governorate->name_en],
        ));
    }

    public function destroy(Governorate $governorate): RedirectResponse
    {
        $this->authorize('delete', $governorate);

        // The foreign key restricts this already; checking first turns a
        // database error into a message the admin can act on.
        if ($governorate->areas()->exists()) {
            return back()->withErrors([
                'governorate' => __('shipping.messages.governorate_has_areas'),
            ]);
        }

        $governorate->delete();

        return redirect()
            ->route('admin.governorates.index')
            ->with('status', __('shipping.messages.governorate_deleted', ['name' => $governorate->name_en]));
    }

    /**
     * Convert the submitted EGP fee into the piastres the column stores.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $data['shipping_fee'] = Money::fromMajor($data['shipping_fee']);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
