<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BannerRequest;
use App\Models\Banner;
use App\Models\Setting;
use App\Services\ImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Promotional banners.
 */
class BannerController extends Controller
{
    private const DIRECTORY = 'banners';

    public function __construct(private readonly ImageService $images)
    {
    }

    public function index(): View
    {
        $this->authorize('manage', Setting::class);

        return view('admin.content.banners.index', [
            // Grouped by placement, which is how staff think about them: "what
            // is on the announcement bar right now".
            'banners' => Banner::query()->ordered()->get()->groupBy('placement'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('manage', Setting::class);

        return view('admin.content.banners.create');
    }

    public function store(BannerRequest $request): RedirectResponse
    {
        $this->authorize('manage', Setting::class);

        $data = $request->bannerData();

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->images->store($request->file('image'), self::DIRECTORY);
        }

        Banner::create($data);

        return redirect()
            ->route('admin.banners.index')
            ->with('status', __('content.banners.saved'));
    }

    public function edit(Banner $banner): View
    {
        $this->authorize('manage', Setting::class);

        return view('admin.content.banners.edit', ['banner' => $banner]);
    }

    public function update(BannerRequest $request, Banner $banner): RedirectResponse
    {
        $this->authorize('manage', Setting::class);

        $data = $request->bannerData();

        if ($request->hasFile('image')) {
            $previous = $banner->image_path;

            $data['image_path'] = $this->images->store($request->file('image'), self::DIRECTORY);

            $this->images->deleteAfterCommit($previous);
        }

        $banner->update($data);

        return redirect()
            ->route('admin.banners.index')
            ->with('status', __('content.banners.saved'));
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $this->authorize('manage', Setting::class);

        $this->images->deleteAfterCommit($banner->image_path);

        $banner->delete();

        return back()->with('status', __('content.banners.deleted'));
    }
}
