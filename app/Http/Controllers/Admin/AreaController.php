<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Casts\Money;
use App\Http\Controllers\Controller;
use App\Http\Requests\Area\StoreAreaRequest;
use App\Http\Requests\Area\UpdateAreaRequest;
use App\Models\Area;
use App\Models\Governorate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Area management, nested under a governorate.
 *
 * Nesting matters: an area only means anything relative to its governorate, and
 * scoping every action to the parent is what stops one governorate's areas from
 * being edited through another's URL.
 */
class AreaController extends Controller
{
    public function index(Governorate $governorate): View
    {
        $this->authorize('viewAny', Area::class);

        return view('admin.areas.index', [
            'governorate' => $governorate,
            'areas'       => $governorate->areas()->ordered()->paginate(40),
        ]);
    }

    public function create(Governorate $governorate): View
    {
        $this->authorize('create', Area::class);

        return view('admin.areas.create', [
            'governorate' => $governorate,
            'area'        => new Area([
                'is_active'  => true,
                'sort_order' => (int) $governorate->areas()->max('sort_order') + 1,
            ]),
        ]);
    }

    public function store(StoreAreaRequest $request, Governorate $governorate): RedirectResponse
    {
        $this->authorize('create', Area::class);

        $area = $governorate->areas()->create($this->attributes($request->validated()));

        return redirect()
            ->route('admin.governorates.areas.index', $governorate)
            ->with('status', __('shipping.messages.area_created', ['name' => $area->name_en]));
    }

    public function edit(Governorate $governorate, Area $area): View
    {
        $this->authorize('update', $area);
        $this->ensureBelongsTo($governorate, $area);

        return view('admin.areas.edit', [
            'governorate' => $governorate,
            'area'        => $area,
        ]);
    }

    public function update(UpdateAreaRequest $request, Governorate $governorate, Area $area): RedirectResponse
    {
        $this->authorize('update', $area);
        $this->ensureBelongsTo($governorate, $area);

        $area->update($this->attributes($request->validated()));

        return redirect()
            ->route('admin.governorates.areas.index', $governorate)
            ->with('status', __('shipping.messages.area_updated', ['name' => $area->name_en]));
    }

    public function toggle(Governorate $governorate, Area $area): RedirectResponse
    {
        $this->authorize('update', $area);
        $this->ensureBelongsTo($governorate, $area);

        $area->update(['is_active' => ! $area->is_active]);

        return back()->with('status', __(
            $area->is_active
                ? 'shipping.messages.area_activated'
                : 'shipping.messages.area_deactivated',
            ['name' => $area->name_en],
        ));
    }

    public function destroy(Governorate $governorate, Area $area): RedirectResponse
    {
        $this->authorize('delete', $area);
        $this->ensureBelongsTo($governorate, $area);

        $area->delete();

        return redirect()
            ->route('admin.governorates.areas.index', $governorate)
            ->with('status', __('shipping.messages.area_deleted', ['name' => $area->name_en]));
    }

    /**
     * Refuse an area reached through the wrong governorate's URL.
     */
    private function ensureBelongsTo(Governorate $governorate, Area $area): void
    {
        abort_unless($area->governorate_id === $governorate->id, 404);
    }

    /**
     * A blank fee means "inherit the governorate's", which is not zero.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $data['shipping_fee'] = filled($data['shipping_fee'] ?? null)
            ? Money::fromMajor($data['shipping_fee'])
            : null;

        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
