<?php

use App\Http\Controllers\AccountDeletionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => redirect('/painel/login'));

Route::get('/conta/excluir',    [AccountDeletionController::class, 'show'])->name('account.show');
Route::delete('/conta/excluir', [AccountDeletionController::class, 'destroy'])->name('account.delete');
Route::get('/conta/excluida',   [AccountDeletionController::class, 'deleted'])->name('account.deleted');

require __DIR__.'/auth.php';
