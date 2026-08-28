<?php

declare(strict_types=1);

namespace App\Services\Import;

/**
 * What an import did, and what it refused.
 *
 * Errors carry their row number, because "colour not found" is useless to
 * someone with a 200-row spreadsheet and actionable with a line number.
 */
class ProductImportResult
{
    /** @var list<array{row: int, message: string}> */
    public array $errors = [];

    public int $productsCreated = 0;

    public int $productsUpdated = 0;

    public int $variantsCreated = 0;

    public int $variantsUpdated = 0;

    public int $imagesAttached = 0;

    public int $rowsRead = 0;

    public function addError(int $row, string $message): void
    {
        $this->errors[] = ['row' => $row, 'message' => $message];
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * Whether anything at all was written.
     */
    public function touchedAnything(): bool
    {
        return $this->productsCreated > 0
            || $this->productsUpdated > 0
            || $this->variantsCreated > 0
            || $this->variantsUpdated > 0;
    }

    /**
     * A one-line summary for the admin screen.
     */
    public function summary(): string
    {
        return __('import.summary', [
            'products' => $this->productsCreated + $this->productsUpdated,
            'variants' => $this->variantsCreated + $this->variantsUpdated,
            'images'   => $this->imagesAttached,
        ]);
    }
}
