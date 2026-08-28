<?php

declare(strict_types=1);

namespace App\Services\Import;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use ZipArchive;

/**
 * Unpacks an upload into a spreadsheet and a folder of images.
 *
 * Accepts either a bare .xlsx or a .zip holding one alongside an images
 * folder. The zip is the useful case — it is how a shop sends a catalogue and
 * its photographs as one thing — but a sheet on its own is a legitimate way to
 * update prices and stock without touching images.
 *
 * Extraction is defensive. A zip is an untrusted archive: an entry named
 * "../../.env" would, extracted naively, write outside the target folder, and
 * an archive of a few kilobytes can expand to fill a disk.
 */
class ImportArchive
{
    /**
     * The most an archive may expand to. A catalogue of photographs is large;
     * a zip bomb is larger.
     */
    private const MAX_EXTRACTED_BYTES = 512 * 1024 * 1024;

    private const MAX_ENTRIES = 2000;

    /**
     * Image types worth copying out. Anything else in the archive is ignored
     * rather than trusted.
     *
     * @var list<string>
     */
    private const IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'webp'];

    public readonly string $sheetPath;

    public readonly ?string $imageDirectory;

    private function __construct(string $sheetPath, ?string $imageDirectory, private readonly ?string $tempDirectory)
    {
        $this->sheetPath = $sheetPath;
        $this->imageDirectory = $imageDirectory;
    }

    /**
     * @throws RuntimeException
     */
    public static function fromUpload(UploadedFile $file): self
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'xlsx') {
            // Nothing to unpack: the sheet is the upload.
            return new self($file->getRealPath(), null, null);
        }

        return self::fromZip($file);
    }

    /**
     * @throws RuntimeException
     */
    private static function fromZip(UploadedFile $file): self
    {
        $directory = self::makeTempDirectory();

        $zip = new ZipArchive();

        if ($zip->open($file->getRealPath()) !== true) {
            throw new RuntimeException(__('import.errors.bad_zip'));
        }

        if ($zip->numFiles > self::MAX_ENTRIES) {
            $zip->close();

            throw new RuntimeException(__('import.errors.bad_zip'));
        }

        $sheet = null;
        $imagesDirectory = $directory.DIRECTORY_SEPARATOR.'images';

        @mkdir($imagesDirectory, 0755, true);

        $extracted = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);

            if ($stat === false || str_ends_with($stat['name'], '/')) {
                continue;
            }

            /*
             * basename() alone decides where an entry lands, so a name like
             * "../../.env" becomes ".env" inside our own temporary folder and
             * cannot escape it. The archive's own directory structure is
             * discarded, which is why the template asks for bare file names.
             */
            $name = basename($stat['name']);

            // Skip the metadata folders macOS adds to every zip it makes.
            if ($name === '' || str_starts_with($name, '.') || str_contains($stat['name'], '__MACOSX')) {
                continue;
            }

            $extracted += (int) $stat['size'];

            if ($extracted > self::MAX_EXTRACTED_BYTES) {
                $zip->close();
                self::deleteDirectory($directory);

                throw new RuntimeException(__('import.errors.archive_too_large'));
            }

            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if ($extension === 'xlsx' && $sheet === null) {
                $sheet = $directory.DIRECTORY_SEPARATOR.$name;
                file_put_contents($sheet, $zip->getFromIndex($i));

                continue;
            }

            if (in_array($extension, self::IMAGE_TYPES, true)) {
                file_put_contents($imagesDirectory.DIRECTORY_SEPARATOR.$name, $zip->getFromIndex($i));
            }
        }

        $zip->close();

        if ($sheet === null) {
            self::deleteDirectory($directory);

            throw new RuntimeException(__('import.errors.no_sheet'));
        }

        return new self($sheet, $imagesDirectory, $directory);
    }

    /**
     * Remove whatever was unpacked.
     *
     * Called in a finally block by the caller, so a failed import does not
     * leave a catalogue of photographs in the temp folder.
     */
    public function cleanUp(): void
    {
        if ($this->tempDirectory !== null) {
            self::deleteDirectory($this->tempDirectory);
        }
    }

    private static function makeTempDirectory(): string
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hoor-import-'.bin2hex(random_bytes(8));

        if (! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException(__('import.errors.temp_failed'));
        }

        return $directory;
    }

    private static function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$entry;

            is_dir($path) ? self::deleteDirectory($path) : @unlink($path);
        }

        @rmdir($directory);
    }
}
