<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReservedTrip;
use App\Models\SpaceTrip;

// Esta clase se encarga de todo lo que tiene que ver con las reservas de viajes espaciales
class ReservedTripController extends Controller
{
    // Saca todas las reservas que ha hecho un usuario
    public function getReservedTripsByUser($userId)
    {
        try {
            // Busca todas las reservas del usuario y la información del viaje
            $reservedTrips = ReservedTrip::with('trip')
                ->where('user_id', $userId)
                ->get();

            // Devuelve la lista de reservas
            return response()->json($reservedTrips, 200);
        } catch (\Exception $e) {
            // Si algo falla, avisa del error
            return response()->json(['message' => 'Error al obtener las reservas'], 500);
        }
    }

    // Crea una nueva reserva sencilla (solo pide usuario y viaje)
    public function create(Request $request)
    {
        try {
            // Comprueba que los datos estén bien
            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,id',
                'trip_id' => 'required|exists:space_trips,id',
            ]);

            // Crea la reserva
            $reservation = ReservedTrip::create($validatedData);

            // Devuelve mensaje de éxito y la reserva creada
            return response()->json([
                'message' => 'Reserva creada exitosamente',
                'data' => $reservation,
            ], 201);
        } catch (\Exception $e) {
            // Si algo falla, avisa del error
            return response()->json([
                'message' => 'Error al crear la reserva',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Devuelve cuántos asientos hay reservados en un viaje
    public function getReservedSeats($tripId)
    {
        $reservedSeats = ReservedTrip::where('trip_id', $tripId)->sum('quantity');
        return response()->json(['reserved_seats' => $reservedSeats]);
    }

    // Crea una reserva, pero aquí se comprueba que no se pase del límite de asientos ni de reservas por persona
    public function store(Request $request)
    {
        // Comprueba que los datos estén bien
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'trip_id' => 'required|exists:trips,id',
            'quantity' => 'required|integer|min:1|max:100'
        ]);

        // Busca el viaje
        $trip = SpaceTrip::findOrFail($validated['trip_id']);
        // Cuenta cuántos asientos ya están reservados
        $totalReserved = ReservedTrip::where('trip_id', $trip->id)->sum('quantity');

        // Si no hay suficientes asientos libres, avisa del error
        if (($totalReserved + $validated['quantity']) > $trip->capacity) {
            return response()->json([
                'message' => 'No hay suficientes asientos disponibles',
                'available_seats' => $trip->capacity - $totalReserved
            ], 422);
        }

        // Mira cuántas reservas tiene ya este usuario para este viaje
        $userReservations = ReservedTrip::where('user_id', $validated['user_id'])
            ->where('trip_id', $trip->id)
            ->sum('quantity');

        // Si el usuario ya reservó muchos asientos, avisa del error
        if (($userReservations + $validated['quantity']) > 10) {
            return response()->json([
                'message' => 'Límite de reservas por usuario excedido',
                'your_reservations' => $userReservations
            ], 422);
        }

        // Si todo está bien, crea la reserva
        $reservation = ReservedTrip::create([
            'user_id' => $validated['user_id'],
            'trip_id' => $validated['trip_id'],
            'quantity' => $validated['quantity'],
            'reservation_date' => now()
        ]);

        // Calcula cuántos asientos quedan libres
        $remainingSeats = $trip->capacity - ($totalReserved + $validated['quantity']);

        // Devuelve mensaje de éxito, asientos que quedan y el id de la reserva
        return response()->json([
            'message' => 'Reserva creada exitosamente',
            'remaining_seats' => $remainingSeats,
            'reservation_id' => $reservation->id
        ], 201);
    }

    // Muestra los viajes que ha reservado un usuario
    public function getUserTrips($userId)
    {
        try {
            $reservedTrips = ReservedTrip::where('user_id', $userId)
                ->with('trip')
                ->get();

            return response()->json($reservedTrips, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener los viajes reservados'], 500);
        }
    }

    // Borra una reserva concreta de un usuario
    public function destroy($userId, $reservationId)
    {
        try {
            // Busca la reserva por su id y el id del usuario
            $reservation = ReservedTrip::where('id', $reservationId)->where('user_id', $userId)->first();

            // Si no existe, avisa que no se encontró
            if (!$reservation) {
                return response()->json(['message' => 'Reserva no encontrada'], 404);
            }

            // Borra la reserva
            $reservation->delete();

            // Devuelve mensaje de éxito
            return response()->json(['message' => 'Reserva cancelada exitosamente'], 200);
        } catch (\Exception $e) {
            // Si algo falla, avisa del error
            return response()->json(['message' => 'Error al cancelar la reserva'], 500);
        }
    }
}