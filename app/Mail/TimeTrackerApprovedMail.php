<?php

namespace App\Mail;

use App\Filament\Money;
use App\Models\TimeTracker;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TimeTrackerApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TimeTracker $timeTracker) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu planilla "'.$this->timeTracker->name.'" fue aprobada',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.time-tracker-approved',
            with: [
                'userName' => $this->timeTracker->user->name,
                'trackerName' => $this->timeTracker->name,
                'amount' => '$ '.Money::format($this->timeTracker->amount),
            ],
        );
    }
}
