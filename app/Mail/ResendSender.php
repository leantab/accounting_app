<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Resend\Laravel\Facades\Resend;

class ResendSender
{
    /**
     * Renders the mailable and sends it through the Resend API.
     *
     * The idempotency key makes retries safe: the same key and payload never sends twice within 24 hours.
     */
    public static function send(Mailable $mailable, string $to, ?string $idempotencyKey = null): void
    {
        Resend::emails()->send(
            [
                'from' => config('mail.from.name').' <'.config('mail.from.address').'>',
                'to' => [$to],
                'subject' => $mailable->envelope()->subject,
                'html' => $mailable->render(),
            ],
            $idempotencyKey === null ? [] : ['idempotency_key' => $idempotencyKey],
        );
    }
}
