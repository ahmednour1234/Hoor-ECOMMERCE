<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Support\Locale;
use Illuminate\Database\Eloquent\Builder;

/**
 * Convenience accessors for side-by-side bilingual columns.
 *
 * HOOR stores translations as sibling columns (name_en / name_ar) rather than
 * a JSON blob or a translations table: there are exactly two locales, both are
 * always required for customer-facing copy, and columns keep the data sortable
 * and indexable straight from SQL.
 *
 * Consuming models declare:
 *   protected array $translatable = ['name', 'description'];
 */
trait HasTranslations
{
    /**
     * Value for the active locale, falling back to the default locale when the
     * translation is missing or empty.
     */
    public function translate(string $attribute, ?string $locale = null): ?string
    {
        return Locale::pick($this, $attribute, $locale);
    }

    /**
     * Expose `$model->name` as the translated value of name_en / name_ar.
     *
     * getAttribute() is the correct hook rather than __get(): Eloquent defines
     * __get() on Model itself, so a trait method of the same name is shadowed
     * by the class and never runs.
     */
    public function getAttribute($key)
    {
        if (in_array($key, $this->translatable ?? [], strict: true)
            && ! array_key_exists($key, $this->attributes)) {
            return $this->translate($key);
        }

        return parent::getAttribute($key);
    }

    /**
     * Order by the column matching the active locale.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrderByTranslation(Builder $query, string $attribute, string $direction = 'asc'): Builder
    {
        return $query->orderBy($attribute.'_'.Locale::current(), $direction);
    }

    /**
     * Search both language columns at once.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSearchTranslation(Builder $query, string $attribute, string $term): Builder
    {
        return $query->where(function (Builder $query) use ($attribute, $term): void {
            foreach (Locale::codes() as $code) {
                $query->orWhere($attribute.'_'.$code, 'like', '%'.$term.'%');
            }
        });
    }
}
