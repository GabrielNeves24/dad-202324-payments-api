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
Route::middleware('auth:api')->group(function () {
    Route::get('users/me', [UserController::class, 'show_me']);
    Route::post('logout' , [AuthController::class, 'logout']);
    Route::get('vcards/{phone_number}', [VCardController::class, 'getVCardsbyphoneNumber']);

    //categorias
    Route::get('vcards/categories', [CategoryController::class, 'getAllCategories']);
    Route::get('vcards/{phone_number}/categories', [CategoryController::class, 'getCategoriesbyphoneNumber']);
    Route::put('vcards/{phone_number}/categories', [CategoryController::class, 'updateCategoryById']);
    Route::get('vcards/{phone_number}/categories/debit', [VCardController::class, 'getCategoriesbyphoneNumberCredit']);
    Route::get('vcards/{phone_number}/categories/credit', [VCardController::class, 'getCategoriesbyphoneNumberDebit']);
    Route::get('vcards/{phone_number}/categories/{id}', [VCardController::class, 'getCategorybyphoneNumberAndId']);
    Route::delete('vcards/{phone_number}/categories/{id}', [VCardController::class, 'deleteCategorybyphoneNumber']);
    Route::post('vcards/{phone_number}/categories', [CategoryController::class, 'addCategoryByPhoneNumber']);
    //fim categorias

    Route::get('categories/defaults', [DefaultCategoryController::class, 'categoriesDefaultAll']);
    Route::get('categories/all', [CategoryController::class, 'getAllCategories']);
    Route::post('categories/defaults', [DefaultCategoryController::class, 'createCategoriesDefault']);
    Route::get('categories/defaults/{id}', [DefaultCategoryController::class, 'categoriesDefaultByID']);
    Route::put('categories/defaults/{id}', [DefaultCategoryController::class, 'updateCategoriesDefault']);
    Route::delete('categories/defaults/{id}', [DefaultCategoryController::class, 'deleteCategoriesDefault']);
    
    //transactions
    Route::get('vcards/{phone_number}/transactions/last30days', [VCardController::class, 'getLast30DaysTransactions']);
    Route::post('transactions/debit', [TransactionController::class, 'store']);
    Route::post('transactions/credit', [TransactionController::class, 'storeCredit']);
    Route::get('transactions/{id}', [TransactionController::class, 'GetTransactionById']);
    Route::put('transactions', [TransactionController::class, 'updateTransactionById']);
    Route::get('vcards/{phone_number}/transactions/all', [VCardController::class, 'getTransactionsbyphoneNumber']);
    Route::get('transactions/info/totalTransactionByTypeDebit', [TransactionController::class, 'getTotalTransactionByPaymentTypeDebit']);
    Route::get('transactions/info/totalTransactionByTypeCredit', [TransactionController::class, 'getTotalTransactionByPaymentTypeCredit']);
    //dim transactions

    // Routes for regular users protected by the 'api' guard
    Route::get('users', [UserController::class, 'getAllUsers']);
    Route::post('verify-password', [UserController::class, 'verifyPassword']);
    Route::post('verify-pin', [UserController::class, 'verifyPin']);
    Route::post('users', [UserController::class, 'create']);
    //get user logon
    Route::get('users/{id}', [UserController::class, 'getUser']);
    Route::get('vcards', [VCardController::class, 'getAllVCards']);
    Route::get('transactions', [TransactionController::class, 'getAllTransactions']);
    Route::put('users/{id}', [UserController::class, 'update']);


    //update info on Vcard
    Route::post('vcards/{phone_number}', [VCardController::class, 'update']);
    Route::put('users/perfil/{id}', [UserController::class, 'update']);
    Route::delete('users/{id}', [UserController::class, 'destroy']);


    //update info on vcards about max_debit and block
    Route::put('vcards/user/{phone_number}', [VCardController::class, 'updateVCardUser']);
    //delete vcard
    Route::delete('vcards/{phone_number}', [VCardController::class, 'deleteVCard']);

    //graficos
    Route::get('transactions/info/transacionsByMonth', [VCardController::class, 'getTransactionsByMonth']);
    Route::get('transactions/info/transacionsByDay', [VCardController::class, 'getTransactionsByDay']);
    Route::get('vcards/{phone_number}/category-spending', [VCardController::class, 'getCategorySpendingByVCard']);


});
Route::get('vcards/{phone_number}/foto', [VCardController::class, 'getVCardImage']);
