<?php

namespace App\Providers;

use App\Models\Chat;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('chat', function (Request $request) {
            return Limit::perMinute(20)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return back()->withErrors([
                        'body' => 'Слишком часто. Подождите минуту.',
                    ]);
                });
        });

        // Подкладываем список чатов во все страницы, использующие layouts.chat
        View::composer('layouts.chat', function ($view) {
            if (auth()->check()) {
                $view->with(
                    'chats',
                    Chat::withCount('messages')->orderBy('name')->get()
                );
            } else {
                $view->with('chats', collect());
            }
        });
    }
}