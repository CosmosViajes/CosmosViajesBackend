<?php

namespace App\Mail;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Esta clase sirve para mandar un correo cuando un pago ha sido rechazado
class FlightPaymentRejection extends Mailable
{
    use Queueable, SerializesModels; // Esto ayuda a enviar el correo bien y a guardarlo en cola si hace falta

    // Cuando creamos el correo, guardamos el usuario y el pago que fue rechazado
    public function __construct(
        public User $user,
        public Payment $payment
    ) {}

    // Esto pone el asunto del correo, o sea, el título que aparece cuando lo recibes
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pago rechazado #' . $this->payment->transaction_id,
        );
    }

    // Aquí decimos qué plantilla se va a usar para el contenido del correo
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.flight_payment_rejection',
        );
    }

    // Esto es otra forma de armar el correo, diciendo el asunto y la vista que se va a mostrar
    public function build()
    {
        return $this->subject('Pago rechazado')
            ->view('emails.flight_payment_rejection');
    }

    // Aquí podríamos poner archivos adjuntos, pero en este caso no mandamos nada extra
    public function attachments(): array
    {
        return [];
    }
}