@component('mail::layout')
@slot('header')
@component('mail::header', ['url' => config('app.url')])
{{ config('app.name') }}
@endcomponent
@endslot

# ❌ ¡Pago rechazado!

**Hola {{ $user->name }},** lamentamos informarte que hemos detectado un problema con tu pago:

@component('mail::panel', ['color' => 'red'])
## Detalles del pago rechazado
- **Transacción:** {{ $payment->transaction_id }}
- **Monto intentado:** {{ number_format($payment->amount, 2) }} {{ $payment->metadata['currency'] }}
- **Método:** {{ $payment->metadata['payment_method'] }}
- **Fecha del intento:** {{ $payment->created_at->format('d/m/Y H:i') }}
@endcomponent

@component('mail::panel', ['color' => 'red'])
**Posibles motivos:**
- Fondos insuficientes
- Datos de tarjeta incorrectos
- Límite de la tarjeta excedido
- Problemas técnicos temporales
@endcomponent

@component('mail::button', ['url' => route('support'), 'color' => 'red'])
Contactar con soporte
@endcomponent

@component('mail::panel')
**Reservas afectadas:**
@foreach($payment->metadata['reservations'] as $reservation)
- Reserva #{{ $reservation['reservation_id'] }}: {{ $reservation['amount'] }}€
@endforeach
@endcomponent

@slot('footer')
@component('mail::footer')
⚠️ **Importante:** Tus reservas quedarán en espera hasta que se regularice el pago
@endcomponent
@endslot
@endcomponent
