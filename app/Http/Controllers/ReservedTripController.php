<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReservedTrip;
use App\Models\SpaceTrip;


class ReservedTripController extends Controller
{
    public function getReservedTripsByUser($userId)
    {
        try {
            $reservedTrips = ReservedTrip::with('trip')
                ->where('user_id', $userId)
                ->get();

            return response()->json($reservedTrips, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener las reservas'], 500);
        }
    }

    /**
     * Crea una nueva reserva.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        try {
            // Validar los datos enviados por el cliente
            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,id', // Verifica que el usuario exista
                'trip_id' => 'required|exists:space_trips,id', // Verifica que el viaje exista
            ]);

            // Crear la reserva en la base de datos utilizando Eloquent
            $reservation = ReservedTrip::create($validatedData);

            return response()->json([
                'message' => 'Reserva creada exitosamente',
                'data' => $reservation,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la reserva',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getReservedSeats($tripId)
    {
        $reservedSeats = ReservedTrip::where('trip_id', $tripId)->sum('quantity');
        return response()->json(['reserved_seats' => $reservedSeats]);
    }

    public function store(Request $request)
    {
        // Validación de datos
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'trip_id' => 'required|exists:trips,id',
            'quantity' => 'required|integer|min:1|max:100' // Ajusta el máximo según tu necesidad
        ]);

        // Obtener el viaje y las reservas existentes
        $trip = SpaceTrip::findOrFail($validated['trip_id']);
        $totalReserved = ReservedTrip::where('trip_id', $trip->id)->sum('quantity');

        // Validar disponibilidad
        if (($totalReserved + $validated['quantity']) > $trip->capacity) {
            return response()->json([
                'message' => 'No hay suficientes asientos disponibles',
                'available_seats' => $trip->capacity - $totalReserved
            ], 422);
        }

        // Validar límite por usuario (opcional)
        $userReservations = ReservedTrip::where('user_id', $validated['user_id'])
            ->where('trip_id', $trip->id)
            ->sum('quantity');

        if (($userReservations + $validated['quantity']) > 10) { // Límite de 10 reservas por usuario
            return response()->json([
                'message' => 'Límite de reservas por usuario excedido',
                'your_reservations' => $userReservations
            ], 422);
        }

        // Crear reserva
        $reservation = ReservedTrip::create([
            'user_id' => $validated['user_id'],
            'trip_id' => $validated['trip_id'],
            'quantity' => $validated['quantity'],
            'reservation_date' => now() // Campo adicional si lo necesitas
        ]);

        // Actualizar contadores
        $remainingSeats = $trip->capacity - ($totalReserved + $validated['quantity']);

        return response()->json([
            'message' => 'Reserva creada exitosamente',
            'remaining_seats' => $remainingSeats,
            'reservation_id' => $reservation->id
        ], 201);
    }

    public function getUserTrips($userId)
    {
        try {
            // Obtener las reservas asociadas al ID del usuario
            $reservedTrips = ReservedTrip::where('user_id', $userId)
                ->with('trip') // Cargar información del viaje relacionado
                ->get();

            return response()->json($reservedTrips, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener los viajes reservados'], 500);
        }
    }

    public function destroy($userId, $reservationId)
    {
        try {
            $reservation = ReservedTrip::where('id', $reservationId)->where('user_id', $userId)->first();

            if (!$reservation) {
                return response()->json(['message' => 'Reserva no encontrada'], 404);
            }

            $reservation->delete();

            return response()->json(['message' => 'Reserva cancelada exitosamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al cancelar la reserva'], 500);
        }
    }
}
