<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A message a customer sent through the contact page.
 *
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string $body
 * @property \Illuminate\Support\Carbon|null $read_at
 */
class ContactMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'phone', 'subject', 'body',
        'user_id', 'read_at', 'read_by', 'admin_note',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'read_by');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Mark as read, recording who read it.
     *
     * Idempotent: opening a message twice should not rewrite when it was first
     * seen.
     */
    public function markRead(User $reader): void
    {
        if ($this->isRead()) {
            return;
        }

        $this->update(['read_at' => now(), 'read_by' => $reader->id]);
    }

    /**
     * A short preview for the inbox listing.
     */
    public function excerpt(int $length = 90): string
    {
        return \Illuminate\Support\Str::limit($this->body, $length);
    }
}
