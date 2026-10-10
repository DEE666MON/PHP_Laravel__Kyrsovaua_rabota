<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ModeratorApplicationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('chat.index'));

// Редирект для Breeze (чтобы не было ошибки Route [dashboard] not defined)
Route::middleware('auth')
    ->get('/dashboard', fn () => redirect()->route('chat.index'))
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // Чаты
    Route::get('/chats',         [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chats/create',  [ChatController::class, 'create'])->name('chat.create');
    Route::post('/chats',        [ChatController::class, 'storeChat'])->name('chat.storeChat');
    Route::get('/chats/{chat}',  [ChatController::class, 'show'])->name('chat.show');

    // Сообщения
    Route::post('/chats/{chat}/messages', [ChatController::class, 'storeMessage'])
        ->middleware('throttle:chat')->name('chat.message.store');
    Route::get('/chats/{chat}/messages', [ChatController::class, 'fetch'])->name('chat.messages');
    Route::get('/chats/{chat}/older',    [ChatController::class, 'older'])->name('chat.older');

    // Модерация
    Route::delete('/messages/{message}', [ChatController::class, 'destroyMessage'])
        ->name('messages.destroy');
    Route::delete('/users/{user}', [ChatController::class, 'destroyUser'])
        ->name('users.destroy');

    // Заявки в модераторы
    Route::get('/moderator/apply',  [ModeratorApplicationController::class, 'create'])
        ->name('moderator.apply');
    Route::post('/moderator/apply', [ModeratorApplicationController::class, 'store'])
        ->name('moderator.apply.store');
    Route::get('/moderator/applications', [ModeratorApplicationController::class, 'index'])
        ->name('moderator.applications');
    Route::post('/moderator/applications/{application}/vote',
        [ModeratorApplicationController::class, 'vote'])->name('moderator.vote');

    // Админ
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/actions', [AdminController::class, 'actions'])->name('admin.actions');
        Route::post('/actions/{action}/undo', [AdminController::class, 'undo'])
            ->name('admin.undo');
        Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
        Route::post('/users/{user}/role', [AdminController::class, 'updateRole'])
            ->name('admin.users.role');
    });
});

require __DIR__.'/auth.php';