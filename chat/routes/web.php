<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('chat.index'));

// Редирект для Breeze (чтобы не было ошибки Route [dashboard] not defined)
Route::middleware('auth')->get('/dashboard', fn () => redirect()->route('chat.index'))
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');

    Route::post('/chat', [ChatController::class, 'store'])
        ->middleware('throttle:chat')
        ->name('chat.store');

    Route::get('/chat/messages', [ChatController::class, 'fetch'])->name('chat.fetch');
    Route::get('/chat/older',    [ChatController::class, 'older'])->name('chat.older');

    // Модерация (доступ проверяется Policy в контроллере)
    Route::delete('/messages/{message}', [ChatController::class, 'destroyMessage'])
        ->name('messages.destroy');
    Route::delete('/users/{user}', [ChatController::class, 'destroyUser'])
        ->name('users.destroy');
});

require __DIR__.'/auth.php';