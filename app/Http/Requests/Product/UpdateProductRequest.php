<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

/**
 * Updating a product. The base rules resolve the current product from the
 * route, so unique checks correctly ignore the record being edited.
 */
class UpdateProductRequest extends ProductRequest
{
}
