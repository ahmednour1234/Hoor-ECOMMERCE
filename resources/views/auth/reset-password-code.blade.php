{{--
    Step three: the new password.

    The code is carried in a hidden field and re-checked on submit rather than
    trusted from the session — it may have expired since step two, and it must
    be spent exactly once.
--}}
<x-layouts.guest>
    <x-slot:title>{{ __('auth.reset.new_title') }} — {{ __('common.brand') }}</x-slot:title>

    <x-auth.heading :title="__('auth.reset.new_title')"
                    :subtitle="__('auth.reset.new_lead')" />

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-5">
        @csrf

        <x-auth.field name="code"
                      type="text"
                      icon="lock"
                      :label="__('auth.reset.code')"
                      :value="old('code')"
                      placeholder="000000"
                      autocomplete="one-time-code"
                      inputmode="numeric"
                      dir="ltr"
                      maxlength="6"
                      class="text-center font-mono text-lg tracking-[0.4em]"
                      required />

        <x-auth.field name="password"
                      type="password"
                      icon="lock"
                      :label="__('auth.fields.password')"
                      autocomplete="new-password"
                      required
                      autofocus />

        <x-auth.field name="password_confirmation"
                      type="password"
                      icon="lock"
                      :label="__('auth.fields.confirm')"
                      autocomplete="new-password"
                      required />

        <x-auth.submit :label="__('auth.reset.save')" />
    </form>

    <x-auth.divider class="mt-6" />

    <p class="text-center text-sm">
        <a href="{{ route('login') }}"
           class="font-medium text-hoor-navy-700 transition hover:text-hoor-gold-600">
            {{ __('auth.reset.back') }}
        </a>
    </p>
</x-layouts.guest>
