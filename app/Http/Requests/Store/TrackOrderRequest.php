<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use App\Support\EgyptianPhone;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The public tracking lookup.
 *
 * Open to everyone — that is the point of the page.
 */
class TrackOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'number' => ['required', 'string', 'max:32'],

            // Validated loosely on purpose: a customer mistyping her own phone
            // should get "we could not find that order", not a lecture about
            // the format of a number she already gave us once.
            'phone' => ['required', 'string', 'max:20'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'number' => strtoupper(trim(EgyptianPhone::toLatinDigits((string) $this->input('number')))),
            'phone'  => EgyptianPhone::normalise($this->input('phone')),
        ]);
    }

    public function number(): string
    {
        return (string) $this->input('number');
    }

    public function phone(): string
    {
        return (string) $this->input('phone');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'number' => __('tracking.fields.number'),
            'phone'  => __('tracking.fields.phone'),
        ];
    }
}
