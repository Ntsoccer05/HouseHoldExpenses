<?php

use App\Http\Controllers\Auth\ForgetPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\IncomeCategoryController;
use App\Http\Controllers\TransactionController;
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

// ログイン済みのみ
// verifiedでメール認証済み
Route::group(['middleware'=>['auth:sanctum']],function(){
    Route::get('/IncomeCategory',[IncomeCategoryController::class, 'index']);
    Route::get('/ExpenseCategory',[ExpenseCategoryController::class, 'index']);
    Route::post('/updateExpenseCategory',[ExpenseCategoryController::class, 'update']);
    Route::post('/updateIncomeCategory',[IncomeCategoryController::class, 'update']);
    Route::post('/deleteExpenseCategory',[ExpenseCategoryController::class, 'delete']);
    Route::post('/deleteIncomeCategory',[IncomeCategoryController::class, 'delete']);
    Route::post('/addExpenseCategory',[ExpenseCategoryController::class, 'add']);
    Route::post('/addIncomeCategory',[IncomeCategoryController::class, 'add']);
    Route::post('/sortExpenseCategory',[ExpenseCategoryController::class, 'sort']);
    Route::post('/sortIncomeCategory',[IncomeCategoryController::class, 'sort']);
    Route::post('/addTransaction',[TransactionController::class, 'create']);
    Route::get('/getTransactions',[TransactionController::class, 'index']);
    Route::post('/updateTransaction',[TransactionController::class, 'update']);
    Route::post('/deleteTransaction',[TransactionController::class, 'delete']);
    Route::post('/logout',[LoginController::class, 'logout']);
});

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [LoginController::class, 'login'])->name('login');;
Route::post('/register', [RegisterController::class, 'temporaryRegister']);

// メール認証
Route::get('/email/verify/{id}/{hash}', VerificationController::class)
->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
Route::post('/email/verification-notification', [VerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');

// ソーシャルログイン
Route::prefix('login')->group(function () {
    Route::get('/{provider}', [LoginController::class, 'getProviderOAuthURL']);
    Route::post('/{provider}/callback', [LoginController::class, 'handleProviderCallback']);
});

// Route::prefix('register')->group(function () {
//     Route::post('/{provider}', [RegisterController::class, 'registerProviderUser']);
// });

//パスワードリセット
Route::post('/password/forget', [ForgetPasswordController::class, 'sendemail']);
Route::post('/password/reset', [ForgetPasswordController::class, 'passwordreset'])->name('password.reset');