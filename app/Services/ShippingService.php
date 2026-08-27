<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Area;
use App\Models\Governorate;
use App\Support\Cart\Cart;
use Illuminate\Support\Collection;

/**
 * Decides what delivery costs.
 *
 * Every shipping figure in the application comes from here. Nothing computes a
 * fee inline and no fee is ever hardcoded: the numbers live in the governorates
 * and areas tables, which the admin owns.
 *
 * Resolution is deliberately simple and explicit:
 *
 *   1. If an area is chosen and it sets its own fee, that fee applies.
 *   2. Otherwise the governorate's fee applies.
 *
 * A nullable area fee is what keeps the system manageable — an admin maintains
 * 27 governorate rates and overrides only the districts that genuinely cost
 * more to reach.
 */
class ShippingService
{
    /**
     * The delivery fee for a destination, in piastres.
     *
     * The area is optional: a customer may choose a governorate alone, and the
     * area is only consulted when it belongs to that governorate — a mismatched
     * pair falls back to the governorate rather than silently pricing against
     * the wrong place.
     */
    public function feeFor(Governorate $governorate, ?Area $area = null): int
    {
        if ($area !== null
            && $area->governorate_id === $governorate->id
            && $area->overridesFee()) {
            return (int) $area->shipping_fee;
        }

        return (int) $governorate->shipping_fee;
    }

    /**
     * Fee for a destination given only its identifiers.
     *
     * Returns null when the destination cannot be delivered to, so callers must
     * decide what to do rather than being handed a zero that looks like free
     * shipping.
     */
    public function feeForIds(int $governorateId, ?int $areaId = null): ?int
    {
        $governorate = Governorate::query()->active()->find($governorateId);

        if ($governorate === null) {
            return null;
        }

        $area = null;

        if ($areaId !== null) {
            $area = Area::query()
                ->active()
                ->where('governorate_id', $governorate->id)
                ->find($areaId);

            // An area id that does not belong to this governorate is a mismatch
            // the caller should know about, not something to quietly ignore.
            if ($area === null) {
                return null;
            }
        }

        return $this->feeFor($governorate, $area);
    }

    /**
     * A full quote for a cart being delivered to a destination.
     *
     * The cart is accepted so that weight- or value-based rules can be added
     * here later without changing a single caller.
     *
     * @return array{fee: int, subtotal: int, total: int, governorate: Governorate, area: Area|null, delivery_days: string}
     */
    public function quote(Cart $cart, Governorate $governorate, ?Area $area = null): array
    {
        $fee = $this->feeFor($governorate, $area);
        $subtotal = $cart->subtotal();

        return [
            'fee'           => $fee,
            'subtotal'      => $subtotal,
            'total'         => $subtotal + $fee,
            'governorate'   => $governorate,
            'area'          => $area,
            'delivery_days' => $governorate->deliveryWindow(),
        ];
    }

    /**
     * Governorates a customer may order to, with their areas.
     *
     * @return Collection<int, Governorate>
     */
    public function deliverableGovernorates(): Collection
    {
        return Governorate::query()
            ->active()
            ->with(['areas' => fn ($query) => $query->active()->ordered()])
            ->ordered()
            ->get();
    }

    /**
     * Active areas within one governorate, for a dependent select.
     *
     * @return Collection<int, Area>
     */
    public function areasFor(Governorate $governorate): Collection
    {
        return $governorate->areas()->active()->ordered()->get();
    }

    /**
     * Whether a governorate/area pair can be delivered to right now.
     */
    public function canDeliverTo(int $governorateId, ?int $areaId = null): bool
    {
        return $this->feeForIds($governorateId, $areaId) !== null;
    }

    /**
     * The cheapest and dearest active delivery fees, for messaging such as
     * "shipping from EGP 40".
     *
     * @return array{min: int, max: int}
     */
    public function feeRange(): array
    {
        $fees = Governorate::query()->active()->pluck('shipping_fee');

        if ($fees->isEmpty()) {
            return ['min' => 0, 'max' => 0];
        }

        return ['min' => (int) $fees->min(), 'max' => (int) $fees->max()];
    }
}
