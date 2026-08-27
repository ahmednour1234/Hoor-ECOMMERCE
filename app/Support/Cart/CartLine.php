<?php

declare(strict_types=1);

namespace App\Support\Cart;

use App\Casts\Money;
use App\Enums\StockStatus;
use App\Models\ProductVariant;

/**
 * One hydrated cart line.
 *
 * Built fresh on every read from the variant row, so the price, name, image and
 * availability shown to the customer are always the current database values.
 * The session holds only a variant id and a quantity — never any of this.
 */
final readonly class CartLine
{
    public function __construct(
        public ProductVariant $variant,
        public int $quantity,
        /** Quantity actually available, when it is lower than requested. */
        public ?int $availableQuantity = null,
    ) {
    }

    public function product(): \App\Models\Product
    {
        return $this->variant->product;
    }

    /**
     * Unit price, read from the database rather than from the session.
     */
    public function unitPrice(): int
    {
        return $this->variant->effectivePrice();
    }

    public function lineTotal(): int
    {
        return $this->unitPrice() * $this->quantity;
    }

    public function isOnSale(): bool
    {
        return $this->variant->isOnSale();
    }

    /**
     * Pre-discount line total, for showing what the saving is worth.
     */
    public function lineTotalBeforeDiscount(): int
    {
        return $this->variant->basePrice() * $this->quantity;
    }

    public function stockStatus(): StockStatus
    {
        return $this->variant->stockStatus();
    }

    /**
     * Whether this line can still be fulfilled at the requested quantity.
     */
    public function isAvailable(): bool
    {
        return $this->variant->is_active && $this->variant->canFulfil($this->quantity);
    }

    /**
     * Whether stock fell below the requested quantity since it was added.
     */
    public function wasReduced(): bool
    {
        return $this->availableQuantity !== null && $this->availableQuantity < $this->quantity;
    }

    public function formattedUnitPrice(): string
    {
        return Money::format($this->unitPrice());
    }

    public function formattedLineTotal(): string
    {
        return Money::format($this->lineTotal());
    }
}
