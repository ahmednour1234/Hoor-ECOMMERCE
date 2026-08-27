<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Enums\ReturnReason;
use App\Enums\ReturnType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Raising a return or exchange.
 *
 * Shape only. Whether these particular lines *may* be returned — the window,
 * the quantities already claimed, whether the line is even on this order — is
 * arithmetic against the order, and lives in CreateReturnRequestAction so that
 * every route in reaches the same rules.
 */
class StoreReturnRequest extends FormRequest
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
            'type'   => ['required', Rule::enum(ReturnType::class)],
            'reason' => ['required', Rule::enum(ReturnReason::class)],
            'note'   => ['nullable', 'string', 'max:1000'],

            // Keyed by order item id; unticked lines arrive as 0 and are
            // dropped by the action rather than rejected here.
            'quantities'   => ['required', 'array'],
            'quantities.*' => ['nullable', 'integer', 'min:0', 'max:999'],

            /*
             * The replacement variant per line, for an exchange.
             *
             * Existence only. Whether a variant is a *valid* replacement — same
             * product, active, in stock — is checked against the order in
             * CreateReturnRequestAction, because it is arithmetic a rule cannot
             * do and because the answer changes between request and approval.
             */
            'replacements'   => ['nullable', 'array'],
            'replacements.*' => ['nullable', 'integer', 'exists:product_variants,id'],
        ];
    }

    public function type(): ReturnType
    {
        return ReturnType::from((string) $this->input('type'));
    }

    public function reason(): ReturnReason
    {
        return ReturnReason::from((string) $this->input('reason'));
    }

    public function note(): ?string
    {
        $note = trim((string) $this->input('note'));

        return $note === '' ? null : $note;
    }

    /**
     * Requested quantities, cast at the boundary.
     *
     * @return array<int, int>
     */
    public function quantities(): array
    {
        $quantities = [];

        foreach ((array) $this->input('quantities', []) as $orderItemId => $quantity) {
            $quantities[(int) $orderItemId] = (int) $quantity;
        }

        return $quantities;
    }

    /**
     * Replacement variant per order item, cast at the boundary.
     *
     * @return array<int, int|null>
     */
    public function replacements(): array
    {
        $replacements = [];

        foreach ((array) $this->input('replacements', []) as $orderItemId => $variantId) {
            $replacements[(int) $orderItemId] = blank($variantId) ? null : (int) $variantId;
        }

        return $replacements;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type'       => __('returns.fields.type'),
            'reason'     => __('returns.fields.reason'),
            'note'       => __('returns.fields.note'),
            'quantities'   => __('returns.fields.items'),
            'replacements' => __('returns.fields.replacement'),
        ];
    }
}
