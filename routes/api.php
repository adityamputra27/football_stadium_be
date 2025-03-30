<?php

use App\Http\Controllers\APIController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FootballClubController;
use App\Http\Controllers\FootballLeagueController;
use App\Http\Controllers\FootballStadiumController;
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

Route::get('/stadiums', [FootballStadiumController::class, 'index']);
Route::get('/stadiums/{id}', [FootballStadiumController::class, 'show']);
Route::post('/stadiums', [FootballStadiumController::class, 'store']);
Route::patch('/stadiums/{id}', [FootballStadiumController::class, 'update']);
Route::delete('/stadiums/{id}', [FootballStadiumController::class, 'destroy']);

Route::group(['prefix' => 'stadiums'], function () {
    Route::get('/', [FootballStadiumController::class, 'index']);
    Route::get('{id}', [FootballStadiumController::class, 'show']);
    Route::post('/', [FootballStadiumController::class, 'store']);
    Route::patch('{id}', [FootballStadiumController::class, 'update']);
    Route::delete('{id}', [FootballStadiumController::class, 'destroy']);

    Route::get('{stadiumId}/files', [FootballStadiumController::class, 'files']);
    Route::post('{stadiumId}/files/upload', [FootballStadiumController::class, 'uploadFile']);
    Route::delete('{stadiumId}/files/{fileId}/delete', [FootballStadiumController::class, 'deleteFile']);
});

Route::resource('/notifications', NotificationController::class);

Route::group(['prefix' => 'mobile', 'middleware' => 'check-headers'], function () {
    Route::get('/notification-counter/{userId}/user', [APIController::class, 'notificationCounter']);
    Route::get('/notification-list/{userId}/user', [APIController::class, 'notificationUser']);
    Route::post('/notification-list/{userId}/mark/{notificationId}', [APIController::class, 'markNotificationUser']);
    Route::post('/register-device', [APIController::class, 'createFirstNotificationUser']);
    Route::get('/main-screen-user', [APIController::class, 'mainScreenUser']);
    Route::get('/all-leagues', [APIController::class, 'allLeagues']);
    Route::get('/all-clubs/{leagueId}', [APIController::class, 'allClubsPerLeague']);
    Route::get('/stadium/{leagueId}/league/{clubId}/club', [APIController::class, 'clubStadium']);

    Route::post('/reset-user', [APIController::class, 'resetUser']);
});