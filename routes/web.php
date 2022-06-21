<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\DashboardController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::any('/{any}', function () {
    $data['title'] = 'Login Page';
    $data['template'] = 'admin';
    return view('index',['data' => $data]);
});

Route::any('/', function () {
    $data['title'] = 'Login Page';
    $data['template'] = 'admin';
    return view('index',['data' => $data]);
});

Route::group(['middleware' => ['revalidate']], function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

