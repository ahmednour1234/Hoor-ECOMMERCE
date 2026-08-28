<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The confirmation a customer receives after placing an order.
 *
 * Cash on delivery leaves her with nothing in writing — no card statement, no
 * payment receipt — so this is the only record she has of what she ordered and
 * what it will cost, and the only place her order number is written down.
 *
 * Queued, so a slow or unreachable mail server cannot delay the response to a
 * customer who has just pressed "confirm order". The order is already
 * committed by the time this is dispatched.
 *
 * The locale is set through Mailable::locale(), which the framework already
 * provides and which survives serialisation onto the queue — a customer who
 * shopped in Arabic must not receive an English email because the worker
 * happened to boot in the default locale.
 */
class OrderPlaced extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('orders.mail.subject', ['number' => $this->order->number]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.orders.placed',
            with: ['order' => $this->order],
        );
    }
}
