<x-layouts.admin>
    @section('title', __('settings.title'))
    @section('page-title', __('settings.title'))

    <x-admin.page-header :title="__('settings.title')" />

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-[14rem_1fr]">

        {{-- Panels come from the registry, so a new group of settings appears
             here without touching this file. --}}
        <nav class="card h-fit p-2" aria-label="{{ __('settings.title') }}">
            @foreach ($groups as $item)
                <a href="{{ route('admin.settings.edit', $item) }}"
                   @if ($item === $group) aria-current="page" @endif
                   class="block rounded-sm px-3 py-2 text-sm transition
                          {{ $item === $group
                              ? 'bg-hoor-navy-700 font-medium text-hoor-cream-50'
                              : 'text-hoor-navy-600 hover:bg-hoor-cream-100' }}">
                    {{ __('settings.groups.'.$item) }}
                </a>
            @endforeach
        </nav>

        <form method="POST" action="{{ route('admin.settings.update', $group) }}" class="card p-6">
            @csrf
            @method('PATCH')

            @if ($errors->any())
                <x-ui.alert variant="danger" class="mb-6">
                    {{ __('catalog.messages.has_errors') }}
                </x-ui.alert>
            @endif

            <div class="space-y-5">
                @foreach ($definitions as $key => $definition)
                    @php
                        // Dots are Laravel's nesting syntax, so the error bag
                        // addresses them with the same escaping the rules use.
                        $field = 'settings['.$key.']';
                        $errorKey = 'settings.'.$key;
                        $value = old('settings.'.str_replace('.', '\\.', $key), $values[$key] ?? null);
                    @endphp

                    @if ($definition->control() === 'toggle')
                        <label class="flex items-start gap-3">
                            {{-- A hidden zero so an unticked box still posts,
                                 otherwise a section could never be switched off. --}}
                            <input type="hidden" name="{{ $field }}" value="0">
                            <input type="checkbox" name="{{ $field }}" value="1"
                                   class="rounded border-hoor-cream-300 text-hoor-navy-500 focus:ring-hoor-denim-500 mt-0.5" @checked($value)>

                            <span>
                                <span class="text-sm text-hoor-navy-700">{{ $definition->label() }}</span>
                                @if ($definition->hint())
                                    <span class="block text-xs text-hoor-muted">{{ $definition->hint() }}</span>
                                @endif
                            </span>
                        </label>

                    @elseif ($key === 'homepage.featured_category_id')
                        <div>
                            <label for="{{ $key }}" class="form-label">{{ $definition->label() }}</label>

                            <select name="{{ $field }}" id="{{ $key }}" class="form-select">
                                <option value="">{{ __('settings.hints.homepage.featured_category_id') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}"
                                        @selected((int) $value === $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                    @elseif ($definition->control() === 'textarea')
                        <div>
                            <label for="{{ $key }}" class="form-label">{{ $definition->label() }}</label>

                            <textarea name="{{ $field }}" id="{{ $key }}" rows="4"
                                      class="form-textarea">{{ $value }}</textarea>

                            @if ($definition->hint())
                                <p class="form-hint">{{ $definition->hint() }}</p>
                            @endif

                            @error($errorKey)<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                    @else
                        <div>
                            <label for="{{ $key }}" class="form-label">{{ $definition->label() }}</label>

                            <input type="{{ $definition->type === 'email' ? 'email' : 'text' }}"
                                   name="{{ $field }}" id="{{ $key }}"
                                   value="{{ $value }}"
                                   @if (in_array($definition->type, ['url', 'email', 'phone'], true)) dir="ltr" @endif
                                   class="form-input">

                            @if ($definition->hint())
                                <p class="form-hint">{{ $definition->hint() }}</p>
                            @endif

                            @error($errorKey)<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="mt-6 border-t border-hoor-cream-300 pt-5">
                <x-ui.button type="submit" variant="primary">
                    {{ __('common.actions.save') }}
                </x-ui.button>
            </div>
        </form>
    </div>
</x-layouts.admin>
