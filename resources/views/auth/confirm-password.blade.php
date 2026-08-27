<x-guest-layout>
    <x-slot:title>{{ __('auth.confirm_page.title') }} — {{ __('common.brand') }}</x-slot:title>

    <x-auth.heading :title="__('auth.confirm_page.title')"
                    :subtitle="__('auth.confirm_page.subtitle')" />

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-5">
        @csrf

        <x-auth.field name="password"
                      type="password"
                      icon="lock"
                      :label="__('auth.fields.password')"
                      :placeholder="__('auth.fields.password_placeholder')"
                      autocomplete="current-password"
                      required
                      autofocus />

        <x-auth.submit :label="__('auth.confirm_page.submit')" />
    </form>
</x-guest-layout>
