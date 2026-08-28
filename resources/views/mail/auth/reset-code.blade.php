{{--
    The password reset code.

    Deliberately short: a customer skimming this on a phone needs the digits,
    how long they last, and what to do if it was not her.
--}}
<x-mail::message>
# {{ __('auth.reset.mail.heading') }}

{{ __('auth.reset.mail.lead') }}

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

{{ __('auth.reset.mail.expires', ['minutes' => $minutes]) }}

{{ __('auth.reset.mail.ignore') }}

{{ __('orders.mail.signoff') }}
</x-mail::message>
