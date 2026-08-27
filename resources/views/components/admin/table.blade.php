{{-- Scrolls horizontally within its own container so the page never does. --}}
@props(['headings' => []])

<div {{ $attributes->merge(['class' => 'card overflow-hidden']) }}>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            @if ($headings !== [])
                <thead class="border-b border-hoor-cream-300 bg-hoor-cream-100/60">
                    <tr>
                        @foreach ($headings as $heading)
                            <th scope="col"
                                class="whitespace-nowrap px-4 py-3 text-start text-xs font-semibold
                                       uppercase tracking-wider text-hoor-muted">
                                {{ $heading }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
            @endif

            <tbody class="divide-y divide-hoor-cream-300">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @isset($footer)
        <div class="border-t border-hoor-cream-300 px-4 py-3">{{ $footer }}</div>
    @endisset
</div>
