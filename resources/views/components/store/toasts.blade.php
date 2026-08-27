{{--
    Transient feedback for actions that no longer reload the page.

    Listens for a `toast` event so any component can raise a message without
    knowing this exists. Messages are announced politely so screen readers
    hear them, since there is no page change to signal success.
--}}
<div x-data="{
        items: [],
        seq: 0,
        push(detail) {
            // Repeating the same message stacks identical toasts, which reads
            // as a bug. Refresh the existing one instead.
            const existing = this.items.find(i => i.message === detail.message);

            if (existing) {
                clearTimeout(existing.timer);
                existing.timer = setTimeout(() => this.dismiss(existing.id), 4000);

                return;
            }

            const id = ++this.seq;
            const timer = setTimeout(() => this.dismiss(id), 4000);

            this.items.push({ id, timer, ...detail });

            // Never let the stack grow past a few; the oldest goes first.
            if (this.items.length > 3) {
                this.dismiss(this.items[0].id);
            }
        },
        dismiss(id) {
            const item = this.items.find(i => i.id === id);

            if (item) {
                clearTimeout(item.timer);
            }

            this.items = this.items.filter(i => i.id !== id);
        },
     }"
     @toast.window="push($event.detail)"
     class="pointer-events-none fixed inset-x-0 bottom-4 z-[60] flex flex-col items-center gap-2 px-4
            sm:bottom-6 sm:items-end sm:px-6"
     role="status"
     aria-live="polite">

    <template x-for="item in items" :key="item.id">
        <div x-transition:enter="transition ease-hoor duration-300"
             x-transition:enter-start="translate-y-3 opacity-0"
             x-transition:enter-end="translate-y-0 opacity-100"
             x-transition:leave="transition ease-hoor duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-md px-4 py-3
                    text-sm shadow-soft"
             :class="item.variant === 'error'
                 ? 'bg-red-600 text-white'
                 : 'bg-hoor-navy-700 text-hoor-cream-50'">

            <span class="mt-0.5 shrink-0" aria-hidden="true">
                <template x-if="item.variant !== 'error'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </template>
                <template x-if="item.variant === 'error'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" d="M12 8v5M12 17h.01" />
                    </svg>
                </template>
            </span>

            <p class="flex-1" x-text="item.message"></p>

            <button type="button" @click="dismiss(item.id)"
                    class="shrink-0 opacity-70 transition hover:opacity-100"
                    aria-label="{{ __('nav.close') }}">&times;</button>
        </div>
    </template>
</div>
