<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CustomerMiddleware;
use App\Http\Middleware\DeliveryMiddleware;
use App\Http\Middleware\CaptainMiddleware;
use App\Http\Middleware\CashierMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function(){
            Route::middleware('api')
            ->prefix('admin')
            ->name('admin.')
            ->group(base_path('routes/admin.php'));
            Route::middleware('api')
            ->prefix('customer')
            ->name('customer.')
            ->group(base_path('routes/customer.php'));
            Route::middleware('api')
            ->prefix('delivery')
            ->name('delivery.')
            ->group(base_path('routes/delivery.php'));
            Route::middleware('api')
            ->prefix('captain')
            ->name('captain.')
            ->group(base_path('routes/captain.php'));
            Route::middleware('api')
            ->prefix('cashier')
            ->name('cashier.')
            ->group(base_path('routes/cashier.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'IsAdmin' => AdminMiddleware::class,
            'IsCustomer' => CustomerMiddleware::class,
            'IsDelivery' => DeliveryMiddleware::class,
            'IsCaptain' => CaptainMiddleware::class,
            'IsCashier' => CashierMiddleware::class,
        ]);
         $middleware->redirectGuestsTo(function (Request $request) {
            if (!$request->is('api/*')) {
                return response()->json(['errors' => 'you must login', 400]);
            } 
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 401);
            }
        });
    })->create();
