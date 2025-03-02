<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FootballClubController;
use App\Http\Controllers\FootballLeagueController;
use App\Http\Controllers\NotificationController;
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

Route::get('/leagues', [FootballLeagueController::class, 'index']);
Route::get('/leagues/{id}', [FootballLeagueController::class, 'show']);
Route::post('/leagues', [FootballLeagueController::class, 'store']);
Route::patch('/leagues/{id}', [FootballLeagueController::class, 'update']);
Route::delete('/leagues/{id}', [FootballLeagueController::class, 'destroy']);

Route::get('/clubs', [FootballClubController::class, 'index']);
Route::get('/clubs/{id}', [FootballClubController::class, 'show']);
Route::post('/clubs', [FootballClubController::class, 'store']);
Route::patch('/clubs/{id}', [FootballClubController::class, 'update']);
Route::delete('/clubs/{id}', [FootballClubController::class, 'destroy']);

Route::resource('/notifications', NotificationController::class);