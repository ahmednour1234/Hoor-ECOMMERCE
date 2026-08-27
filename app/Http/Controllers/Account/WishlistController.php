<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\WishlistService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Saved products.
 *
 * The toggle answers JSON when asked, so the heart on a product card flips
 * without a reload — and stays a real form post otherwise, so it works with
 * JavaScript off. Same route, same rules, two renderings.
 */
class WishlistController extends Controller
{
    public function __construct(private readonly WishlistService $wishlist)
    {
    }

    public function index(Request $request): View
    {
        return view('store.account.wishlist', [
            'products' => $this->wishlist->paginate($request->user()),
        ]);
    }

    public function toggle(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $result = $this->wishlist->toggle($request->user(), $product);

        $message = $result['saved']
            ? __('account.wishlist.added')
            : __('account.wishlist.removed');

        if ($request->expectsJson()) {
            return response()->json($result + ['message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->wishlist->remove($request->user(), $product);

        return back()->with('status', __('account.wishlist.removed'));
    }
}
