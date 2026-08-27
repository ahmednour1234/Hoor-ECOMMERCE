<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Someone who asked to hear from HOOR.
 *
 * Unsubscribing timestamps rather than deletes: the same address signing up
 * again is a different event from one that never left, and a deleted row
 * cannot tell them apart.
 *
 * @property int $id
 * @property string $email
 * @property string|null $locale
 * @property \Illuminate\Support\Carbon|null $unsubscribed_at
 */
class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = ['email', 'locale', 'unsubscribed_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['unsubscribed_at' => 'datetime'];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSubscribed(Builder $query): Builder
    {
        return $query->whereNull('unsubscribed_at');
    }

    public function isSubscribed(): bool
    {
        return $this->unsubscribed_at === null;
    }
}
