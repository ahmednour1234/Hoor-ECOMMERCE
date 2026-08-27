<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Back-office access levels.
 *
 * `customer` is the default for every self-registered storefront account and
 * carries no dashboard access whatsoever.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Staff = 'staff';
    case Customer = 'customer';

    /**
     * Roles allowed to reach the admin dashboard.
     *
     * @return list<self>
     */
    public static function backOffice(): array
    {
        return [self::Admin, self::Staff];
    }

    public function canAccessAdmin(): bool
    {
        return in_array($this, self::backOffice(), strict: true);
    }

    public function label(): string
    {
        return __('common.roles.'.$this->value);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            static fn (array $carry, self $role): array => $carry + [$role->value => $role->label()],
            [],
        );
    }
}
