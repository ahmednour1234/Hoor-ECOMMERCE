<x-layouts.admin>
    @section('title', __('import.title'))
    @section('page-title', __('import.title'))

    <x-admin.page-header :title="__('import.title')">
        <x-slot:subtitle>{{ __('import.subtitle') }}</x-slot:subtitle>

        <x-slot:actions>
            <x-ui.button variant="ghost" size="sm" :href="route('admin.products.index')">
                {{ __('common.actions.back') }}
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Every row that was rejected, with its line number. "Colour not found"
         is useless on a 200-row sheet and actionable with a row number. --}}
    @if (session('import_errors'))
        <div class="card mb-6 border-red-200 bg-red-50 p-5">
            <h2 class="font-display text-base text-red-800">{{ __('import.errors.title') }}</h2>

            <p class="mt-1 text-sm text-red-700">
                {{ __('import.errors.lead', ['count' => count(session('import_errors'))]) }}
            </p>

            <ul class="mt-4 max-h-64 space-y-1.5 overflow-y-auto text-sm text-red-700">
                @foreach (session('import_errors') as $error)
                    <li class="flex gap-2">
                        @if ($error['row'] > 0)
                            <span class="shrink-0 font-mono text-xs text-red-500" dir="ltr">
                                {{ __('import.errors.row', ['row' => $error['row']]) }}
                            </span>
                        @endif

                        <span>{{ $error['message'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1.3fr_1fr]">

        <x-admin.panel :title="__('import.steps.title')">
            <ol class="space-y-4">
                @foreach (['one', 'two', 'three', 'four'] as $index => $step)
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full
                                     bg-hoor-navy-500 text-xs font-medium text-hoor-cream-50" dir="ltr">
                            {{ $index + 1 }}
                        </span>

                        <span class="text-sm leading-relaxed text-hoor-navy-600/90">
                            {{ __('import.steps.'.$step) }}
                        </span>
                    </li>
                @endforeach
            </ol>

            {{-- What the zip should look like, drawn rather than described. --}}
            <pre class="mt-5 overflow-x-auto rounded-sm bg-hoor-cream-100 p-4 font-mono text-xs
                        leading-relaxed text-hoor-navy-700" dir="ltr">catalogue.zip
├── products.xlsx
└── images/
    ├── jeans-1.jpg
    ├── jeans-2.jpg
    └── jacket-1.jpg</pre>

            <p class="mt-4 rounded-sm bg-hoor-beige-100 px-4 py-2.5 text-xs text-hoor-navy-600/85">
                {{ __('import.imported_as_draft') }}
            </p>
        </x-admin.panel>

        <div class="space-y-6">
            <x-admin.panel :title="__('import.template')">
                <p class="text-sm leading-relaxed text-hoor-muted">
                    {{ __('import.template_note') }}
                </p>

                <x-ui.button variant="outline" class="mt-4 w-full"
                             :href="route('admin.products.import.template')">
                    {{ __('import.template') }}
                </x-ui.button>
            </x-admin.panel>

            <x-admin.panel :title="__('import.file')">
                <form method="POST" action="{{ route('admin.products.import.store') }}"
                      enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <input type="file" name="file" id="file" required
                               accept=".xlsx,.zip"
                               class="block w-full text-sm text-hoor-navy-700
                                      file:me-3 file:rounded-sm file:border-0
                                      file:bg-hoor-navy-500 file:px-4 file:py-2
                                      file:text-sm file:font-medium file:text-hoor-cream-50
                                      hover:file:bg-hoor-navy-600">

                        <p class="form-hint">{{ __('import.file_hint') }}</p>

                        @error('file')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <x-ui.button type="submit" variant="primary" class="w-full">
                        {{ __('import.submit') }}
                    </x-ui.button>
                </form>
            </x-admin.panel>
        </div>
    </div>
</x-layouts.admin>
