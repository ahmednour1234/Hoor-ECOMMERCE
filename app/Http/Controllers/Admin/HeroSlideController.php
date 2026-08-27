<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HeroSlideRequest;
use App\Models\HeroSlide;
use App\Services\ContentService;
use App\Services\ImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Hero slides.
 *
 * Images go through ImageService so uploads land on the configured disk and
 * replaced files are removed only once the database write has committed.
 */
class HeroSlideController extends Controller
{
    private const DIRECTORY = 'hero';

    public function __construct(
        private readonly ImageService $images,
        private readonly ContentService $content,
    ) {
    }

    public function index(): View
    {
        $this->authorize('manage', \App\Models\Setting::class);

        return view('admin.content.slides.index', [
            'slides' => HeroSlide::query()->ordered()->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('manage', \App\Models\Setting::class);

        return view('admin.content.slides.create');
    }

    public function store(HeroSlideRequest $request): RedirectResponse
    {
        $this->authorize('manage', \App\Models\Setting::class);

        HeroSlide::create($request->slideData() + [
            'image_path' => $this->images->store($request->file('image'), self::DIRECTORY),
        ]);

        $this->content->flush();

        return redirect()
            ->route('admin.slides.index')
            ->with('status', __('content.slides.saved'));
    }

    public function edit(HeroSlide $slide): View
    {
        $this->authorize('manage', \App\Models\Setting::class);

        return view('admin.content.slides.edit', ['slide' => $slide]);
    }

    public function update(HeroSlideRequest $request, HeroSlide $slide): RedirectResponse
    {
        $this->authorize('manage', \App\Models\Setting::class);

        $data = $request->slideData();

        if ($request->hasFile('image')) {
            $previous = $slide->image_path;

            $data['image_path'] = $this->images->store($request->file('image'), self::DIRECTORY);

            // Removed after the transaction commits, so a failed write does not
            // leave the slide pointing at a file that no longer exists.
            $this->images->deleteAfterCommit($previous);
        }

        $slide->update($data);

        $this->content->flush();

        return redirect()
            ->route('admin.slides.index')
            ->with('status', __('content.slides.saved'));
    }

    public function destroy(HeroSlide $slide): RedirectResponse
    {
        $this->authorize('manage', \App\Models\Setting::class);

        $this->images->deleteAfterCommit($slide->image_path);

        $slide->delete();

        $this->content->flush();

        return back()->with('status', __('content.slides.deleted'));
    }
}
