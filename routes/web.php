<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainController;
use App\Http\Controllers\SukiController;
use App\Http\Controllers\ShikuController;
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
Route::post('/api/getMonitorInfo', [SukiController::class, 'getMonitorInfo']);
Route::post('/api/suki', [SukiController::class, 'suki']);
Route::post('/api/getList', [SukiController::class, 'getList']);
Route::post('/api/getGoodsList', [SukiController::class, 'getGoodsList']);

Route::get('/api/abc', [MainController::class, 'abc']);
Route::post('/api/getViolationList', [MainController::class, 'getViolationList']);
Route::post('/api/updateThrowMgr', [MainController::class, 'updateThrowMgr']);
Route::post('/api/updateBlackMgr', [MainController::class, 'updateBlackMgr']);

Route::post('/api/getLatestPostList', [MainController::class, 'getLatestPostList']);
Route::post('/api/getPostList', [MainController::class, 'getPostList']);
Route::post('/api/insertPost', [MainController::class, 'insertPost']);
Route::post('/api/deletePost', [MainController::class, 'deletePost']);

Route::post('/api/getListRowData', [MainController::class, 'getListRowData']);

Route::post('/api/addStamp', [MainController::class, 'addStamp']);

Route::post('/api/shareAction', [MainController::class, 'shareAction']);

Route::post('/api/reportPost', [MainController::class, 'reportPost']);
Route::get('/api/initAction', [MainController::class, 'initAction']);

Route::get('/api/getAbs', [MainController::class, 'getAbs']);

Route::get('/api/test', [MainController::class, 'test']);

Route::get('/{code}', [MainController::class, 'access']);

//市区町村用API
Route::post('/api/shiku_init', [ShikuController::class, 'init']);
Route::post('/api/shiku_regist', [ShikuController::class, 'regist']);
Route::post('/api/shiku_clear', [ShikuController::class, 'clear']);

Route::get('/{any}', function () {
    return view('spa.app')->with(['title' => "なにもなし", 'card' => "base.jpg"]);
})->where('any', '.*');