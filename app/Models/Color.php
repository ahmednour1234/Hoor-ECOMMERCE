<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property string $name
 */
class Color extends Model
{
    /** @use HasFactory<\Database\Factories\ColorFactory> */
    use HasFactory, HasTranslations;

    /** @var list<string> */
    protected array $translatable = ['name'];

    /** @var list<string> */
    protected $fillable = ['name_ar', 'name_en', 'slug', 'hex', 'sort_order', 'is_active'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $color): void {
            if (blank($color->slug)) {
                $color->slug = Str::slug($color->name_en);
            }

            // Normalise swatches so the value can be dropped straight into CSS.
            if (filled($color->hex)) {
                $color->hex = '#'.strtoupper(ltrim($color->hex, '#'));
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<ProductVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name_en');
    }

    /**
     * Whether a swatch needs dark text to stay legible.
     *
     * Uses the perceived-luminance formula so pale denim and cream swatches do
     * not render white-on-white in the colour selector.
     */
    public function isLight(): bool
    {
        [$r, $g, $b] = sscanf($this->hex, '#%02x%02x%02x') ?? [0, 0, 0];

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 186;
    }
}
