<?php

namespace App\Http\Controllers;

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

class PaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'reservations' => 'required|array',
            'reservations.*.reservation_id' => 'required|integer',
            'reservations.*.amount' => 'required|numeric|min:0.01',
            'total_amount' => 'required|numeric|min:0.01'
        ]);
      
        $response = [
            'transaction_id' => Str::uuid(),
            'status' => 'pending',
            'timestamp' => Carbon::now()->toIso8601String(),
            'currency' => 'EUR',
            'amount' => $validated['total_amount']
        ];

        $payment = $this->createPaymentRecord($validated['user_id'], $response, $validated['reservations']);

        return response()->json($response);
    }

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
                'payment_method' => 'Tarjeta terminada en ****'
            ]
        ]);
    }

    private function sendConfirmationEmail(Payment $payment): void
    {
        $user = $payment->user()->first(); // Obtener usuario relacionado
        $verificationUrl = URL::signedRoute('payment.verify', ['payment' => $payment->id]);
        
        Mail::to($user->email)->send(
            new FlightPaymentConfirmation($user, $payment, $verificationUrl)
        );
    }

    public function pending()
    {
        // Devuelve pagos pendientes
        return Payment::where('status', 'pending')->with('user')->get();
    }

    public function accept(Payment $payment)
    {
        DB::beginTransaction();
        
        try {
            $payment->status = 'approved';
            $payment->save();
            
            // 1. Obtener reservas asociadas al pago
            $reservations = ReservedTrip::where('user_id', $payment->user_id)->get();

            if ($reservations->isEmpty()) {
                throw new \Exception('No hay reservas para archivar');
            }

            // 2. Mover a tabla de historial
            foreach ($reservations as $reservation) {
                DB::table('reservation_histories')->insert([
                    'user_id' => $reservation->user_id,
                    'trip_id' => $reservation->trip_id,
                    'quantity' => $reservation->quantity,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // 3. Eliminar reservas originales
            ReservedTrip::where('user_id', $payment->user_id)->delete();

            // 4. Enviar correo de confirmación
            $this->sendConfirmationEmail($payment);

            DB::commit();
            
            return response()->json([
                'message' => 'Pago aceptado y reservas archivadas',
                'archived_reservations' => $reservations->count()
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al procesar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function reject(Payment $payment)
    {
        $payment->status = 'rejected';
        $payment->save();
        
        $this->sendRejectionEmail($payment);

        return response()->json(['message' => 'Pago rechazado']);
    }

    private function sendRejectionEmail(Payment $payment): void
    {
        $user = $payment->user()->first();
        $verificationUrl = URL::signedRoute('payment.verify', ['payment' => $payment->id]);
        
        Mail::to($user->email)->send(
            new FlightPaymentRejection($user, $payment, $verificationUrl)
        );
    }

    public function userPayments($userId)
    {
        return Payment::with(['user:id,name,email'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

}