<x-guest-layout>
    <x-slot:title>{{ __('auth.forgot_page.title') }} — {{ __('common.brand') }}</x-slot:title>

    <x-auth.heading :title="__('auth.forgot_page.title')"
                    :subtitle="__('auth.forgot_page.subtitle')" />

    @if (session('status'))
        <div class="mt-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
        @csrf

        <x-auth.field name="email"
                      type="email"
                      icon="mail"
                      :label="__('auth.fields.email')"
                      :placeholder="__('auth.fields.email_placeholder')"
                      autocomplete="username"
                      required
                      autofocus />

        <x-auth.submit :label="__('auth.forgot_page.submit')" />
    </form>

    <x-auth.divider class="mt-6" />

    <p class="text-center text-sm">
        <a href="{{ route('login') }}"
           class="text-hoor-denim-600 underline underline-offset-2 transition hover:text-hoor-gold-600">
            {{ __('auth.forgot_page.back') }}
        </a>
    </p>
</x-guest-layout>
