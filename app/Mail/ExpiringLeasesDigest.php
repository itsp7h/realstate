<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Staff digest of leases running out. Internal, so it goes to admins rather
 * than tenants: whether a lease is renewed is a decision, not a notification.
 */
class ExpiringLeasesDigest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Collection $leases,
        public int $days,
        public Carbon $until,
    ) {
    }

    public function envelope(): Envelope
    {
        $count = $this->leases->count();

        return new Envelope(
            subject: $count.' lease'.($count === 1 ? '' : 's')." ending in the next {$this->days} days — Promoseven Real Estate",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.expiring-leases',
            with: ['leases' => $this->leases, 'days' => $this->days, 'until' => $this->until],
        );
    }
}
