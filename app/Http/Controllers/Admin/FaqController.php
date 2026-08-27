<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The questions shown on the contact page.
 *
 * Gated by the same content permission as slides and banners: they are all one
 * job, and splitting the permission would give five that are always granted
 * together.
 */
class FaqController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage', Setting::class);

        return view('admin.content.faqs.index', [
            'faqs' => Faq::query()->ordered()->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('manage', Setting::class);

        return view('admin.content.faqs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage', Setting::class);

        Faq::create($this->validated($request));

        return redirect()
            ->route('admin.faqs.index')
            ->with('status', __('content.faqs.saved'));
    }

    public function edit(Faq $faq): View
    {
        $this->authorize('manage', Setting::class);

        return view('admin.content.faqs.edit', ['faq' => $faq]);
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $this->authorize('manage', Setting::class);

        $faq->update($this->validated($request));

        return redirect()
            ->route('admin.faqs.index')
            ->with('status', __('content.faqs.saved'));
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $this->authorize('manage', Setting::class);

        $faq->delete();

        return back()->with('status', __('content.faqs.deleted'));
    }

    /**
     * Both languages are required: a question that exists in only one would
     * leave the other locale's accordion with a blank row.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'question_ar' => ['required', 'string', 'max:255'],
            'question_en' => ['required', 'string', 'max:255'],
            'answer_ar'   => ['required', 'string', 'max:2000'],
            'answer_en'   => ['required', 'string', 'max:2000'],
            'position'    => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        return array_merge($data, [
            'placement' => 'contact',
            'position'  => (int) ($data['position'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ]);
    }
}
