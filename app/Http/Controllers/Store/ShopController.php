<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Repositories\ProductRepository;
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
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function __invoke(Request $request): View
    {
        $filter = ProductFilter::fromRequest($request);

        return view('store.shop.index', [
            'filter'   => $filter,
            'products' => $this->products->paginateForShop($filter),
            'facets'   => $this->products->shopFacets(),
            'sorts'    => ProductFilter::availableSorts(),
        ]);
    }
}
