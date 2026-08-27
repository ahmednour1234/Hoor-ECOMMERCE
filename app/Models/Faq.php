<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One frequently asked question.
 *
 * @property int $id
 * @property string $placement
 * @property string $question
 * @property string $answer
 * @property int $position
 * @property bool $is_active
 */
class Faq extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'placement',
        'question_ar', 'question_en',
        'answer_ar', 'answer_en',
        'position', 'is_active',
    ];

    /** @var list<string> */
    protected array $translatable = ['question', 'answer'];

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
}
