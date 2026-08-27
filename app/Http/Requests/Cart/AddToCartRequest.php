<?php

declare(strict_types=1);

namespace App\Http\Requests\Cart;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\VariantResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates an "add to cart" submission.
 *
 * The storefront disables combinations that do not exist and caps the quantity
 * input at the stock on hand, but none of that is trusted here. Every check is
 * repeated against the database:
 *
 *   - the product is published and therefore purchasable at all
 *   - the variant row exists and is active
 *   - the variant belongs to THIS product, not a cheaper one
 *   - the requested quantity is available right now
 *
 * The stock check is a guard against obvious mistakes, not a reservation:
 * availability is re-checked under a row lock when the order is placed, because
 * stock can change between viewing a page and paying for it.
 */
class AddToCartRequest extends FormRequest
{
    private ?ProductVariant $resolved = null;

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
            'quantity'   => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // Skip the ownership and stock checks when the basics already
            // failed, otherwise the customer sees two errors for one mistake.
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->validatePurchasable($validator);
        });
    }

    private function validatePurchasable(Validator $validator): void
    {
        $product = $this->product();

        if ($product === null || $product->status !== ProductStatus::Published) {
            $validator->errors()->add('variant_id', __('cart.errors.unavailable'));

            return;
        }

        // Scoping the lookup to the product is what stops a tampered variant_id
        // from buying a different product's stock at this product's price.
        $variant = app(VariantResolver::class)->forProduct($product, (int) $this->integer('variant_id'));

        if ($variant === null) {
            $validator->errors()->add('variant_id', __('cart.errors.invalid_combination'));

            return;
        }

        $quantity = (int) $this->integer('quantity');

        if ($variant->stock_quantity <= 0) {
            $validator->errors()->add('variant_id', __('cart.errors.out_of_stock'));

            return;
        }

        if (! $variant->canFulfil($quantity)) {
            $validator->errors()->add('quantity', __('cart.errors.insufficient_stock', [
                'count' => $variant->stock_quantity,
            ]));

            return;
        }

        $this->resolved = $variant;
    }

    /**
     * The product this request targets, from the route binding.
     */
    public function product(): ?Product
    {
        $product = $this->route('product');

        return $product instanceof Product ? $product : null;
    }

    /**
     * The validated variant.
     *
     * Only available after validation has passed, so callers can rely on it
     * being a real, active, in-stock row belonging to the right product.
     */
    public function variant(): ProductVariant
    {
        return $this->resolved ?? throw new \LogicException(
            'The variant is only available after validation has passed.',
        );
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
        return [
            'variant_id' => __('catalog.labels.size').' / '.__('catalog.labels.color'),
            'quantity'   => __('catalog.fields.stock'),
        ];
    }
}
