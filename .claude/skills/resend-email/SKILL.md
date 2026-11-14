---
name: resend-email
description:
  Sends and configures email through Resend in this Laravel app. Use when working
  on mail, mailables, queued mail jobs, MAIL_* or RESEND_* env settings, domain
  verification, webhooks, idempotency, retries, or Resend errors.
---

# Resend Email

Source: https://resend.com/docs/ai-onboarding.md and https://resend.com/docs/send-with-laravel.md (fetched 2026-11-14). Any docs page has a markdown version by appending `.md`; all docs in one file: https://resend.com/docs/llms-full.txt.

## State of this project

- `resend/resend-laravel` is already in `composer.json`; `config/mail.php` already has the `resend` mailer (`'transport' => 'resend'`) and `config/services.php` has a `resend` entry. Do not add dependencies without approval.
- `.env` still has `MAIL_MAILER=log` (emails are only logged). Switching to Resend is an env change by the user.
- `QUEUE_CONNECTION=database`: emails go through jobs, so a queue worker must be running.
- Existing mail: `App\Mail\UserInviteMail`, `App\Mail\TimeTrackerApprovedMail` (sent by `App\Jobs\SendTimeTrackerApprovedMail`). All email text must be Spanish (LATAM).

- Emails are sent through the Resend facade with `App\Mail\ResendSender::send($mailable, $to, $idempotencyKey)` (renders the mailable, uses `mail.from`). Used by `ManageUsers` (invite, no key) and `SendTimeTrackerApprovedMail` (key `time-tracker-approved/{id}-{amountCents}`, 4 tries, no retry on 400/401/403/422). Tests mock `Resend::shouldReceive('emails')`.

## Setup (the user does the account steps)

1. A human creates the Resend account and an API key (https://resend.com/docs/create-an-api-key.md). Never type or store a real key; the user puts it in `.env`.
2. A sending domain must be verified in https://resend.com/domains by adding the DNS records at the DNS provider. Without a verified domain, only `onboarding@resend.dev` works.
3. `.env`:

```ini
MAIL_MAILER=resend
RESEND_API_KEY=re_xxxxxxxxx
MAIL_FROM_ADDRESS=notificaciones@tu-dominio.com
MAIL_FROM_NAME="${APP_NAME}"
```

On Laravel Cloud, connecting Resend as an email resource injects `RESEND_API_KEY`, `MAIL_MAILER`, `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME`.

## Sending

Use Laravel's `Mail` facade with Mailables. Keep sending inside queued jobs, never in the request:

```php
Mail::to($user->email)->send(new TimeTrackerApprovedMail($tracker));
```

The Resend facade is the alternative when Resend-only features are needed (scheduling, attachments by API, idempotency keys):

```php
use Resend\Laravel\Facades\Resend;

Resend::emails()->send([
    'from' => 'Acme <notificaciones@tu-dominio.com>',
    'to' => [$user->email],
    'subject' => 'Asunto',
    'html' => (new SomeMailable($model))->render(),
]);
```

Limits and rules:

- Single send (`POST /emails`): max 50 recipients, attachments up to 40 MB total, scheduling allowed.
- Batch (`POST /emails/batch`): up to 100 emails per request, no attachments or scheduling. The whole batch fails if one email fails validation, so validate first.
- Default rate limit: 10 requests per second. With many queued emails, use job rate limiting or small batches.

## Production practices

- Idempotency keys prevent duplicate sends on retry: `<event-type>/<entity-id>` for single sends (e.g. `time-tracker-approved/123`), `batch-<event-type>/<batch-id>` for batches. Keys last 24 hours, max 256 chars. Same key and payload returns the original response; same key with a different payload returns 409.
- Errors: 400/422 and 401/403 must not be retried (fix the request, API key or domain). 409 needs a new key or corrected payload. Retry 429 and 500 with exponential backoff starting at 1s, 3 to 5 retries, always with idempotency keys. In jobs, set `$tries` and `backoff()` accordingly and do not retry validation or auth failures.

## Webhooks (delivery events)

`resend-laravel` ships a controller at `/resend/webhook` that dispatches a Laravel event per Resend event (`email.delivered` becomes `EmailDelivered`).

- Register the public HTTPS URL in the Resend dashboard (use a tunnel such as ngrok locally).
- Exclude `resend/*` from CSRF (in Laravel 12 configure it in `bootstrap/app.php` with `validateCsrfTokens(except: ['resend/*'])`).
- Set `RESEND_WEBHOOK_SECRET` (signing secret from the dashboard) so signatures are verified.

## Testing

Never hit Resend in tests: use `Mail::fake()` or `Queue::fake()` (see `tests/Feature/Actions/TimeTracker/TimeTrackerApprovedMailTest.php`).

## Tools and further docs

- Resend MCP server: `https://mcp.resend.com/mcp` (OAuth) or local `npx resend-mcp`; setup at https://resend.com/docs/mcp-server.md.
- Resend CLI: https://resend.com/docs/cli.md. Docs MCP: `npx add-mcp https://resend.com/docs/mcp`.
- Official Resend email skills (not installed here): `npx skills add resend/resend-skills` (also `resend/react-email`, `resend/email-best-practices` for SPF/DKIM/DMARC and compliance).
- Laravel examples repo: `resend-examples` (sending, inbound webhooks, audiences, domains).
