<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class TeamInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public TeamInvitation $invitation, public string $acceptUrl) {}

    public function envelope(): Envelope
    {
        $teamName = $this->invitation->team->name ?? 'a team';

        return new Envelope(
            subject: 'You\'re invited to join '.$teamName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.team-invitation',
            with: [
                'team' => $this->invitation->team,
                'acceptUrl' => $this->acceptUrl,
            ],
        );
    }
}
