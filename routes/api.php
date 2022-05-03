<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\InstrumentsController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('login', [ApiController::class, 'authenticate']);
Route::post('register', [ApiController::class, 'register']);
Route::post('login_admin', [ApiController::class, 'authenticate_admin']);
Route::get('logout_admin', [ApiController::class, 'logout_admin']);


Route::group(['middleware' => ['jwt.verify']], function() {
    Route::get('logout', [ApiController::class, 'logout']);
    Route::get('get_instruments_list', [InstrumentsController::class, 'get_instruments_list']);
    Route::get('get_favourites_list', [InstrumentsController::class, 'get_favourites_list']);
    Route::post('save_favourites', [InstrumentsController::class, 'save_favourites']);
    Route::post('buy_sell', [InstrumentsController::class, 'buy_sell']);
    Route::get('portfolio', [InstrumentsController::class, 'portfolio']);
    Route::get('trades', [InstrumentsController::class, 'trades']);
    Route::get('funds', [InstrumentsController::class, 'funds']);
    Route::get('profile', [InstrumentsController::class, 'trading_profile']);
    Route::post('change_password',[ApiController::class, 'change_password']);
    Route::delete('remove_favourites',[InstrumentsController::class, 'remove_favourites']);
});