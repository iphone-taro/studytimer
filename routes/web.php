<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use App\Consts\Consts;
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
Route::post('/api/reportStudy', [MainController::class, 'reportStudy']);

Route::get('/{code}', [MainController::class, 'test']);

Route::get('/{any}', function () {
    return view('spa.app')->with(['title' => "なにもなし", 'card' => "card_common"]);
})->where('any', '.*');