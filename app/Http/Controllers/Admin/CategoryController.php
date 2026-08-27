<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::query()
            ->with(['parent'])
            ->withCount(['products', 'children'])
            ->when(
                filled($search = $request->string('search')->toString()),
                fn ($query) => $query->searchTranslation('name', $search),
            )
            ->ordered()
            ->paginate(25)
            ->withQueryString();

        return view('admin.categories.index', [
            'categories' => $categories,
            'filters'    => $request->only('search'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('admin.categories.create', [
            'category' => new Category(['is_active' => true, 'sort_order' => 0]),
            'parents'  => $this->parentOptions(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', Category::class);

        $category = $this->service->create(
            $request->safe()->except(['image', 'remove_image']),
            $request->file('image'),
        );

        return redirect()
            ->route('admin.categories.index')
            ->with('status', __('catalog.messages.category_created', ['name' => $category->name_en]));
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('admin.categories.edit', [
            'category' => $category,
            'parents'  => $this->parentOptions($category),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $this->service->update(
            $category,
            $request->safe()->except(['image', 'remove_image']),
            $request->file('image'),
            $request->boolean('remove_image'),
        );

        return redirect()
            ->route('admin.categories.index')
            ->with('status', __('catalog.messages.category_updated', ['name' => $category->name_en]));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        // The foreign key already restricts this; checking first turns a
        // database error into a message the admin can act on.
        if (! $this->service->canDelete($category)) {
            return back()->withErrors([
                'category' => __('catalog.messages.category_in_use'),
            ]);
        }

        $this->service->delete($category);

        return redirect()
            ->route('admin.categories.index')
            ->with('status', __('catalog.messages.category_deleted', ['name' => $category->name_en]));
    }

    /**
     * Categories eligible as a parent.
     *
     * Excludes the category being edited and its descendants, so the picker
     * cannot be used to create a cycle.
     *
     * @return array<int, string>
     */
    private function parentOptions(?Category $exclude = null): array
    {
        return Category::query()
            ->roots()
            ->ordered()
            ->when($exclude !== null, fn ($query) => $query->whereKeyNot($exclude->id))
            ->get()
            ->mapWithKeys(fn (Category $category): array => [$category->id => $category->name])
            ->all();
    }
}
