<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = Http::get('https://clientbcknd.food2go.online/admin/v1/my_domain_package')->body();
        $response = json_decode($response);
        $subscription = collect($response?->user_subscription) ?? collect([]); 
        $subscription = $subscription->where('back_link', url(''))
        ->where('is_active', true)
        ->first();  
        if (empty($subscription)) {
            return response()->json([
                'errors' => 'Application is stoping now'
            ], 400);
        } 
        if (auth()->user()->role == 'customer' && auth()->user()->deleted_at == 0 ) {
            return $next($request);
        }
        return response()->json([
            'faild' => 'You must loggin as customer'
        ], 400);
    }
}
