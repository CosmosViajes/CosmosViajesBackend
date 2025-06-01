<?php

// Aquí estamos diciendo qué cosas vamos a usar en este archivo, como los controladores y modelos
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SpaceTripController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReservedTripController;
use App\Http\Controllers\PaymentController;
use App\Models\Payment;
use App\Models\User;
use App\Http\Controllers\Api\ExperienciaController;
use App\Http\Controllers\Api\ExperienciaLikeController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\StatsController;

// Aquí empiezan las rutas, que son como las direcciones o accesos de la web para cada cosa

// Rutas para iniciar sesión, registrarse y cerrar sesión
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
Route::get('/user', [AuthController::class, 'me'])->middleware('auth:api');

// Cambiar el rol (tipo) de un usuario
Route::put('/users/{user}/role', [UserController::class, 'updateRole']);

// Rutas para solicitudes de usuarios (por ejemplo, pedir ser empresa o proveedor)
Route::post('/requests', [RequestController::class, 'store']);
Route::get('/admin/requests', [RequestController::class, 'index']);
Route::put('/admin/requests/{id}', [RequestController::class, 'updateStatus']);

// Rutas para ver y actualizar datos de usuario, cambiar contraseña, buscar por email, etc.
Route::get('/users/{userId}/get-photo', [UserController::class, 'getPhoto']);
Route::post('/users/{userId}/update', [UserController::class, 'update']);
Route::post('/users/{userId}/update-password', [UserController::class, 'update-password']);
Route::post('/user-by-email', [UserController::class, 'getUserIdByEmail']);
Route::get('/users/{userId}', [UserController::class, 'getUserData']);

// Rutas para crear, actualizar, borrar y ver viajes espaciales
Route::post('/trips', [SpaceTripController::class, 'create']);
Route::post('/trips/{id}/update', [SpaceTripController::class, 'update']);
Route::delete('/trips/{id}', [SpaceTripController::class, 'destroy']);
Route::get('/flights', [SpaceTripController::class, 'getFlights']);

// Rutas para ver y gestionar reservas de viajes
Route::get('/users/{userId}/reserved-trips', [ReservedTripController::class, 'getReservedTripsByUser']);
Route::get('/users/{userId}/reserved-trips', [ReservedTripController::class, 'getUserTrips']);
Route::get('trips/{trip}/seats', [ReservedTripController::class, 'getReservedSeats']);
Route::post('reserved-trips', [ReservedTripController::class, 'store']);
Route::post('reserved-trips/cancel-bulk', [ReservedTripController::class, 'cancelBulk'])->middleware('api');
Route::post('/reserved-trips', [ReservedTripController::class, 'create']);
Route::delete('/users/{user}/reserved-trips/{reservation}', [ReservedTripController::class, 'destroy']);

// Rutas para pagos: procesar, ver pendientes, aceptar, rechazar, etc.
Route::post('/payments/process', [PaymentController::class, 'processPayment']);
Route::get('/payments/pending', [PaymentController::class, 'pending']);
Route::put('/payments/{payment}/accept', [PaymentController::class, 'accept']);
Route::put('/payments/{payment}/reject', [PaymentController::class, 'reject']);

// Ruta para verificar un pago (por ejemplo, desde un enlace en el correo)
Route::get('/payments/{payment}/verify', function (Payment $payment) {
    if (!request()->hasValidSignature()) {
        abort(403);
    }
    return view('payment-verification', compact('payment'));
})->name('payment.verify');

// Ver los pagos de un usuario concreto
Route::get('/payments/users/{userId}/payments', [PaymentController::class, 'userPayments']);

// Otra ruta para archivar pagos (parecida a aceptar)
Route::get('/payments/{payment}/archive', [PaymentController::class, 'accept']);

// Ruta de soporte (ayuda)
Route::view('/support', 'support')->name('support');

// Ver todos los proveedores (usuarios que ofrecen viajes)
Route::get('providers', function () {
    return User::where('role', 'provider')
        ->select('id', 'name', 'email')
        ->get();
});

// Ver todos las empresas
Route::get('companies', function () {
    return User::where('role', 'company')
        ->select('id', 'name', 'email')
        ->get();
});

// Ver los viajes de un proveedor concreto
Route::get('providers/{providerId}/flights', function ($providerId) {
    return \App\Models\SpaceTrip::where('company_id', $providerId)
        ->select('id', 'name', 'type', 'price', 'photo', 'departure', 'duration', 'capacity', 'description')
        ->get();
});

// Rutas para experiencias (opiniones, valoraciones, etc.)
Route::prefix('v1')->group(function () {
    Route::apiResource('experiencias', ExperienciaController::class)
        ->only(['index', 'store']);
});

// Dar o quitar "me gusta" en una experiencia
Route::post('experiencias/{id}/like/{userId}', [ExperienciaLikeController::class, 'toggle']);
// Ver a qué experiencias le ha dado "me gusta" un usuario
Route::get('experiencias/user-likes', [ExperienciaLikeController::class, 'userLikes']);
// Borrar una experiencia
Route::delete('experiencias/{id}/destroy', [ExperienciaController::class, 'destroy']);
// Subir una imagen a una experiencia
Route::post('experiencias/image/upload', [ExperienciaController::class, 'uploadImage']);
// Borrar una imagen de una experiencia
Route::delete('experiencias/image/{id}/delete', [ExperienciaController::class, 'deleteImage']);

// Ruta para ver estadísticas avanzadas
Route::get('/stats/advanced-stats', [StatsController::class, 'getAdvancedStats']);

// Ruta para saber qué usuario está conectado (usando un sistema de seguridad)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Ruta para responder a opciones (CORS), normalmente no la usas tú, sino el navegador
Route::options('{any}', function () {
    return response()->json([], 200);
})->where('any', '.*');