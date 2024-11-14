<?php

use App\Http\Controllers\FavoriController;
use App\Http\Controllers\ScheduledTransferController;
use App\Http\Controllers\TransactionHistoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\TransfertController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Routes publiques
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
// Route de vérification du code initial (première étape)
Route::post('/verify-code', [AuthController::class, 'verifyInitialCode'])->name('verify.code');


// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    // Profil utilisateur
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/users/update-profile', [UserController::class, 'updateProfile']);
    Route::post('update-secret-code', [AuthController::class, 'updateSecretCode']);
    Route::post('/set-secret-code', [AuthController::class, 'setCustomSecretCode']);


    // Transferts
    Route::post('/transfer', [TransfertController::class, 'transfer']);
    Route::get('/history', [TransactionHistoryController::class, 'index']);
    // Route::post('/transfer/cancel', [TransfertController::class, 'cancelTransfer']);
    Route::post('/transfer/schedule', [ScheduledTransferController::class, 'schedule']);
    Route::post('/transfer/multiple', [TransfertController::class, 'multipleTransfer']);
    Route::post('/transfer/{id}/cancel', [TransfertController::class, 'cancelTransfer']);


    //Transactions
    Route::prefix('transactions')->group(function () {
        Route::get('/history', [TransactionHistoryController::class, 'index']);
        Route::get('/balance', [TransactionController::class, 'checkBalance']);
        Route::post('/verify-qr', [TransactionController::class, 'verifyQrCode']);
    });

    // Favoris
    Route::prefix('favoris')->group(function () {
        Route::post('/add', [FavoriController::class, 'add']);
        Route::get('/list', [FavoriController::class, 'list']);
        Route::delete('/{id}', [FavoriController::class, 'delete']);
        Route::post('/check', [FavoriController::class, 'checkFavori']);
    });

    // Contacts et favoris
    Route::prefix('contacts')->group(function () {
        Route::get('/list', [ContactController::class, 'list']);
        Route::post('/toggle-favori', [ContactController::class, 'toggleFavori']);
    });

    // Paiement Marchand
    Route::post('/merchant/pay', [MerchantController::class, 'processPayment']);
    Route::get('/merchant/stats', [MerchantController::class, 'getStats']);
    Route::get('/merchant/qr', [MerchantController::class, 'generateQR']);
    Route::post('/payment/qr', [MerchantController::class, 'processQRPayment']);

    // Gestion du compte
    Route::prefix('account')->group(function () {
        Route::put('/toggle-card', [UserController::class, 'toggleCard']);
        Route::post('/regenerate-qr', [UserController::class, 'regenerateQrCode']);
        Route::put('/update-pin', [UserController::class, 'updatePin']);
    });

    // Routes Admin
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        //Route::get('/transactions/all', [TransactionController::class, 'allTransactions']);
        Route::post('/users/block', [UserController::class, 'blockUser']);
        Route::post('/users/unblock', [UserController::class, 'unblockUser']);
    });
});

// Route de fallback pour les URL non trouvées
Route::fallback(function () {
    return response()->json([
        'message' => 'Route non trouvée. Veuillez vérifier l\'URL et la méthode HTTP.'
    ], 404);
});
