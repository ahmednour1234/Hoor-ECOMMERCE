<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Repositories\OrderRepository;
use App\Services\WishlistService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The customer's account overview.
 *
 * A short summary and the way in to everything else, so a returning customer
 * lands somewhere useful rather than on a menu.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly WishlistService $wishlist,
    ) {
    }

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('store.account.dashboard', [
            'recentOrders'  => $user->orders()->with('address')->withCount('items')->limit(3)->get(),
            'orderCount'    => $user->orders()->count(),
            'addressCount'  => $user->addresses()->count(),
            'wishlistCount' => $this->wishlist->count($user),
            'openReturns'   => $user->returnRequests()->pending()->count(),
        ]);
    }
}
