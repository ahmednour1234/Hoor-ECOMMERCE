<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use App\Support\EgyptianPhone;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A message from the contact page.
 *
 * Open to guests: requiring an account to ask a question would lose the
 * question.
 */
class ContactMessageRequest extends FormRequest
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
            'name'  => ['required', 'string', 'max:160'],

            // One of the two is needed, or there is no way to answer her.
            'email' => ['nullable', 'required_without:phone', 'email', 'max:190'],
            'phone' => ['nullable', 'required_without:email', 'string', EgyptianPhone::RULE],

            'subject' => ['nullable', 'string', 'max:190'],
            'body'    => ['required', 'string', 'min:10', 'max:2000'],

            // A honeypot: a field no human sees, so anything filling it is a
            // bot. Cheaper and less hostile than a captcha.
            'website' => ['nullable', 'prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['phone' => EgyptianPhone::normalise($this->input('phone'))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function messageData(): array
    {
        return [
            'name'    => (string) $this->input('name'),
            'email'   => $this->input('email') ?: null,
            'phone'   => $this->input('phone') ?: null,
            'subject' => $this->input('subject') ?: null,
            'body'    => (string) $this->input('body'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name'    => __('store.contact.name'),
            'email'   => __('store.contact.email'),
            'phone'   => __('store.contact.phone'),
            'subject' => __('store.contact.subject'),
            'body'    => __('store.contact.message'),
        ];
    }
}
