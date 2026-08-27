<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CouponRequest;
use App\Models\Coupon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Coupon management.
 */
class CouponController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Coupon::class);

        $search = trim((string) $request->query('search'));

        return view('admin.coupons.index', [
            'coupons' => Coupon::query()
                ->when($search !== '', fn ($query) => $query->where('code', 'like', "%{$search}%"))
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),

            'search' => $search,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Coupon::class);

        return view('admin.coupons.create');
    }

    public function store(CouponRequest $request): RedirectResponse
    {
        $this->authorize('create', Coupon::class);

        $coupon = Coupon::create($request->couponData());

        return redirect()
            ->route('admin.coupons.index')
            ->with('status', __('coupons.messages.saved', ['code' => $coupon->code]));
    }

    /**
     * The coupon, and who has used it.
     */
    public function show(Coupon $coupon): View
    {
        $this->authorize('view', $coupon);

        return view('admin.coupons.show', [
            'coupon' => $coupon,

            'redemptions' => $coupon->redemptions()
                ->with(['order', 'user'])
                ->latest('created_at')
                ->paginate(20),
        ]);
    }

    public function edit(Coupon $coupon): View
    {
        $this->authorize('update', $coupon);

        return view('admin.coupons.edit', ['coupon' => $coupon]);
    }

    public function update(CouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $this->authorize('update', $coupon);

        $coupon->update($request->couponData());

        return redirect()
            ->route('admin.coupons.index')
            ->with('status', __('coupons.messages.saved', ['code' => $coupon->code]));
    }

    /**
     * Switch a coupon on or off.
     *
     * The usual way a campaign ends, and safer than deletion: the redemptions
     * stay attached to the orders that used them.
     */
    public function toggle(Coupon $coupon): RedirectResponse
    {
        $this->authorize('update', $coupon);

        $coupon->update(['is_active' => ! $coupon->is_active]);

        return back()->with('status', __('coupons.messages.'.($coupon->is_active ? 'enabled' : 'disabled'), [
            'code' => $coupon->code,
        ]));
    }

    /**
     * Delete a coupon that was never used.
     *
     * A coupon with redemptions is deactivated instead: deleting it would
     * cascade away the record of discounts real orders were given.
     */
    public function destroy(Coupon $coupon): RedirectResponse
    {
        $this->authorize('delete', $coupon);

        if ($coupon->redemptions()->exists()) {
            return back()->withErrors(['code' => __('coupons.errors.has_redemptions')]);
        }

        $coupon->delete();

        return redirect()
            ->route('admin.coupons.index')
            ->with('status', __('coupons.messages.deleted'));
    }
}
