{{--
    Step two: enter the code from the email.

    The email being reset is shown but not editable — it lives in the session,
    so a customer cannot change whose password she is resetting between steps.
--}}
<x-layouts.guest>
    <x-slot:title>{{ __('auth.reset.code_title') }} — {{ __('common.brand') }}</x-slot:title>

    <x-auth.heading :title="__('auth.reset.code_title')"
                    :subtitle="__('auth.reset.code_lead', ['email' => $email, 'minutes' => $minutes])" />

    @if (session('status'))
        <div class="mt-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.code.verify') }}" class="mt-6 space-y-5">
        @csrf

        {{-- Digits only, and always left to right: a six-digit code reads the
             same way in Arabic, and inputmode numeric brings up the number pad
             on a phone. --}}
        <x-auth.field name="code"
                      type="text"
                      icon="lock"
                      :label="__('auth.reset.code')"
                      placeholder="000000"
                      autocomplete="one-time-code"
                      inputmode="numeric"
                      dir="ltr"
                      maxlength="6"
                      class="text-center font-mono text-lg tracking-[0.4em]"
                      required
                      autofocus />

        <x-auth.submit :label="__('auth.reset.verify')" />
    </form>

    {{-- A second form rather than a link: resending is a state change, so it
         must be a POST with a token behind it. --}}
    <form method="POST" action="{{ route('password.code.resend') }}" class="mt-4">
        @csrf

        <button type="submit"
                class="w-full text-center text-sm text-hoor-denim-600 transition hover:text-hoor-denim-700">
            {{ __('auth.reset.resend') }}
        </button>
    </form>

    <x-auth.divider class="mt-6" />

    <p class="text-center text-sm">
        <a href="{{ route('login') }}"
           class="font-medium text-hoor-navy-700 transition hover:text-hoor-gold-600">
            {{ __('auth.reset.back') }}
        </a>
    </p>
</x-layouts.guest>
