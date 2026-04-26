<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\GuestSessionController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\PetController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\PoiController;
use App\Http\Controllers\AuthController;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Autenticación pública
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Sesiones de invitado
Route::post('/guest/sessions', [GuestSessionController::class, 'create']);
Route::get('/guest/sessions/{sessionId}/quota', [GuestSessionController::class, 'quota']);
Route::post('/guest/sessions/{sessionId}/routes', [GuestSessionController::class, 'calculateRoute']);

// Endpoints públicos
Route::get('/vehicle-labels', [VehicleController::class, 'labels']);

Route::get('/incidents', [IncidentController::class, 'index']);
Route::get('/incidents/{incidentId}', [IncidentController::class, 'show']);

Route::get('/predictions', [PredictionController::class, 'index']);
Route::get('/predictions/latest', [PredictionController::class, 'latest']);
Route::get('/predictions/districts/{district}', [PredictionController::class, 'byDistrict']);

Route::get('/locations/search', [RouteController::class, 'searchLocation']);
Route::post('/routes/preview', [RouteController::class, 'preview']);

// POIs públicos preparados para futuras mejoras
Route::get('/pois/categories', [PoiController::class, 'categories']);
Route::post('/pois/search', [PoiController::class, 'search']);
Route::post('/pois/stats', [PoiController::class, 'stats']);

// Usuario registrado
Route::middleware('jwt.auth')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'deactivate']);
    Route::get('/users/{user}/stats', [UserController::class, 'stats']);

    Route::get('/users/{user}/profile', [UserProfileController::class, 'show']);
    Route::put('/users/{user}/locations/home', [UserProfileController::class, 'setHome']);
    Route::put('/users/{user}/locations/work', [UserProfileController::class, 'setWork']);
    Route::delete('/users/{user}/locations/home', [UserProfileController::class, 'clearHome']);
    Route::delete('/users/{user}/locations/work', [UserProfileController::class, 'clearWork']);

    Route::get('/users/{user}/vehicles', [VehicleController::class, 'index']);
    Route::post('/users/{user}/vehicles', [VehicleController::class, 'store']);
    Route::get('/users/{user}/vehicles/{vehicleId}', [VehicleController::class, 'show']);
    Route::put('/users/{user}/vehicles/{vehicleId}', [VehicleController::class, 'update']);
    Route::delete('/users/{user}/vehicles/{vehicleId}', [VehicleController::class, 'delete']);
    Route::put('/users/{user}/vehicles/{vehicleId}/default', [VehicleController::class, 'setDefault']);

    Route::post('/routes', [RouteController::class, 'calculate']);
    Route::get('/routes/{historyId}', [RouteController::class, 'detail']);

    Route::get('/users/{user}/routes/history', [RouteController::class, 'history']);
    Route::delete('/users/{user}/routes/history/{historyId}', [RouteController::class, 'deleteHistory']);

    Route::get('/users/{user}/routes/favorites', [RouteController::class, 'favorites']);
    Route::post('/users/{user}/routes/favorites', [RouteController::class, 'addFavorite']);
    Route::delete('/users/{user}/routes/favorites/{favoriteId}', [RouteController::class, 'removeFavorite']);

    Route::get('/users/{user}/pet', [PetController::class, 'show']);
    Route::put('/users/{user}/pet', [PetController::class, 'update']);

    Route::get('/items', [ItemController::class, 'catalog']);
    Route::get('/users/{user}/inventory', [ItemController::class, 'inventory']);
    Route::get('/users/{user}/badges', [ItemController::class, 'badges']);
    Route::put('/users/{user}/equipment', [ItemController::class, 'updateEquipment']);
});

// Administrador
Route::prefix('admin')
    ->middleware(['jwt.auth', 'admin'])
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/users/{user}', [AdminController::class, 'userDetail']);
        Route::put('/users/{user}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{user}', [AdminController::class, 'deactivateUser']);

        Route::post('/incidents', [AdminController::class, 'createIncident']);
        Route::put('/incidents/{incident}', [AdminController::class, 'updateIncident']);
        Route::delete('/incidents/{incident}', [AdminController::class, 'deleteIncident']);

        Route::post('/predictions/run', [AdminController::class, 'runPredictions']);
    });