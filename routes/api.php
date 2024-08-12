<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\CounterController;
use App\Http\Controllers\SubscribeController;
use App\Http\Controllers\TimezoneController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);
Route::post('/convert-timestamps', [TimezoneController::class, 'convertTimestamps']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/competitions', [CompetitionController::class, 'store']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/getusers', [UserController::class, 'index']);
    Route::get('/getcompetitions', [CompetitionController::class, 'getRecentCompetitions']);
    
    Route::get('/getcompetitiondetails/{id}', [CompetitionController::class, 'getCompetitionDetails']);
    Route::post('/addsubscription', [SubscribeController::class, 'addSubscription']);
    Route::get('/getusercompetitions/{userId}', [CompetitionController::class, 'getUserCompetitions']);
    Route::post('/savecounterestkhfar', [CounterController::class, 'saveEstkhfar']);
    Route::get('/getcounterestkhfar', [CounterController::class, 'getCounterValue']);
    Route::get('/getnatijatiestkhfar', [CounterController::class, 'getNatijatiestkhfar']);
    Route::get('getcompetitionestkhfar/{competitionId}/results', [CompetitionController::class, 'getResultsestkhfar']);
    
    Route::post('/savecountersalawat', [CounterController::class, 'savesalawat']);
    Route::get('/getcountersalawat', [CounterController::class, 'getCounterSalawat']);
    Route::get('getnataeejsalawat/{competitionId}', [CompetitionController::class, 'getnataeejsalawat']);
    Route::get('/getuser/{id}', [UserController::class, 'getUser']);
    Route::post('/updateuser/{id}', [UserController::class, 'updateUser']);
    Route::post('/save_timezone', [UserController::class, 'saveTimezone']);


});
