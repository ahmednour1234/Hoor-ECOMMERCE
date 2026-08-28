{{--
    The order confirmation.

    Markdown mail rather than a bespoke HTML template: it renders acceptably in
    every client without a table-layout of our own to maintain, and degrades to
    readable plain text.

    Every figure comes from the order's own snapshot columns, not from the live
    catalog — the email must say what was ordered, even if a price changes the
    next day.
--}}
@php
    $address = $order->address;
    $trackUrl = route('store.tracking.index');
@endphp

<x-mail::message>
# {{ __('orders.mail.greeting', ['name' => $order->customerName()]) }}

{{ __('orders.mail.lead') }}

**{{ __('orders.mail.number') }}:** {{ $order->number }}
**{{ __('orders.mail.placed') }}:** {{ $order->created_at->translatedFormat('d M Y') }}

## {{ __('orders.mail.items') }}

<x-mail::table>
| {{ __('orders.admin.product') }} | {{ __('returns.fields.quantity') }} | {{ __('checkout.summary.total') }} |
|:---|:---:|---:|
@foreach ($order->items as $item)
| {{ $item->product_name }}{{ $item->size_name || $item->color_name ? ' — '.collect([$item->size_name, $item->color_name])->filter()->join(' / ') : '' }} | {{ $item->quantity }} | {{ \App\Casts\Money::format($item->line_total) }} |
@endforeach
</x-mail::table>

**{{ __('checkout.summary.subtotal') }}:** {{ \App\Casts\Money::format($order->subtotal) }}
@if ($order->discount > 0)
**{{ __('checkout.summary.discount') }}:** −{{ \App\Casts\Money::format($order->discount) }}
@endif
**{{ __('checkout.summary.shipping') }}:** {{ \App\Casts\Money::format($order->shipping) }}
**{{ __('checkout.summary.total') }}:** {{ \App\Casts\Money::format($order->total) }}

## {{ __('orders.mail.payment') }}

{{ __('orders.mail.cod') }}

@if ($address)
## {{ __('orders.mail.delivery') }}

{{ $address->full_name }}
{{ $address->phone }}
{{ $address->address }}
{{ collect([$address->area_name, $address->governorate_name])->filter()->join('، ') }}
@endif

<x-mail::button :url="$trackUrl">
{{ __('orders.mail.track') }}
</x-mail::button>

{{ __('orders.mail.track_hint') }}

@if ($contactPhone = app(\App\Services\SettingsService::class)->get('contact.phone'))
{{ __('orders.mail.help', ['phone' => $contactPhone]) }}
@endif

{{ __('orders.mail.signoff') }}
</x-mail::message>
