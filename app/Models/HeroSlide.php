<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A slide in the homepage hero.
 *
 * Copy is stored bilingually and rendered as live text over the photograph
 * rather than baked into the artwork, so it translates, is indexable, and can
 * be reworded without a designer.
 *
 * @property int $id
 * @property string $image_path
 * @property string|null $backdrop
 * @property int $position
 * @property bool $is_active
 * @property string|null $eyebrow
 * @property string|null $headline
 * @property string|null $subheadline
 * @property string|null $cta_label
 */
class HeroSlide extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'image_path', 'backdrop',
        'eyebrow_ar', 'eyebrow_en',
        'headline_ar', 'headline_en',
        'subheadline_ar', 'subheadline_en',
        'cta_label_ar', 'cta_label_en', 'cta_url',
        'position', 'is_active',
    ];

    /** @var list<string> */
    protected array $translatable = ['eyebrow', 'headline', 'subheadline', 'cta_label'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'position'  => 'integer',
            'is_active' => 'boolean',
        ];
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
        return $query->orderBy('position')->orderBy('id');
    }

    /**
     * The public URL of the photograph.
     */
    public function imageUrl(): string
    {
        return Storage::disk(config('hoor.media.disk'))->url($this->image_path);
    }

    /**
     * The slide as the hero component expects it.
     *
     * Keeps the component's shape stable whether slides come from the database
     * or from the brand fallback.
     *
     * @return array<string, mixed>
     */
    public function toSlideArray(): array
    {
        return [
            'image'       => $this->image_path,
            'backdrop'    => $this->backdrop,
            'eyebrow'     => $this->eyebrow,
            'headline'    => $this->headline,
            'subheadline' => $this->subheadline,
            'cta_label'   => $this->cta_label,
            'cta_url'     => $this->cta_url,
        ];
    }
}
