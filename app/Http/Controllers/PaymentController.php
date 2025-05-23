<?php

// Esto dice en qué carpeta está este archivo
namespace App\Http\Controllers;

// Aquí decimos qué cosas vamos a usar en este archivo
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Payment;
use App\Models\User;
use App\Mail\FlightPaymentConfirmation;
use App\Mail\FlightPaymentRejection;
use App\Models\ReservedTrip;
use BcMath\Number;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;

// Esta clase se encarga de todo lo relacionado con los pagos en la web
class PaymentController extends Controller
{
    // Esta función se usa cuando alguien quiere pagar un viaje
    public function processPayment(Request $request)
    {
        // Aquí comprobamos que los datos que manda el usuario están bien
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id', // El usuario debe existir
            'reservations' => 'required|array', // Debe haber una lista de reservas
            'reservations.*.reservation_id' => 'required|integer', // Cada reserva debe tener su id
            'reservations.*.amount' => 'required|numeric|min:0.01', // Cada reserva debe tener un precio
            'total_amount' => 'required|numeric|min:0.01' // El total debe ser mayor que cero
        ]);
      
        // Creamos una respuesta con los datos del pago, como un ticket
        $response = [
            'transaction_id' => Str::uuid(), // Un número único para este pago
            'status' => 'pending', // Al principio está pendiente
            'timestamp' => Carbon::now()->toIso8601String(), // Fecha y hora
            'currency' => 'EUR', // Moneda
            'amount' => $validated['total_amount'] // El total a pagar
        ];

        // Guardamos el pago en la base de datos
        $payment = $this->createPaymentRecord($validated['user_id'], $response, $validated['reservations']);

        // Devolvemos la respuesta al usuario
        return response()->json($response);
    }

    // Esta función guarda el pago en la base de datos
    private function createPaymentRecord(int $userId, array $data, array $reservations): Payment
    {
        return Payment::create([
            'user_id' => $userId,
            'transaction_id' => $data['transaction_id'],
            'amount' => $data['amount'],
            'status' => $data['status'],
            'metadata' => [
                'currency' => $data['currency'],
                'timestamp' => $data['timestamp'],
                'reservations' => $reservations,
                'payment_method' => 'Tarjeta terminada en ****' // Aquí pondríamos los últimos números de la tarjeta
            ]
        ]);
    }

    // Esta función manda un correo de confirmación cuando el pago va bien
    private function sendConfirmationEmail(Payment $payment): void
    {
        $user = $payment->user()->first();
        $verificationUrl = URL::signedRoute('payment.verify', ['payment' => $payment->id]);
        
        Mail::to($user->email)->send(
            new FlightPaymentConfirmation($user, $payment, $verificationUrl)
        );
    }

    // Esta función muestra todos los pagos que están pendientes (todavía no aprobados)
    public function pending()
    {
        return Payment::where('status', 'pending')->with('user')->get();
    }

    // Esta función se usa para aceptar un pago (por ejemplo, cuando todo está bien)
    public function accept(Payment $payment)
    {
        DB::beginTransaction(); // Empezamos una operación que, si algo falla, se deshace todo
        
        try {
            $payment->status = 'approved'; // Cambiamos el estado a aprobado
            $payment->save();
            
            // Buscamos todas las reservas de este usuario
            $reservations = ReservedTrip::where('user_id', $payment->user_id)->get();

            if ($reservations->isEmpty()) {
                throw new \Exception('No hay reservas para archivar');
            }

            // Guardamos cada reserva en el historial (como un registro de lo que ha pasado)
            foreach ($reservations as $reservation) {
                DB::table('reservation_histories')->insert([
                    'user_id' => $reservation->user_id,
                    'trip_id' => $reservation->trip_id,
                    'quantity' => $reservation->quantity,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Borramos las reservas porque ya están archivadas
            ReservedTrip::where('user_id', $payment->user_id)->delete();

            // Mandamos un correo de confirmación al usuario
            $this->sendConfirmationEmail($payment);

            DB::commit(); // Si todo va bien, guardamos los cambios
            
            return response()->json([
                'message' => 'Pago aceptado y reservas archivadas',
                'archived_reservations' => $reservations->count()
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack(); // Si algo falla, deshacemos todo
            return response()->json([
                'message' => 'Error al procesar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Esta función se usa para rechazar un pago (por ejemplo, si algo está mal)
    public function reject(Payment $payment)
    {
        $payment->status = 'rejected'; // Cambiamos el estado a rechazado
        $payment->save();
        
        $this->sendRejectionEmail($payment); // Mandamos un correo diciendo que el pago fue rechazado

        return response()->json(['message' => 'Pago rechazado']);
    }

    // Esta función manda un correo cuando el pago es rechazado
    private function sendRejectionEmail(Payment $payment): void
    {
        $user = $payment->user()->first();
        $verificationUrl = URL::signedRoute('payment.verify', ['payment' => $payment->id]);
        
        Mail::to($user->email)->send(
            new FlightPaymentRejection($user, $payment, $verificationUrl)
        );
    }

    // Esta función muestra todos los pagos de un usuario concreto
    public function userPayments($userId)
    {
        return Payment::with(['user:id,name,email'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

}