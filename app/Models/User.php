<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property UserRole $role
 * @property bool $is_active
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => UserRole::class,
            'is_active'         => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Whether this account may reach the back office at all.
     *
     * Deactivated accounts are refused regardless of their role so that access
     * can be revoked without deleting the user or rewriting their history.
     */
    public function canAccessAdmin(): bool
    {
        return $this->is_active && $this->role->canAccessAdmin();
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeBackOffice(Builder $query): Builder
    {
        return $query->whereIn('role', array_column(UserRole::backOffice(), 'value'));
    }

    // ---------------------------------------------------------------- Account

    /**
     * Orders this customer placed while signed in.
     *
     * A guest checkout is not linked here even if the same person later
     * registers: the order carries no user_id, and inferring ownership from a
     * matching phone number would expose one customer's order to anyone who
     * knows their number.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest('created_at');
    }

    /** @return HasMany<CustomerAddress, $this> */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /** @return HasMany<Wishlist, $this> */
    public function wishlist(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Products this customer has saved.
     *
     * @return BelongsToMany<Product, $this>
     */
    public function wishlistProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'wishlists')->withTimestamps();
    }

    /** @return HasMany<ReturnRequest, $this> */
    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class)->latest('created_at');
    }

    /**
     * The address to prefill checkout with, if the customer has saved one.
     */
    public function defaultAddress(): ?CustomerAddress
    {
        return $this->addresses()->defaultFirst()->first();
    }
}
