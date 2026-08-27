<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

/**
 * Creating a product. Rules are inherited wholesale; the class exists so the
 * controller signature states intent and store/update can diverge later.
 */
class StoreProductRequest extends ProductRequest
{
}
