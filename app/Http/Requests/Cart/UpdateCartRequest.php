<?php

declare(strict_types=1);

namespace App\Http\Requests\Cart;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a quantity change on an existing cart line.
 *
 * Zero is allowed and means "remove this line", which is what a quantity input
 * cleared to nothing should do. Availability is not asserted here: the service
 * clamps to the stock on hand and reports what changed, so a customer lowering
 * a quantity is never blocked by a stock drop they did not cause.
 */
class UpdateCartRequest extends FormRequest
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
            'variant_id' => ['required', 'integer', Rule::exists('product_variants', 'id')],
            'quantity'   => ['required', 'integer', 'min:0', 'max:99'],
        ];
    }

    public function variant(): ProductVariant
    {
        return ProductVariant::query()->findOrFail($this->integer('variant_id'));
    }

    public function quantity(): int
    {
        return (int) $this->integer('quantity');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['quantity' => __('store.product.quantity')];
    }
}
