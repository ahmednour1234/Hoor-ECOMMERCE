<x-layouts.guest>
    <x-slot:title>{{ __('auth.reset_page.title') }} — {{ __('common.brand') }}</x-slot:title>

    <x-auth.heading :title="__('auth.reset_page.title')"
                    :subtitle="__('auth.reset_page.subtitle')" />

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-auth.field name="email"
                      type="email"
                      icon="mail"
                      :label="__('auth.fields.email')"
                      :value="$request->email"
                      autocomplete="username"
                      required
                      autofocus />

        <x-auth.field name="password"
                      type="password"
                      icon="lock"
                      :label="__('auth.fields.password')"
                      :placeholder="__('auth.fields.password_placeholder')"
                      autocomplete="new-password"
                      required />

        <x-auth.field name="password_confirmation"
                      type="password"
                      icon="lock"
                      :label="__('auth.fields.confirm')"
                      :placeholder="__('auth.fields.confirm_placeholder')"
                      autocomplete="new-password"
                      required />

        <x-auth.submit :label="__('auth.reset_page.submit')" />
    </form>
</x-layouts.guest>
