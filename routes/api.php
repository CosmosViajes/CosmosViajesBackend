<?php

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

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
Route::get('/user', [AuthController::class, 'me'])->middleware('auth:api');

Route::put('/users/{user}/role', [UserController::class, 'updateRole']);

Route::post('/requests', [RequestController::class, 'store']);
Route::get('/admin/requests', [RequestController::class, 'index']);
Route::put('/admin/requests/{id}', [RequestController::class, 'updateStatus']);

Route::get('/users/{userId}/get-photo', [UserController::class, 'getPhoto']);
Route::post('/users/{userId}/update', [UserController::class, 'update']);
Route::post('/users/{userId}/update-password', [UserController::class, 'update-password']);
Route::post('/user-by-email', [UserController::class, 'getUserIdByEmail']);
Route::get('/users/{userId}', [UserController::class, 'getUserData']);

Route::post('/trips', [SpaceTripController::class, 'create']);
Route::post('/trips/{id}/update', [SpaceTripController::class, 'update']);
Route::delete('/trips/{id}', [SpaceTripController::class, 'destroy']);
Route::get('/flights', [SpaceTripController::class, 'getFlights']);

Route::get('/users/{userId}/reserved-trips', [ReservedTripController::class, 'getReservedTripsByUser']);
Route::get('/users/{userId}/reserved-trips', [ReservedTripController::class, 'getUserTrips']);
Route::get('trips/{trip}/seats', [ReservedTripController::class, 'getReservedSeats']);
Route::post('reserved-trips', [ReservedTripController::class, 'store']);
Route::post('reserved-trips/cancel-bulk', [ReservedTripController::class, 'cancelBulk'])->middleware('api');
Route::post('/reserved-trips', [ReservedTripController::class, 'create']);
Route::delete('/users/{user}/reserved-trips/{reservation}', [ReservedTripController::class, 'destroy']);

Route::post('/payments/process', [PaymentController::class, 'processPayment']);
Route::get('/payments/pending', [PaymentController::class, 'pending']);
Route::put('/payments/{payment}/accept', [PaymentController::class, 'accept']);
Route::put('/payments/{payment}/reject', [PaymentController::class, 'reject']);

Route::get('/payments/{payment}/verify', function (Payment $payment) {
    if (!request()->hasValidSignature()) {
        abort(403);
    }

    return view('payment-verification', compact('payment'));
})->name('payment.verify');

Route::get('/payments/users/{userId}/payments', [PaymentController::class, 'userPayments']);

Route::get('/payments/{payment}/archive', [PaymentController::class, 'accept']);

Route::view('/support', 'support')->name('support');

Route::get('providers', function () {
    return User::where('role', 'provider')
        ->select('id', 'name')
        ->get();
});

Route::get('providers/{providerId}/flights', function ($providerId) {
    return \App\Models\SpaceTrip::where('company_id', $providerId)
        ->select('id', 'name', 'type', 'price', 'photo', 'departure', 'duration', 'capacity', 'description')
        ->get();
});

Route::prefix('v1')->group(function () {
    Route::apiResource('experiencias', ExperienciaController::class)
        ->only(['index', 'store']);
});

Route::post('experiencias/{id}/like/{userId}', [ExperienciaLikeController::class, 'toggle']);
Route::get('experiencias/user-likes', [ExperienciaLikeController::class, 'userLikes']);
Route::delete('experiencias/{id}/destroy', [ExperienciaController::class, 'destroy']);

Route::post('experiencias/image/upload', [ExperienciaController::class, 'uploadImage']);
Route::delete('experiencias/image/{id}/delete', [ExperienciaController::class, 'deleteImage']);

Route::get('/stats/advanced-stats', [StatsController::class, 'getAdvancedStats']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::options('{any}', function () {
    return response()->json([], 200);
})->where('any', '.*');
