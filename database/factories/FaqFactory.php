<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Faq>
 */
class FaqFactory extends Factory
{
    public function definition(): array
    {
        return [
            'placement'   => 'contact',
            'question_en' => fake()->sentence(6).'?',
            'question_ar' => 'سؤال شائع؟',
            'answer_en'   => fake()->paragraph(),
            'answer_ar'   => 'إجابة على السؤال.',
            'position'    => 0,
            'is_active'   => true,
        ];
    }
}
