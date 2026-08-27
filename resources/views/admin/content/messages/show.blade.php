<x-layouts.admin>
    @section('title', $message->subject ?: $message->name)
    @section('page-title', $message->subject ?: $message->name)

    <x-admin.page-header :title="$message->subject ?: __('content.messages.title')">
        <x-slot:subtitle>
            {{ $message->created_at->translatedFormat('d M Y — H:i') }}
        </x-slot:subtitle>

        <x-slot:actions>
            <x-ui.button variant="ghost" size="sm" :href="route('admin.messages.index')">
                {{ __('content.messages.back') }}
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.panel :title="__('content.messages.title')">
                <p class="whitespace-pre-line text-sm leading-relaxed text-hoor-navy-700">
                    {{ $message->body }}
                </p>
            </x-admin.panel>

            {{-- A place for staff to record what was done, kept apart from the
                 customer's own words. --}}
            <x-admin.panel :title="__('content.messages.note')">
                <form method="POST" action="{{ route('admin.messages.update', $message) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <x-ui.textarea name="admin_note" rows="4"
                                   :value="old('admin_note', $message->admin_note)" />

                    <x-ui.button type="submit" variant="primary" size="sm">
                        {{ __('common.actions.save') }}
                    </x-ui.button>
                </form>
            </x-admin.panel>
        </div>

        <div class="space-y-6">
            <x-admin.panel :title="__('content.messages.from')">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-hoor-muted">{{ __('store.contact.name') }}</dt>
                        <dd class="text-hoor-navy-700">{{ $message->name }}</dd>
                    </div>

                    @if ($message->email)
                        <div>
                            <dt class="text-xs text-hoor-muted">{{ __('store.contact.email') }}</dt>
                            <dd dir="ltr">
                                <a href="mailto:{{ $message->email }}"
                                   class="text-hoor-denim-600 hover:text-hoor-denim-700">{{ $message->email }}</a>
                            </dd>
                        </div>
                    @endif

                    @if ($message->phone)
                        <div>
                            <dt class="text-xs text-hoor-muted">{{ __('store.contact.phone') }}</dt>
                            <dd dir="ltr">
                                <a href="tel:{{ $message->phone }}"
                                   class="text-hoor-denim-600 hover:text-hoor-denim-700">{{ $message->phone }}</a>
                            </dd>
                        </div>
                    @endif

                    @if ($message->user)
                        <div>
                            <dt class="text-xs text-hoor-muted">{{ __('content.messages.account') }}</dt>
                            <dd class="text-hoor-navy-700">{{ $message->user->name }}</dd>
                        </div>
                    @endif
                </dl>

                @if ($message->reader && $message->read_at)
                    <p class="mt-4 border-t border-hoor-cream-300 pt-3 text-xs text-hoor-muted">
                        {{ __('content.messages.read_by', [
                            'name' => $message->reader->name,
                            'date' => $message->read_at->translatedFormat('d M Y'),
                        ]) }}
                    </p>
                @endif
            </x-admin.panel>

            <x-admin.panel :title="__('common.actions.edit')">
                <div class="space-y-2">
                    <form method="POST" action="{{ route('admin.messages.unread', $message) }}">
                        @csrf
                        @method('PATCH')
                        <x-ui.button type="submit" variant="outline" size="sm" class="w-full">
                            {{ __('content.messages.mark_unread') }}
                        </x-ui.button>
                    </form>

                    <form method="POST" action="{{ route('admin.messages.destroy', $message) }}"
                          onsubmit="return confirm(@js(__('content.messages.confirm')))">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" variant="ghost" size="sm"
                                     class="w-full text-red-600 hover:text-red-700">
                            {{ __('content.messages.delete') }}
                        </x-ui.button>
                    </form>
                </div>
            </x-admin.panel>
        </div>
    </div>
</x-layouts.admin>
