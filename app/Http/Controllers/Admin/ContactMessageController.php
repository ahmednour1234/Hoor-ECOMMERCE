<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The contact inbox.
 */
class ContactMessageController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage', Setting::class);

        $unreadOnly = $request->boolean('unread');

        return view('admin.content.messages.index', [
            'messages' => ContactMessage::query()
                ->with('user')
                ->when($unreadOnly, fn ($query) => $query->unread())
                ->latest('created_at')
                ->paginate(20)
                ->withQueryString(),

            'unreadCount' => ContactMessage::query()->unread()->count(),
            'unreadOnly'  => $unreadOnly,
        ]);
    }

    /**
     * Read a message.
     *
     * Opening it marks it read, which is what "read" means — asking staff to
     * click a second button would leave the unread count permanently wrong.
     */
    public function show(Request $request, ContactMessage $message): View
    {
        $this->authorize('manage', Setting::class);

        $message->markRead($request->user());

        return view('admin.content.messages.show', [
            'message' => $message->load(['user', 'reader']),
        ]);
    }

    /**
     * Save an internal note.
     */
    public function update(Request $request, ContactMessage $message): RedirectResponse
    {
        $this->authorize('manage', Setting::class);

        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $message->update(['admin_note' => $validated['admin_note'] ?: null]);

        return back()->with('status', __('content.messages.note_saved'));
    }

    /**
     * Put it back in the unread pile.
     */
    public function markUnread(ContactMessage $message): RedirectResponse
    {
        $this->authorize('manage', Setting::class);

        $message->update(['read_at' => null, 'read_by' => null]);

        return redirect()
            ->route('admin.messages.index')
            ->with('status', __('content.messages.title'));
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $this->authorize('manage', Setting::class);

        $message->delete();

        return redirect()
            ->route('admin.messages.index')
            ->with('status', __('content.messages.deleted'));
    }
}
