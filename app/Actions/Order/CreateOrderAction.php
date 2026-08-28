<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InsufficientStockException;
use App\Models\Area;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Support\Cart\Cart;
use App\Support\Cart\CartLine;
use Illuminate\Support\Facades\DB;

/**
 * Turns a validated basket into an order.
 *
 * This is the only place stock is decremented, and the only place an order is
 * created. Everything happens inside one transaction:
 *
 *   1. Every variant in the basket is re-read WITH A ROW LOCK, in a stable
 *      order. Locking is what prevents overselling: two customers racing for
 *      the last unit are serialised by the database rather than by hope, and
 *      the second one fails cleanly instead of both succeeding.
 *
 *   2. Stock is checked against the locked rows — not against what the cart
 *      believed a moment ago — and decremented.
 *
 *   3. The order, its items, its address and its opening history entry are
 *      written from server-computed figures.
 *
 * If anything fails, the transaction rolls back and no stock was consumed.
 *
 * Variants are locked in ascending id order for a reason: two orders containing
 * the same two products in different sequences would otherwise be able to grab
 * one lock each and wait forever on the other. A consistent order makes that
 * deadlock impossible.
 */
class CreateOrderAction
{
    public function __construct(private readonly GenerateOrderNumber $numbers)
    {
    }

    /**
     * @param  array<string, mixed>  $details  Validated customer details.
     * @param  array{subtotal: int, discount: int, shipping: int, total: int, coupon_id: int|null, coupon_code: string|null}  $totals
     *
     * @throws InsufficientStockException
     */
    public function execute(
        Cart $cart,
        array $details,
        Governorate $governorate,
        ?Area $area,
        array $totals,
        ?int $userId = null,
    ): Order {
        return DB::transaction(function () use ($cart, $details, $governorate, $area, $totals, $userId): Order {
            $variants = $this->lockVariants($cart);

            $this->assertStockAvailable($cart, $variants);

            $order = $this->createOrder($details, $totals, $userId);

            $this->createItems($order, $cart, $variants);
            $this->createAddress($order, $details, $governorate, $area, $totals['shipping']);
            $this->decrementStock($cart, $variants);
            $this->recordOpeningHistory($order);

            return $order;
        });
    }

    /**
     * Re-read every variant under a row lock, in ascending id order.
     *
     * The consistent ordering is deliberate — see the class comment.
     *
     * @return \Illuminate\Support\Collection<int, ProductVariant>
     */
    private function lockVariants(Cart $cart): \Illuminate\Support\Collection
    {
        $ids = $cart->lines
            ->map(fn (CartLine $line): int => $line->variant->id)
            ->sort()
            ->values()
            ->all();

        return ProductVariant::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    /**
     * Verify the locked rows can still satisfy the basket.
     *
     * This check is the one that counts. The cart page and the checkout form
     * both checked stock earlier, but only these locked rows reflect what is
     * true right now, with no one else able to change it until we commit.
     *
     * @param  \Illuminate\Support\Collection<int, ProductVariant>  $variants
     *
     * @throws InsufficientStockException
     */
    private function assertStockAvailable(Cart $cart, \Illuminate\Support\Collection $variants): void
    {
        $shortfalls = [];

        foreach ($cart->lines as $line) {
            $variant = $variants->get($line->variant->id);

            if ($variant === null || ! $variant->is_active) {
                $shortfalls[] = [
                    'name'      => $line->product()->name,
                    'variant'   => $line->variant->label(),
                    'requested' => $line->quantity,
                    'available' => 0,
                ];

                continue;
            }

            if ($variant->stock_quantity < $line->quantity) {
                $shortfalls[] = [
                    'name'      => $line->product()->name,
                    'variant'   => $line->variant->label(),
                    'requested' => $line->quantity,
                    'available' => $variant->stock_quantity,
                ];
            }
        }

        if ($shortfalls !== []) {
            throw InsufficientStockException::forLines($shortfalls);
        }
    }

    /**
     * @param  array<string, mixed>  $details
     * @param  array<string, mixed>  $totals
     */
    private function createOrder(array $details, array $totals, ?int $userId): Order
    {
        return Order::query()->create([
            'number'         => $this->numbers->generate(),
            'user_id'        => $userId,
            'status'         => OrderStatus::Pending,
            'payment_method' => PaymentMethod::CashOnDelivery,
            'subtotal'       => $totals['subtotal'],
            'discount'       => $totals['discount'],
            'shipping'       => $totals['shipping'],
            'total'          => $totals['total'],
            'coupon_id'      => $totals['coupon_id'] ?? null,
            'coupon_code'    => $totals['coupon_code'] ?? null,
            'notes'          => $details['notes'] ?? null,
        ]);
    }

    /**
     * Write each line as a snapshot.
     *
     * Names, SKU, colour, size, image and price are copied onto the row rather
     * than referenced, so the order reads correctly forever — even if the
     * product is renamed, repriced or deleted.
     *
     * @param  \Illuminate\Support\Collection<int, ProductVariant>  $variants
     */
    private function createItems(Order $order, Cart $cart, \Illuminate\Support\Collection $variants): void
    {
        foreach ($cart->lines as $line) {
            $variant = $variants->get($line->variant->id);
            $product = $line->product();

            // Prices come from the freshly locked row, not from the cart's
            // earlier reading, so what is charged is what the catalog says now.
            $unitPrice = $variant->effectivePrice();
            $basePrice = $variant->basePrice();

            $order->items()->create([
                'product_id'         => $product->id,
                'product_variant_id' => $variant->id,

                'product_name_ar' => $product->name_ar,
                'product_name_en' => $product->name_en,
                'sku'             => $variant->sku,
                'color_name_ar'   => $variant->color?->name_ar,
                'color_name_en'   => $variant->color?->name_en,
                'size_name_ar'    => $variant->size?->name_ar,
                'size_name_en'    => $variant->size?->name_en,
                'image_path'      => $product->primaryImage?->path,

                'unit_price'                 => $unitPrice,
                'unit_price_before_discount' => $basePrice,
                'quantity'                   => $line->quantity,
                'line_total'                 => $unitPrice * $line->quantity,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function createAddress(
        Order $order,
        array $details,
        Governorate $governorate,
        ?Area $area,
        int $shippingFee,
    ): void {
        $order->address()->create([
            'full_name' => $details['full_name'],
            'phone'     => $details['phone'],
            'phone_alt' => $details['phone_alt'] ?? null,

            // Snapshotted like everything else on the address: where the
            // confirmation went is part of the record of the order.
            'email'     => $details['email'] ?? null,

            'governorate_id'      => $governorate->id,
            'governorate_name_ar' => $governorate->name_ar,
            'governorate_name_en' => $governorate->name_en,

            'area_id'      => $area?->id,
            'area_name_ar' => $area?->name_ar,
            'area_name_en' => $area?->name_en,

            'address'      => $details['address'],
            'landmark'     => $details['landmark'] ?? null,

            // The pin she dropped, so the courier has a location as well as a
            // description.
            'latitude'     => $details['latitude'] ?? null,
            'longitude'    => $details['longitude'] ?? null,
            'shipping_fee' => $shippingFee,
        ]);
    }

    /**
     * Commit the stock.
     *
     * A conditional decrement rather than a plain subtraction: the WHERE clause
     * is a second line of defence, so even if the lock were somehow bypassed
     * the update could not drive stock negative.
     *
     * @param  \Illuminate\Support\Collection<int, ProductVariant>  $variants
     *
     * @throws InsufficientStockException
     */
    private function decrementStock(Cart $cart, \Illuminate\Support\Collection $variants): void
    {
        foreach ($cart->lines as $line) {
            $variant = $variants->get($line->variant->id);

            $affected = ProductVariant::query()
                ->whereKey($variant->id)
                ->where('stock_quantity', '>=', $line->quantity)
                ->decrement('stock_quantity', $line->quantity);

            if ($affected === 0) {
                throw InsufficientStockException::forLines([[
                    'name'      => $line->product()->name,
                    'variant'   => $variant->label(),
                    'requested' => $line->quantity,
                    'available' => $variant->fresh()->stock_quantity,
                ]]);
            }
        }
    }

    /**
     * Open the audit trail.
     *
     * Every order starts with an explicit entry, so its history is complete
     * from the first moment rather than beginning at the first admin action.
     */
    private function recordOpeningHistory(Order $order): void
    {
        $order->statusHistory()->create([
            'from_status' => null,
            'to_status'   => OrderStatus::Pending,
            'user_id'     => null,
            'note'        => __('orders.history.placed'),
        ]);
    }
}
