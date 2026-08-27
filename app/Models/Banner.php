<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A promotional banner.
 *
 * Placement-keyed rather than having a table per slot, so a new position on the
 * site is a new string rather than a migration.
 *
 * @property int $id
 * @property string $placement
 * @property string|null $image_path
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property bool $is_active
 */
class Banner extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'placement', 'image_path',
        'title_ar', 'title_en',
        'body_ar', 'body_en',
        'cta_label_ar', 'cta_label_en', 'cta_url',
        'starts_at', 'ends_at',
        'position', 'is_active',
    ];

    /** @var list<string> */
    protected array $translatable = ['title', 'body', 'cta_label'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at'   => 'datetime',
            'position'  => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Banners that should be on screen right now.
     *
     * A date range that has passed switches the banner off by itself, rather
     * than relying on someone remembering at midnight.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePlacement(Builder $query, string $placement): Builder
    {
        return $query->where('placement', $placement);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    public function imageUrl(): ?string
    {
        return $this->image_path
            ? Storage::disk(config('hoor.media.disk'))->url($this->image_path)
            : null;
    }

    /**
     * Whether this banner's run has finished, for the admin listing.
     */
    public function hasExpired(): bool
    {
        return $this->ends_at !== null && $this->ends_at->isPast();
    }

    public function isScheduled(): bool
    {
        return $this->starts_at !== null && $this->starts_at->isFuture();
    }
}
