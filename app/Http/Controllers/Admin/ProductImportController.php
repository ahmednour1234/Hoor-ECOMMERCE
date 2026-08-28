<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Import\ImportArchive;
use App\Services\Import\ProductImporter;
use App\Services\Import\ProductImportTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Importing a catalogue from a spreadsheet.
 *
 * Two endpoints that matter: one hands out a template already filled with the
 * shop's own categories, sizes and colours, and one takes it back.
 */
class ProductImportController extends Controller
{
    public function __construct(
        private readonly ProductImporter $importer,
        private readonly ProductImportTemplate $template,
    ) {
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('admin.products.import');
    }

    /**
     * Download the template.
     *
     * Generated rather than a static file, so its reference sheet lists the
     * categories and sizes that exist today — a template naming a category the
     * shop deleted would teach people to type a value the import rejects.
     */
    public function template(): BinaryFileResponse
    {
        $this->authorize('create', Product::class);

        $path = storage_path('app/hoor-product-template-'.now()->format('Y-m-d').'.xlsx');

        $this->template->writeTo($path);

        return response()->download($path)->deleteFileAfterSend();
    }

    /**
     * Take the filled-in sheet back.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:'.(200 * 1024),
                // A zip carries the images; a bare sheet updates prices and
                // stock without touching them.
                'mimes:xlsx,zip',
            ],
        ]);

        try {
            $archive = ImportArchive::fromUpload($request->file('file'));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        try {
            $result = $this->importer->import($archive->sheetPath, $archive->imageDirectory);
        } finally {
            // Whatever happened, the unpacked photographs do not stay on disk.
            $archive->cleanUp();
        }

        if ($result->hasErrors()) {
            return back()
                ->with('import_errors', $result->errors)
                ->withErrors([
                    'file' => __('import.errors.rejected', ['count' => count($result->errors)]),
                ]);
        }

        return redirect()
            ->route('admin.products.index')
            ->with('status', $result->summary());
    }
}
