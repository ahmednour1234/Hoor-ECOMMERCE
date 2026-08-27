<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Size\StoreSizeRequest;
use App\Http\Requests\Size\UpdateSizeRequest;
use App\Models\Size;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SizeController extends Controller
{
    public function index(): View
    {
        return view('admin.sizes.index', [
            'sizes' => Size::query()->withCount('variants')->ordered()->paginate(30),
        ]);
    }

    public function create(): View
    {
        return view('admin.sizes.create', [
            'size' => new Size([
                'is_active'  => true,
                // Append to the end of the run by default.
                'sort_order' => (int) Size::query()->max('sort_order') + 1,
            ]),
        ]);
    }

    public function store(StoreSizeRequest $request): RedirectResponse
    {
        $size = Size::query()->create($request->validated());

        return redirect()
            ->route('admin.sizes.index')
            ->with('status', __('catalog.messages.size_created', ['name' => $size->name_en]));
    }

    public function edit(Size $size): View
    {
        return view('admin.sizes.edit', ['size' => $size]);
    }

    public function update(UpdateSizeRequest $request, Size $size): RedirectResponse
    {
        $size->update($request->validated());

        return redirect()
            ->route('admin.sizes.index')
            ->with('status', __('catalog.messages.size_updated', ['name' => $size->name_en]));
    }

    public function destroy(Size $size): RedirectResponse
    {
        if ($size->variants()->exists()) {
            return back()->withErrors(['size' => __('catalog.messages.size_in_use')]);
        }

        $size->delete();

        return redirect()
            ->route('admin.sizes.index')
            ->with('status', __('catalog.messages.size_deleted', ['name' => $size->name_en]));
    }
}
