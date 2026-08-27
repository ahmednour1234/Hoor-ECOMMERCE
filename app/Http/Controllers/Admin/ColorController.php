<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Color\StoreColorRequest;
use App\Http\Requests\Color\UpdateColorRequest;
use App\Models\Color;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ColorController extends Controller
{
    public function index(): View
    {
        return view('admin.colors.index', [
            'colors' => Color::query()->withCount('variants')->ordered()->paginate(30),
        ]);
    }

    public function create(): View
    {
        return view('admin.colors.create', [
            'color' => new Color(['is_active' => true, 'hex' => '#2B4166', 'sort_order' => 0]),
        ]);
    }

    public function store(StoreColorRequest $request): RedirectResponse
    {
        $color = Color::query()->create($request->validated());

        return redirect()
            ->route('admin.colors.index')
            ->with('status', __('catalog.messages.color_created', ['name' => $color->name_en]));
    }

    public function edit(Color $color): View
    {
        return view('admin.colors.edit', ['color' => $color]);
    }

    public function update(UpdateColorRequest $request, Color $color): RedirectResponse
    {
        $color->update($request->validated());

        return redirect()
            ->route('admin.colors.index')
            ->with('status', __('catalog.messages.color_updated', ['name' => $color->name_en]));
    }

    /**
     * Colours in use by a variant are deactivated rather than deleted, so
     * existing variants keep the attribute they were sold under.
     */
    public function destroy(Color $color): RedirectResponse
    {
        if ($color->variants()->exists()) {
            return back()->withErrors(['color' => __('catalog.messages.color_in_use')]);
        }

        $color->delete();

        return redirect()
            ->route('admin.colors.index')
            ->with('status', __('catalog.messages.color_deleted', ['name' => $color->name_en]));
    }
}
