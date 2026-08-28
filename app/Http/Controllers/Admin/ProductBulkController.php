<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Changing the status of several products at once.
 *
 * The case this exists for: an import lands fifty products as drafts, and
 * publishing them one at a time is fifty page loads.
 *
 * Only status changes. Deleting in bulk was left out deliberately — one
 * mis-click there removes fifty products, and the saving over deleting them
 * individually is not worth that.
 */
class ProductBulkController extends Controller
{
    /**
     * How many may be changed in one request.
     *
     * A bound on the work a single click can cause, and on the size of the
     * payload a crafted form could submit.
     */
    private const MAX = 200;

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::enum(ProductStatus::class)],

            'products'   => ['required', 'array', 'max:'.self::MAX],
            'products.*' => ['integer', Rule::exists('products', 'id')],
        ], [], [
            'products' => __('catalog.products.title'),
        ]);

        $status = ProductStatus::from($validated['action']);
        $ids = array_map('intval', $validated['products']);

        /*
         * Authorised per product rather than against the class: the policy's
         * update() takes an instance, and checking one product would not
         * actually authorise the others.
         */
        foreach (Product::query()->whereIn('id', $ids)->get() as $product) {
            $this->authorize('update', $product);
        }

        /*
         * published_at is stamped the first time a product is published and
         * then left alone: it is the date the shop first offered the piece,
         * and re-publishing after a spell in drafts should not make an old
         * product look new. The homepage's "new in" rail orders by it.
         */
        $updates = ['status' => $status];

        if ($status === ProductStatus::Published) {
            Product::query()
                ->whereIn('id', $ids)
                ->whereNull('published_at')
                ->update(['published_at' => now()]);
        }

        $changed = Product::query()->whereIn('id', $ids)->update($updates);

        return back()->with('status', __('catalog.products.bulk_done', [
            'count'  => $changed,
            'status' => $status->label(),
        ]));
    }
}
