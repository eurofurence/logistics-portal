<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ClubMemberPortalLink extends Mailable
{
    public function __construct(public string $purpose, public string $url, public ?string $loginId) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('member-portal.mail.'.$this->purpose.'.title'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.club-member-portal-link');
    }
}
