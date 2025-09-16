<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WaiterMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ((auth()->user()->role == 'waiter' && auth()->user()->status == 1) ||
        (auth()->user()->role == 'captain_order' && auth()->user()->status == 1 &&
        auth()->user()->waiter == 1)) {
            return $next($request);
        }
        return response()->json([
            'errors' => 'You must loggin'
        ], 400); 
    }
}
