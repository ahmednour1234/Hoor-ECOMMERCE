<x-layouts.guest>
    <x-slot:title>{{ __('auth.register.title') }} — {{ __('common.brand') }}</x-slot:title>

    <x-auth.heading :title="__('auth.register.title')"
                    :subtitle="__('auth.register.subtitle')" />

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5">
        @csrf

        <x-auth.field name="name"
                      icon="user"
                      :label="__('auth.fields.name')"
                      :placeholder="__('auth.fields.name_placeholder')"
                      autocomplete="name"
                      required
                      autofocus />

        <x-auth.field name="email"
                      type="email"
                      icon="mail"
                      :label="__('auth.fields.email')"
                      :placeholder="__('auth.fields.email_placeholder')"
                      autocomplete="username"
                      required />

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

        {{-- Required by the form rather than merely displayed: a tick box that
             does not gate the submit is decoration. --}}
        <label for="terms" class="flex items-start gap-2.5 text-sm text-hoor-navy-600/80">
            <input id="terms" type="checkbox" name="terms" value="1" required
                   @checked(old('terms'))
                   class="mt-0.5 rounded border-hoor-cream-300 text-hoor-navy-500
                          focus:ring-hoor-denim-500/40">

            <span>
                {{ __('auth.register.agree') }}
                <a href="{{ route('store.pages.about') }}"
                   class="font-medium text-hoor-denim-600 underline underline-offset-2
                          transition hover:text-hoor-gold-600">
                    {{ __('auth.register.terms') }}
                </a>
            </span>
        </label>

        @error('terms')
            <p class="-mt-3 text-xs text-red-600">{{ $message }}</p>
        @enderror

        <x-auth.submit :label="__('auth.register.submit')" />
    </form>

    {{-- Renders nothing when no provider is configured. --}}
    <x-auth.social />

    <x-auth.divider :label="__('auth.or')" class="mt-6" />

    <p class="text-center text-sm text-hoor-navy-600/80">
        {{ __('auth.register.have_account') }}
        <a href="{{ route('login') }}"
           class="font-medium text-hoor-denim-600 underline underline-offset-2 transition hover:text-hoor-gold-600">
            {{ __('auth.login.submit') }}
        </a>
    </p>
</x-layouts.guest>
