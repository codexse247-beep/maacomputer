<?php

namespace App\Jobs;

use App\Support\Notifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Sends one email in the background so pages never wait for the mail server. */
class SendMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 1; // the mail server may have accepted it even when it answered badly — never send twice

    public function __construct(
        public string $to,
        public string $subject,
        public string $text,
        public string $logText,
        public ?string $event = null,
        public ?int $studentId = null,
    ) {}

    public function handle(): void
    {
        Notifier::sendEmail($this->to, $this->subject, $this->text, $this->logText, $this->event, $this->studentId);
    }
}
