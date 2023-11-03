<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DefaultCategoryController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\VCardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AuthController;
use App\Models\Transaction;
use App\Models\VCard;
use App\Models\User;
use App\Models\Category;
use App\Models\DefaultCategory;
use App\Models\Payment;


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
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Route::middleware('auth:api')->group(function () {
//     Route::get('/users', function (Request $request) {
//         return $request->user();
//     });
// });

Route::middleware('auth:api')->group(function () {
    // Routes for regular users protected by the 'api' guard
    Route::get('users', [UserController::class, 'getAllUsers']);
    Route::get('vcards', [VCardController::class, 'getAllVCards']);
});
Route::get('vcards/{phone_number}/foto', [VCardController::class, 'getVCardImage']);
Route::middleware('auth:vcard-api')->group(function () {
    // Routes for VCard users protected by the 'vcard-api' guard
    //Route::get('vcards', [VCardController::class, 'getAllVCards']);
    Route::get('vcards/{phone_number}', [VCardController::class, 'getVCardsbyphoneNumber']);
    
});

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Route::get('users', [UserController::class, 'getAllUsers']);
// Route::get('vcards', [VCardController::class, 'getAllVCards']);
// Route::get('vcards/{phone_number}', [VCardController::class, 'getVCardsbyphoneNumber']);
// Route::get('vcards/{phone_number}/foto', [VCardController::class, 'getVCardImage']);

//Route::get('vcards/{vcard}/categories', [CategoryController::class, 'index']);
//Route::get('vcards/{vcard}/categories/{id}', [CategoryController::class, 'show']);
//Route::get('vcards/{vcard}/transactions', [TransactionController::class, 'getAllTrasacionsByNumber']);
//Route::resource('vcards.categories', CategoryController::class);

//Route::get('vcards/{phone_number}', [VCardController::class, 'show']);
//Route::get('vcards/{phone_number}/foto', [VCardController::class, 'getVCardImage']);
//Route::resource('vcards', VCardController::class);


//Route::post('credit-transaction', [PaymentController::class, 'createCreditTransaction']);
//Route::post('debit-transaction', [PaymentController::class, 'createDebitTransaction']);


//http://dad-202324-payments-api.test/api/vcards/900000015/categories/900000015