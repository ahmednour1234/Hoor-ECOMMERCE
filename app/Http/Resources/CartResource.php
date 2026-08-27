<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Casts\Money;
use App\Support\Cart\Cart;
use App\Support\Cart\CartLine;

/**
 * The cart, shaped for the browser.
 *
 * Every AJAX action returns this, so the page can redraw itself from the
 * server's recalculated figures rather than doing arithmetic of its own. The
 * client never computes a total — it displays one.
 */
final readonly class CartResource
{
    public static function toArray(Cart $cart): array
    {
        return [
            'count'    => $cart->totalQuantity(),
            'lines'    => $cart->lines->map(self::line(...))->values()->all(),
            'notices'  => $cart->notices,
            'empty'    => $cart->isEmpty(),
            'ready'    => $cart->isCheckoutReady(),

            'totals' => [
                'subtotal'           => $cart->subtotal(),
                'subtotal_formatted' => $cart->formattedSubtotal(),
                'savings'            => $cart->savings(),
                'savings_formatted'  => $cart->formattedSavings(),
                'has_savings'        => $cart->hasSavings(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function line(CartLine $line): array
    {
        $variant = $line->variant;

        return [
            'variant_id'      => $variant->id,
            'quantity'        => $line->quantity,
            'max_quantity'    => $variant->stock_quantity,
            'stock'           => $variant->stock_quantity,
            'status'          => $line->stockStatus()->value,
            'available'       => $line->isAvailable(),
            'was_reduced'     => $line->wasReduced(),

            'unit_price'      => $line->unitPrice(),
            'unit_formatted'  => $line->formattedUnitPrice(),
            'total'           => $line->lineTotal(),
            'total_formatted' => $line->formattedLineTotal(),
            'on_sale'         => $line->isOnSale(),
        ];
    }

    /**
     * A minimal payload for pages that only need the badge.
     */
    public static function summary(Cart $cart): array
    {
        return [
            'count'              => $cart->totalQuantity(),
            'subtotal'           => $cart->subtotal(),
            'subtotal_formatted' => $cart->formattedSubtotal(),
        ];
    }
}
