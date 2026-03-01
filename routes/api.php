<?php
use App\Http\Controllers\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('students/getAll', [StudentController::class, 'getAll']);
Route::get('students/{id}', [StudentController::class, 'getById']);
Route::post('students/create', [StudentController::class, 'create']);
Route::put('students/update/{id}', [StudentController::class, 'update']);
Route::delete('students/delete/{id}', [StudentController::class, 'delete']);
