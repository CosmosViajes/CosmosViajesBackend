@component('mail::layout')
@slot('header')
@component('mail::header', ['url' => config('app.url')])
{{ config('app.name') }}
@endcomponent
@endslot

# ¡Pago confirmado para tu vuelo!

**Hola {{ $user->name }},** aquí están los detalles de tu reserva:

@component('mail::panel')
## Detalles de pago
- **Transacción:** {{ $payment->transaction_id }}
- **Monto:** {{ number_format($payment->amount, 2) }} {{ $payment->metadata['currency'] }}
- **Método:** {{ $payment->metadata['payment_method'] }}
- **Fecha de pago:** {{ $payment->created_at->format('d/m/Y H:i') }}
@endcomponent

@component('mail::button', ['url' => $verificationUrl])
Verificar Pago
@endcomponent

@component('mail::panel')
**Reservas:**
@foreach($payment->metadata['reservations'] as $reservation)
- Reserva #{{ $reservation['reservation_id'] }}: {{ $reservation['amount'] }}€
@endforeach
@endcomponent

@slot('footer')
@component('mail::footer')
© {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
@endcomponent
@endslot
@endcomponent