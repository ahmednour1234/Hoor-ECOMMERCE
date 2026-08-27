<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Joining the newsletter.
 */
class NewsletterRequest extends FormRequest
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
            /*
             * Not `unique`: an address that unsubscribed and came back is a
             * customer changing her mind, and a uniqueness error would read to
             * her as the form being broken. The controller resubscribes.
             */
            'email'   => ['required', 'email', 'max:190'],
            'website' => ['nullable', 'prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    public function email(): string
    {
        return (string) $this->input('email');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['email' => __('store.newsletter.email')];
    }
}
