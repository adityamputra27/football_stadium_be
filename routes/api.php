<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LeagueController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/me', [AuthController::class, 'me']);
Route::post('/refresh', [AuthController::class, 'refresh']);

Route::get('/leagues', [LeagueController::class, 'index']);
Route::get('/leagues/{id}', [LeagueController::class, 'show']);
Route::post('/leagues', [LeagueController::class, 'store']);
Route::patch('/leagues/{id}', [LeagueController::class, 'update']);
Route::delete('/leagues/{id}', [LeagueController::class, 'destroy']);