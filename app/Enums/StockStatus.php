<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Derived availability of a variant or product.
 *
 * This is never stored: it is computed from the variant stock quantities so
 * that quantity and status can never disagree.
 */
enum StockStatus: string
{
    case InStock = 'in_stock';
    case LowStock = 'low_stock';
    case OutOfStock = 'out_of_stock';

    /**
     * Classify a quantity against the threshold at which staff should reorder.
     */
    public static function forQuantity(int $quantity, int $lowStockThreshold): self
    {
        return match (true) {
            $quantity <= 0                  => self::OutOfStock,
            $quantity <= $lowStockThreshold => self::LowStock,
            default                         => self::InStock,
        };
    }

    public function isPurchasable(): bool
    {
        return $this !== self::OutOfStock;
    }

    public function label(): string
    {
        return __('catalog.stock.'.$this->value);
    }

    /**
     * Badge variant used by the shared <x-ui.badge> component.
     */
    public function badge(): string
    {
        return match ($this) {
            self::InStock    => 'success',
            self::LowStock   => 'warning',
            self::OutOfStock => 'danger',
        };
    }
}
