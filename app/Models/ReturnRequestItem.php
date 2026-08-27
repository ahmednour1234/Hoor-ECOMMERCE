<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\HasTranslations;

/**
 * One order line being sent back, and how much of it.
 *
 * @property int $id
 * @property int $return_request_id
 * @property int $order_item_id
 * @property int $quantity
 */
class ReturnRequestItem extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'return_request_id', 'order_item_id', 'quantity', 'received_quantity',
        'replacement_variant_id',
        'replacement_sku',
        'replacement_size_ar', 'replacement_size_en',
        'replacement_color_ar', 'replacement_color_en',
    ];

    /**
     * The replacement's size and colour read in the current locale, exactly as
     * order items do.
     *
     * @var list<string>
     */
    protected array $translatable = ['replacement_size_name', 'replacement_color_name'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity'          => 'integer',
            'received_quantity' => 'integer',
        ];
    }

    /** @return BelongsTo<ReturnRequest, $this> */
    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    /** @return BelongsTo<OrderItem, $this> */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * The variant going out instead, for an exchange.
     *
     * Nullable twice over: null on a plain return, and null again if the
     * variant was later deleted — the snapshot columns still say what was
     * agreed.
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function replacementVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'replacement_variant_id');
    }

    /**
     * Whether this line names a replacement.
     */
    public function isExchange(): bool
    {
        return $this->replacement_variant_id !== null || $this->replacement_sku !== null;
    }

    /**
     * The replacement as one readable label, from the snapshot.
     */
    public function replacementLabel(): ?string
    {
        if (! $this->isExchange()) {
            return null;
        }

        $parts = collect([$this->replacement_size_name, $this->replacement_color_name])
            ->filter()
            ->join(' / ');

        return $parts !== '' ? $parts : $this->replacement_sku;
    }

    /**
     * How many of this line are still expected back.
     */
    public function outstanding(): int
    {
        return max(0, $this->quantity - (int) ($this->received_quantity ?? 0));
    }
}
