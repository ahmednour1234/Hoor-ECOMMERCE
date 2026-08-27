<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use App\Repositories\ProductRepository;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ProductService $service,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        return view('admin.products.index', [
            'products'   => $this->products->paginateForAdmin($request->only([
                'search', 'category', 'status', 'stock', 'sort', 'direction',
            ])),
            'statistics' => $this->products->statistics(),
            'categories' => $this->categoryOptions(),
            'statuses'   => ProductStatus::options(),
            'filters'    => $request->only(['search', 'category', 'status', 'stock', 'sort', 'direction']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('admin.products.create', $this->formData(new Product([
            'status'              => ProductStatus::Draft,
            'low_stock_threshold' => 3,
        ])));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $product = $this->service->create($request->validated());

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', __('catalog.messages.created', ['name' => $product->name_en]));
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('admin.products.edit', $this->formData(
            $this->products->loadForForm($product),
        ));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $this->service->update($product, $request->validated());

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', __('catalog.messages.updated', ['name' => $product->name_en]));
    }

    /**
     * Soft-delete: the product leaves the catalog but stays restorable, and its
     * images remain on disk.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $this->service->delete($product);

        return redirect()
            ->route('admin.products.index')
            ->with('status', __('catalog.messages.deleted', ['name' => $product->name_en]));
    }

    /**
     * Shared payload for the create and edit screens.
     *
     * @return array<string, mixed>
     */
    private function formData(Product $product): array
    {
        return [
            'product'    => $product,
            'categories' => $this->categoryOptions(),
            'colors'     => Color::query()->active()->ordered()->get(),
            'sizes'      => Size::query()->active()->ordered()->get(),
            'statuses'   => ProductStatus::options(),
        ];
    }

    /**
     * Category picker options, indented so the tree structure stays readable
     * in a flat <select>.
     *
     * @return array<int, string>
     */
    private function categoryOptions(): array
    {
        $categories = Category::query()->with('parent')->ordered()->get();

        return $categories
            ->sortBy(fn (Category $category): string => ($category->parent?->name ?? $category->name).' '.$category->name)
            ->mapWithKeys(fn (Category $category): array => [
                $category->id => $category->parent_id === null
                    ? $category->name
                    : '— '.$category->name,
            ])
            ->all();
    }
}
