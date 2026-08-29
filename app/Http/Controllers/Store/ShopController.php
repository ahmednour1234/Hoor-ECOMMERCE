<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Repositories\ProductRepository;
use App\Services\WishlistService;
use App\Support\ProductFilter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Shop / collection listing.
 *
 * Filter state lives entirely in the query string, so every view of the shop is
 * a shareable, bookmarkable URL and the browser's back button behaves. The
 * controller does no filtering itself — it parses the request into a validated
 * filter object and hands that to the repository.
 */
class ShopController extends Controller
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly WishlistService $wishlist,
    ) {
    }

    public function __invoke(Request $request): View
    {
        $filter = ProductFilter::fromRequest($request);

        $products = $this->products->paginateForShop($filter);

        return view('store.shop.index', [
            'filter'   => $filter,
            'products' => $products,
            'facets'   => $this->products->shopFacets(),
            'sorts'    => ProductFilter::availableSorts(),
            'saved'    => $this->savedProductIds($request, $products),
        ]);
    }

    /**
     * Which products on this page the customer has already saved.
     *
     * Resolved here, in one query for the whole grid, because the card cannot
     * ask without doing it once per heart. Without this every heart rendered
     * empty, so a saved product looked unsaved — and the next click took it
     * off the wishlist instead of putting it on.
     *
     * @param  \Illuminate\Contracts\Pagination\Paginator<int, \App\Models\Product>  $products
     * @return list<int>
     */
    private function savedProductIds(Request $request, $products): array
    {
        $user = $request->user();

        if ($user === null) {
            // A guest's wishlist lives in the browser; the button reads it
            // there and fills its own heart in.
            return [];
        }

        return $this->wishlist->savedAmong(
            $user,
            $products->getCollection()->pluck('id')->all(),
        );
    }
}
