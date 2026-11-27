<?php

namespace App\Mail;

use App\Services\ClubMemberFiles;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ClubMemberMessage extends Mailable
{
    /** @param Collection<int, Media> $files */
    public function __construct(public string $messageSubject, public string $messageBody, public Collection $files, public ?string $replyToAddress = null, public int $messagePriority = 3)
    {
        $this->priority($messagePriority);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->messageSubject, replyTo: $this->replyToAddress ? [$this->replyToAddress] : []);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.club-member-message');
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return $this->files->map(fn (Media $file): Attachment => Attachment::fromStorageDisk($file->disk, $file->getPathRelativeToRoot())
            ->as(ClubMemberFiles::originalName($file))
            ->withMime($file->mime_type))->all();
    }
}
