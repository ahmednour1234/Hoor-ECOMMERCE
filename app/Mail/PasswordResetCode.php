<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The reset code itself.
 *
 * The plaintext code lives only here and in the customer's inbox — the
 * database holds a hash of it. That means this object must not be logged or
 * serialised anywhere it could be read back, which is why nothing else is
 * carried on it.
 */
class PasswordResetCode extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly int $expiresInMinutes,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('auth.reset.mail.subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.auth.reset-code',
            with: [
                'code'    => $this->code,
                'minutes' => $this->expiresInMinutes,
            ],
        );
    }
}
