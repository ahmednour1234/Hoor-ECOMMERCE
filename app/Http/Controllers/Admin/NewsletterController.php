<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Newsletter subscribers.
 *
 * There is no sending here — HOOR has no mail provider configured, and a
 * "send" button that silently did nothing would be worse than none. The export
 * is what the list is for: it goes into whatever the business already uses.
 */
class NewsletterController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage', Setting::class);

        return view('admin.content.newsletter.index', [
            'subscribers' => NewsletterSubscriber::query()
                ->latest('created_at')
                ->paginate(50),

            'total' => NewsletterSubscriber::query()->subscribed()->count(),
        ]);
    }

    /**
     * The subscribed addresses as CSV.
     *
     * Streamed rather than built in memory: a mailing list is exactly the kind
     * of table that is small today and large in a year.
     */
    public function export(): StreamedResponse
    {
        $this->authorize('manage', Setting::class);

        $filename = 'hoor-newsletter-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'wb');

            // A BOM, so Excel opens UTF-8 correctly rather than mangling it.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['email', 'locale', 'subscribed_at']);

            NewsletterSubscriber::query()
                ->subscribed()
                ->orderBy('id')
                ->chunk(500, function ($subscribers) use ($handle): void {
                    foreach ($subscribers as $subscriber) {
                        fputcsv($handle, [
                            $subscriber->email,
                            $subscriber->locale,
                            $subscriber->created_at?->toDateString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
