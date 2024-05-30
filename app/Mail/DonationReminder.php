<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DonationReminder extends Mailable
{
    use Queueable, SerializesModels;
    protected $user;
    protected $lastDonation;
    protected $nextDonationDate;
    /**
     * Create a new message instance.
     */
    public function __construct($user, $lastDonation, $nextDonationDate)
    {
        $this->user = $user;
        $this->lastDonation = $lastDonation;
        $this->nextDonationDate = $nextDonationDate;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Donation Reminder',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return (new Content)
            ->view('mail.donation-reminder')
            ->with([
                'user' => $this->user,
                'lastDonation' => $this->lastDonation,
                'nextDonationDate' => $this->nextDonationDate
            ]);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
