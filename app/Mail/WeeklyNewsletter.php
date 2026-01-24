<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyNewsletter extends Mailable
{
    use Queueable, SerializesModels;

    public $post;
    public $cliente;

    /**
     * Create a new message instance.
     */
    public function __construct($post, $cliente)
    {
        $this->post = $post;
        $this->cliente = $cliente;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.mailers.newsletter.username'), 'Asistencia Vircom Blog'),
            subject: '📰 Vircom Al Día: ' . $this->post->titulo,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-newsletter',
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
