<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates a status change from the back office.
 *
 * The transition itself is re-checked in UpdateOrderStatusAction, which is the
 * authority; validating it here as well turns what would be an exception into
 * a field error the admin can act on.
 */
class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'note'   => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $order = $this->order();
            $target = $this->status();

            if ($order === null || $target === null) {
                return;
            }

            if (! $order->status->canTransitionTo($target)) {
                $validator->errors()->add('status', __('orders.errors.invalid_transition', [
                    'from' => $order->status->label(),
                    'to'   => $target->label(),
                ]));
            }
        });
    }

    public function order(): ?Order
    {
        $order = $this->route('order');

        return $order instanceof Order ? $order : null;
    }

    public function status(): ?OrderStatus
    {
        return OrderStatus::tryFrom((string) $this->input('status'));
    }

    public function note(): ?string
    {
        $note = trim((string) $this->input('note'));

        return $note === '' ? null : $note;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => __('orders.fields.status'),
            'note'   => __('orders.fields.note'),
        ];
    }
}
