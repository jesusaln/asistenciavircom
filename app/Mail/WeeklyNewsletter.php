<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

use Illuminate\Mail\Mailables\Headers;

class WeeklyNewsletter extends Mailable
{
    use Queueable, SerializesModels;

    public $post;
    public $cliente;
    public $trackToken;

    /**
     * Create a new message instance.
     */
    public function __construct($post, $cliente, $trackToken = null)
    {
        $this->post = $post;
        $this->cliente = $cliente;
        $this->trackToken = $trackToken;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $fromAddress = config('mail.mailers.newsletter.from.address')
            ?? config('mail.mailers.newsletter.username')
            ?? config('mail.from.address');

        return new Envelope(
            from: new Address($fromAddress, 'Asistencia Vircom Blog'),
            subject: $this->post->titulo,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-newsletter',
            text: 'emails.weekly-newsletter-text',
        );
    }

    /**
     * Get the message headers.
     */
    public function headers(): Headers
    {
        return new Headers(
            text: [
                'List-Unsubscribe' => '<' . config('app.url') . '/newsletter/unsubscribe?email=' . urlencode($this->cliente->email) . '>',
                'Precedence' => 'bulk',
                'X-Auto-Response-Suppress' => 'OOF, AutoReply',
            ],
        );
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
