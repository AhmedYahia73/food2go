<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KitchenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ((auth()->user()->type == 'kitchen' || auth()->user()->type == 'brista') && 
        auth()->user()->status == 1) {
            return $next($request);
        }
        return response()->json([
            'faild' => 'You must loggin'
        ], 400);
        return $next($request);
    }
}
