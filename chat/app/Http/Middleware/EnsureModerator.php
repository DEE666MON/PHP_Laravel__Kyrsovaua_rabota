<?php

// namespace App\Http\Middleware;

// use Closure;
// use Illuminate\Http\Request;

// class EnsureModerator
// {
//     public function handle(Request $request, Closure $next)
//     {
//         if (! $request->user() || ! $request->user()->isModerator()) {
//             abort(403, 'Доступ только для модераторов');
//         }
//         return $next($request);
//     }
// }