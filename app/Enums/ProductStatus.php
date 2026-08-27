<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Publication state of a catalog product.
 *
 * `draft` is the safe default for a half-entered product; only `published`
 * products are reachable from the storefront. `archived` retires a product
 * from the catalog while keeping it readable for historical orders.
 */
enum ProductStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return __('catalog.status.'.$this->value);
    }

    /**
     * Whether storefront visitors may see products in this state.
     */
    public function isVisible(): bool
    {
        return $this === self::Published;
    }

    /**
     * States a storefront query is allowed to return.
     *
     * @return list<string>
     */
    public static function visibleValues(): array
    {
        return [self::Published->value];
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            static fn (array $carry, self $case): array => $carry + [$case->value => $case->label()],
            [],
        );
    }
}
