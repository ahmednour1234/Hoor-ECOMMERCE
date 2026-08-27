<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A return request that cannot be raised, with the reason a customer can act on.
 *
 * Private constructor and named factories, so every rejection has to state
 * which rule it broke rather than passing an ad-hoc string.
 */
class ReturnNotAllowedException extends RuntimeException
{
    private function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }

    public static function notDelivered(): self
    {
        return new self('not_delivered', __('returns.errors.not_delivered'));
    }

    public static function windowClosed(int $days): self
    {
        return new self('window_closed', __('returns.errors.window_closed', ['days' => $days]));
    }

    public static function noItems(): self
    {
        return new self('no_items', __('returns.errors.no_items'));
    }

    /**
     * More units named than the order actually holds, or than remain
     * un-returned.
     */
    public static function quantityExceeded(string $product): self
    {
        return new self('quantity_exceeded', __('returns.errors.quantity_exceeded', ['product' => $product]));
    }

    public static function itemNotOnOrder(): self
    {
        return new self('item_not_on_order', __('returns.errors.item_not_on_order'));
    }

    public static function tooManyOpen(int $max): self
    {
        return new self('too_many_open', __('returns.errors.too_many_open', ['max' => $max]));
    }

    public static function alreadyDecided(): self
    {
        return new self('already_decided', __('returns.errors.already_decided'));
    }

    /**
     * The request is not at a status this operation can act on.
     */
    public static function invalidTransition(\App\Enums\ReturnStatus $from, \App\Enums\ReturnStatus $to): self
    {
        return new self('invalid_transition', __('returns.errors.invalid_transition', [
            'from' => $from->label(),
            'to'   => $to->label(),
        ]));
    }

    // --------------------------------------------------------------- Exchanges

    /**
     * An exchange line that named no replacement.
     */
    public static function replacementRequired(string $product): self
    {
        return new self('replacement_required', __('returns.errors.replacement_required', [
            'product' => $product,
        ]));
    }

    /**
     * The replacement belongs to a different product.
     */
    public static function replacementNotOnProduct(): self
    {
        return new self('replacement_not_on_product', __('returns.errors.replacement_not_on_product'));
    }

    /**
     * The replacement is retired.
     */
    public static function replacementInactive(string $sku): self
    {
        return new self('replacement_inactive', __('returns.errors.replacement_inactive', ['sku' => $sku]));
    }

    /**
     * The replacement has sold out — the case that makes re-checking at
     * approval time necessary rather than merely tidy.
     */
    public static function replacementOutOfStock(string $sku): self
    {
        return new self('replacement_out_of_stock', __('returns.errors.replacement_out_of_stock', ['sku' => $sku]));
    }

    /**
     * Build the right exchange failure from an ExchangeAvailability reason.
     */
    public static function forExchangeReason(string $reason, string $sku): self
    {
        return match ($reason) {
            'different_product' => self::replacementNotOnProduct(),
            'inactive'          => self::replacementInactive($sku),
            default             => self::replacementOutOfStock($sku),
        };
    }

    /**
     * More units reported back than the request covers.
     */
    public static function receivedTooMany(string $product): self
    {
        return new self('received_too_many', __('returns.errors.received_too_many', ['product' => $product]));
    }
}
