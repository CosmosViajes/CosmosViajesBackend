<?php

namespace App\Mail;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FlightPaymentRejection extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Payment $payment
    ) {}

    // Configuración del asunto (método envelope)
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pago rechazado #' . $this->payment->transaction_id,
        );
    }

    // Configuración del contenido (método content)
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.flight_payment_rejection',
        );
    }

    public function build()
    {
        return $this->subject('Pago rechazado')
            ->view('emails.flight_payment_rejection');
    }

    public function attachments(): array
    {
        return [];
    }
}