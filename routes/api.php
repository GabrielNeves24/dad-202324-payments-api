<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DefaultCategoryController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\VCardController;
use App\Http\Controllers\PaymentController;

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

Route::resource('users', UserController::class);
Route::resource('default_categories', DefaultCategoryController::class);

Route::get('vcards/{vcard}/categories', [CategoryController::class, 'index']);
Route::get('vcards/{vcard}/categories/{id}', [CategoryController::class, 'show']);
//Route::resource('vcards.categories', CategoryController::class);

Route::get('vcards', [VCardController::class, 'index']);
Route::resource('vcards', VCardController::class);


Route::post('credit-transaction', [PaymentController::class, 'createCreditTransaction']);
Route::post('debit-transaction', [PaymentController::class, 'createDebitTransaction']);


