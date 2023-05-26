<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GameFriendController;
use App\Http\Controllers\ManagementController;
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
Route::get('/api/test', [GameFriendController::class, 'test']);

Route::post('/api/getGameList', [GameFriendController::class, 'getGameList']);
Route::post('/api/getPostList', [GameFriendController::class, 'getPostList']);
Route::post('/api/insertPost', [GameFriendController::class, 'insertPost']);
Route::post('/api/deletePost', [GameFriendController::class, 'deletePost']);
Route::post('/api/reportPost', [GameFriendController::class, 'reportPost']);

Route::post('/api/getManagementData', [ManagementController::class, 'getManagementData']);
Route::post('/api/updateAddGame', [ManagementController::class, 'updateAddGame']);
Route::post('/api/updateReport', [ManagementController::class, 'updateReport']);
Route::post('/api/updateNew', [ManagementController::class, 'updateNew']);

Route::get('/post/{gameId}', [GameFriendController::class, 'viewPost']);

Route::get('/{any}', function () {
    return view('spa.app')->with(['title' => Consts::BASE_TITLE, 'description' => Consts::BASE_DESCRIPTION, 'seo' => Consts::BASE_SEO]);
})->where('any', '.*');
// Route::get('/{any}', [QuizController::class, 'baseAction'])->where('any', '.*');

// Route::get('/{any}', function () {
//     dd(url["fragment"];);
// })->where('any', '.*');
