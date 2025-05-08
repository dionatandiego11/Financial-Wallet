<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController; 

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () { // CHAVE ABERTA 1
    // Dashboard
    Route::get('/dashboard', [WalletController::class, 'dashboard'])->name('dashboard');

    // Deposit
    Route::get('/wallet/deposit', [WalletController::class, 'showDepositForm'])->name('wallet.deposit.form');
    Route::post('/wallet/deposit', [WalletController::class, 'processDeposit'])->name('wallet.deposit.process');

    // Transfer
    Route::get('/wallet/transfer', [WalletController::class, 'showTransferForm'])->name('wallet.transfer.form');
    Route::post('/wallet/transfer', [WalletController::class, 'processTransfer'])->name('wallet.transfer.process');

    // Reversal
    Route::post('/transactions/{transaction}/reverse', [TransactionController::class, 'reverse'])->name('transactions.reverse');

    // Profile routes
    // Se você instalou o Breeze, estas rotas de profile geralmente já estão definidas dentro do auth.php.
    // Se não estiverem, pode manter. Caso contrário, é redundante.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rotas Administrativas
    // Aplicando o middleware 'admin' aqui
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () { // CHAVE ABERTA 2
        Route::get('/users', [AdminController::class, 'listUsers'])->name('users.index');
        // Outras rotas de admin...
    }); // <<--- CHAVE FECHADA 2 (para o grupo 'admin')

}); // <<--- ESTA É A CHAVE QUE ESTAVA FALTANDO (CHAVE FECHADA 1 para o grupo 'auth', 'verified')

require __DIR__.'/auth.php';