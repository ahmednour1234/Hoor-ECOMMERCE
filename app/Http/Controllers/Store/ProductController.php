<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\VariantResolver;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Product detail page.
 *
 * The variant matrix handed to the view contains only real, active rows, so the
 * selector cannot offer a combination that does not exist. Everything the page
 * allows is still re-validated when the customer adds to cart.
 */
class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly VariantResolver $variants,
    ) {
    }

    public function show(Product $product): View
    {
        // Route binding resolves by slug regardless of status, so unpublished
        // products are hidden here rather than leaking through a shared link.
        if ($product->status !== ProductStatus::Published) {
            throw new NotFoundHttpException();
        }

        $product = $this->products->loadForDetail($product);

        return view('store.products.show', [
            'product'  => $product,
            'colors'   => $this->variants->colors($product),
            'sizes'    => $this->variants->sizes($product),
            'matrix'   => $this->variants->matrix($product),
            'selected' => $this->variants->defaultVariant($product),
            'related'  => $this->products->related($product),
        ]);
    }
}
