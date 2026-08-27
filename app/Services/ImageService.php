<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Single owner of every file that enters or leaves the media disk.
 *
 * Two rules govern this class:
 *
 *  1. Binary data never reaches the database. Only the storage-relative path
 *     is persisted; the bytes live on the configured disk.
 *
 *  2. A file is only deleted once the database transaction that orphaned it
 *     has committed. Deleting inside a transaction that later rolls back would
 *     destroy a file whose row still exists — an unrecoverable data loss that
 *     a rollback cannot undo.
 */
class ImageService
{
    public function disk(): string
    {
        return config('hoor.media.disk', 'public');
    }

    /**
     * Store an upload under the given directory and return its relative path.
     *
     * The filename is randomised so that re-uploading a file with the same name
     * never overwrites an existing image belonging to another product.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        $name = sprintf(
            '%s-%s.%s',
            now()->format('Ymd'),
            Str::random(20),
            strtolower($file->getClientOriginalExtension() ?: 'jpg'),
        );

        return $file->storeAs($directory, $name, ['disk' => $this->disk()]);
    }

    /**
     * Queue a file for deletion once the surrounding transaction commits.
     *
     * Safe to call inside or outside a transaction: with no active transaction
     * Laravel runs the callback immediately.
     */
    public function deleteAfterCommit(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        DB::afterCommit(function () use ($path): void {
            $this->delete($path);
        });
    }

    /**
     * Paths written during the current unit of work.
     *
     * Uploads land on disk before the transaction commits, so a later failure
     * would leave the bytes behind with no row referencing them. Laravel offers
     * no afterRollback hook, so the caller wraps its work in transaction() and
     * discards these on failure.
     *
     * @var list<string>
     */
    private array $pending = [];

    /**
     * Track a freshly written file so it can be discarded if the work fails.
     */
    public function trackPending(string $path): void
    {
        $this->pending[] = $path;
    }

    /**
     * Delete every file written since the last checkpoint.
     *
     * Called from the catch block of the service that owns the transaction.
     */
    public function discardPending(): void
    {
        foreach ($this->pending as $path) {
            $this->delete($path);
        }

        $this->pending = [];
    }

    /**
     * Forget tracked files after a successful commit — they are now referenced
     * by committed rows and must not be deleted.
     */
    public function commitPending(): void
    {
        $this->pending = [];
    }

    /**
     * Remove a file immediately.
     *
     * A missing file is not an error — the desired end state (no file at this
     * path) already holds, and failing here would block an otherwise valid
     * delete. Genuine failures are logged rather than thrown so that tidying up
     * storage never breaks a user-facing action.
     */
    public function delete(?string $path): bool
    {
        if (blank($path)) {
            return false;
        }

        $disk = Storage::disk($this->disk());

        if (! $disk->exists($path)) {
            return false;
        }

        try {
            return $disk->delete($path);
        } catch (\Throwable $e) {
            Log::warning('Failed to delete media file.', [
                'path'  => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param  iterable<string|null>  $paths
     */
    public function deleteManyAfterCommit(iterable $paths): void
    {
        foreach ($paths as $path) {
            $this->deleteAfterCommit($path);
        }
    }

    public function url(?string $path): ?string
    {
        return blank($path) ? null : Storage::disk($this->disk())->url($path);
    }

    public function exists(?string $path): bool
    {
        return filled($path) && Storage::disk($this->disk())->exists($path);
    }

    /**
     * Directory configured for a given media type (products, banners, …).
     */
    public function directoryFor(string $type): string
    {
        return config("hoor.media.paths.{$type}", $type);
    }
}
