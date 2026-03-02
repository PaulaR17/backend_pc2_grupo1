<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\GuestSessionController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\PetController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\AdminController;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

//usuarios
Route::get('/users/{userId}', [UserController::class, 'show']);
Route::put('/users/{userId}', [UserController::class, 'update']);
Route::delete('/users/{userId}', [UserController::class, 'deactivate']); // status=false
Route::get('/users/{userId}/stats', [UserController::class, 'stats']);

//user profile
Route::get('/users/{userId}/profile', [UserProfileController::class, 'show']);
Route::put('/users/{userId}/locations/home', [UserProfileController::class, 'setHome']);
Route::put('/users/{userId}/locations/work', [UserProfileController::class, 'setWork']);
Route::delete('/users/{userId}/locations/home', [UserProfileController::class, 'clearHome']);
Route::delete('/users/{userId}/locations/work', [UserProfileController::class, 'clearWork']);

//coches y etiquetas
Route::get('/users/{userId}/vehicles', [VehicleController::class, 'index']);
Route::post('/users/{userId}/vehicles', [VehicleController::class, 'store']);
Route::get('/users/{userId}/vehicles/{vehicleId}', [VehicleController::class, 'show']);
Route::put('/users/{userId}/vehicles/{vehicleId}', [VehicleController::class, 'update']);
Route::delete('/users/{userId}/vehicles/{vehicleId}', [VehicleController::class, 'delete']);
Route::put('/users/{userId}/vehicles/{vehicleId}/default', [VehicleController::class, 'setDefault']);

Route::get('/vehicle-labels', [VehicleController::class, 'labels']);

//calcular ruta (se guarda en HISTORY)
Route::post('/routes', [RouteController::class, 'calculate']);

//preview (NO SE GUARDA)
Route::post('/routes/preview', [RouteController::class, 'preview']);

//ruta detallada == entrada al historial
Route::get('/routes/{historyId}', [RouteController::class, 'detail']);

//historial
Route::get('/users/{userId}/routes/history', [RouteController::class, 'history']);
Route::delete('/users/{userId}/routes/history/{historyId}', [RouteController::class, 'deleteHistory']);

//favoritos
Route::get('/users/{userId}/routes/favorites', [RouteController::class, 'favorites']);
Route::post('/users/{userId}/routes/favorites', [RouteController::class, 'addFavorite']); // body: historyId
Route::delete('/users/{userId}/routes/favorites/{favoriteId}', [RouteController::class, 'removeFavorite']);

//sesiones de invitado
Route::post('/guest/sessions', [GuestSessionController::class, 'create']);
Route::get('/guest/sessions/{sessionId}/quota', [GuestSessionController::class, 'quota']);
Route::post('/guest/sessions/{sessionId}/routes', [GuestSessionController::class, 'calculateRoute']);

//incidentes
Route::get('/incidents', [IncidentController::class, 'index']);
Route::get('/incidents/{incidentId}', [IncidentController::class, 'show']);

//predicciones
Route::get('/predictions', [PredictionController::class, 'index']);
Route::get('/predictions/latest', [PredictionController::class, 'latest']);
Route::get('/predictions/districts/{district}', [PredictionController::class, 'byDistrict']);

//todo lo de la mascota
Route::get('/users/{userId}/pet', [PetController::class, 'show']);
Route::put('/users/{userId}/pet', [PetController::class, 'update']);

Route::get('/items', [ItemController::class, 'catalog']);
Route::get('/users/{userId}/inventory', [ItemController::class, 'inventory']);
Route::get('/users/{userId}/badges', [ItemController::class, 'badges']);
Route::put('/users/{userId}/equipment', [ItemController::class, 'updateEquipment']);

//admin
Route::prefix('admin')->group(function () {

    Route::get('/dashboard', [AdminController::class, 'dashboard']);

    Route::get('/users', [AdminController::class, 'users']);
    Route::get('/users/{userId}', [AdminController::class, 'userDetail']);
    Route::put('/users/{userId}', [AdminController::class, 'updateUser']);
    Route::delete('/users/{userId}', [AdminController::class, 'deactivateUser']);

    Route::post('/incidents', [AdminController::class, 'createIncident']);
    Route::put('/incidents/{incidentId}', [AdminController::class, 'updateIncident']);
    Route::delete('/incidents/{incidentId}', [AdminController::class, 'deleteIncident']);

    Route::post('/predictions/run', [AdminController::class, 'runPredictions']);
});