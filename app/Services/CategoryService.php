<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Category writes, including the optional banner image.
 */
class CategoryService
{
    public function __construct(private readonly ImageService $images)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $image = null): Category
    {
        return DB::transaction(function () use ($data, $image): Category {
            if ($image !== null) {
                $data['image'] = $this->images->store($image, $this->images->directoryFor('category'));
            }

            return Category::query()->create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data, ?UploadedFile $image = null, bool $removeImage = false): Category
    {
        $previous = $category->image;

        DB::transaction(function () use ($category, $data, $image, $removeImage): void {
            if ($image !== null) {
                $data['image'] = $this->images->store($image, $this->images->directoryFor('category'));
            } elseif ($removeImage) {
                $data['image'] = null;
            }

            $category->update($data);
        });

        // The old file is only orphaned once the new path is committed.
        if (filled($previous) && $previous !== $category->fresh()->image) {
            $this->images->deleteAfterCommit($previous);
        }

        return $category->refresh();
    }

    /**
     * Categories in use are refused rather than cascaded.
     *
     * The foreign key already restricts this at the database level; checking
     * here turns a 500 into a message the admin can act on.
     */
    public function canDelete(Category $category): bool
    {
        return ! $category->products()->exists() && ! $category->children()->exists();
    }

    public function delete(Category $category): void
    {
        $image = $category->image;

        $category->delete();

        // Soft-deleted, so the banner is kept for a possible restore.
        unset($image);
    }
}
