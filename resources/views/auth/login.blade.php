<x-layouts.guest>
    <x-slot:title>{{ __('auth.login.title') }} — {{ __('common.brand') }}</x-slot:title>

    <x-auth.heading :title="__('auth.login.title')"
                    :subtitle="__('auth.login.subtitle')" />

    {{-- Status: shown after a password reset link is sent, or a session expires. --}}
    @if (session('status'))
        <div class="mt-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- A failed sign-in is reported once, above the form. Laravel attaches it
         to the email field, but naming which of the two was wrong would tell
         an attacker whether the address exists. --}}
    @if ($errors->has('email') && ! $errors->has('password'))
        <div class="mt-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first('email') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
        @csrf

        <x-auth.field name="email"
                      type="email"
                      icon="mail"
                      :label="__('auth.fields.email')"
                      :placeholder="__('auth.fields.email_placeholder')"
                      autocomplete="username"
                      required
                      autofocus />

        <x-auth.field name="password"
                      type="password"
                      icon="lock"
                      :label="__('auth.fields.password')"
                      :placeholder="__('auth.fields.password_placeholder')"
                      autocomplete="current-password"
                      required />

        <div class="flex flex-wrap items-center justify-between gap-3">
            <label for="remember_me" class="flex items-center gap-2 text-sm text-hoor-navy-600/80">
                <input id="remember_me" type="checkbox" name="remember"
                       class="rounded border-hoor-cream-300 text-hoor-navy-500
                              focus:ring-hoor-denim-500/40">
                {{ __('auth.login.remember') }}
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                   class="text-sm text-hoor-denim-600 underline-offset-2 transition
                          hover:text-hoor-gold-600 hover:underline">
                    {{ __('auth.login.forgot') }}
                </a>
            @endif
        </div>

        <x-auth.submit :label="__('auth.login.submit')" />
    </form>

    <x-auth.divider :label="__('auth.or')" class="mt-6" />

    <p class="text-center text-sm text-hoor-navy-600/80">
        {{ __('auth.login.no_account') }}
        <a href="{{ route('register') }}"
           class="font-medium text-hoor-denim-600 underline underline-offset-2 transition hover:text-hoor-gold-600">
            {{ __('auth.register.submit') }}
        </a>
    </p>
</x-layouts.guest>
