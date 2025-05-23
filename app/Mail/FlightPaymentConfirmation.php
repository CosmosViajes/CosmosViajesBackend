<?php

namespace App\Mail;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Esta clase sirve para mandar un correo cuando alguien paga un viaje
class FlightPaymentConfirmation extends Mailable
{
    use Queueable, SerializesModels; // Esto ayuda a que el correo se mande bien y se pueda guardar en cola si hace falta

    // Aquí guardamos el usuario, el pago y un enlace de verificación cuando creamos el correo
    public function __construct(
        public User $user,
        public Payment $payment,
        public string $verificationUrl
    ) {}

    // Esto pone el asunto del correo, o sea, el título que ves al recibirlo
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmación de pago #' . $this->payment->transaction_id,
        );
    }

    // Aquí decimos qué plantilla se va a usar para el contenido del correo
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.flight-payment-confirmation',
        );
    }

    // Esto es otra forma de construir el correo, poniendo el asunto y la vista que se va a mostrar
    public function build()
    {
        return $this->subject('Confirmación de pago')
            ->view('emails.flight-payment-confirmation');
    }

    // Aquí podríamos poner archivos adjuntos, pero en este caso no mandamos nada extra
    public function attachments(): array
    {
        return [];
    }
}