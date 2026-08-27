<x-guest-layout>
    <x-slot:title>{{ __('auth.verify_page.title') }} — {{ __('common.brand') }}</x-slot:title>

    <x-auth.heading :title="__('auth.verify_page.title')"
                    :subtitle="__('auth.verify_page.subtitle')" />

    @if (session('status') === 'verification-link-sent')
        <div class="mt-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ __('auth.verify_page.sent') }}
        </div>
    @endif

    <div class="mt-6 space-y-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-auth.submit :label="__('auth.verify_page.resend')" />
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full rounded-md border border-hoor-cream-300 bg-white/80 px-6 py-2.5
                           text-sm text-hoor-navy-600 transition hover:border-hoor-navy-300
                           hover:text-hoor-navy-700">
                {{ __('auth.verify_page.logout') }}
            </button>
        </form>
    </div>
</x-guest-layout>
