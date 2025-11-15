<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use Illuminate\Auth\AuthenticationException;

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CustomerMiddleware;
use App\Http\Middleware\DeliveryMiddleware;
use App\Http\Middleware\CaptainMiddleware;
use App\Http\Middleware\BranchMiddleware;
use App\Http\Middleware\CashierMiddleware;
use App\Http\Middleware\KitchenMiddleware;
use App\Http\Middleware\WaiterMiddleware;
use App\Http\Middleware\StorageMiddleware;
use App\Http\Middleware\AdmiOrBranchMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {

            Route::middleware('api')
            ->prefix('admin')
            ->name('admin.')
            ->group(base_path('routes/admin.php'));

            Route::middleware('api')
            ->prefix('kitchen')
            ->name('kitchen.')
            ->group(base_path('routes/kitchen.php'));

            Route::middleware('api')
            ->prefix('branch')
            ->name('branch.')
            ->group(base_path('routes/branch.php'));

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

            Route::middleware('api')
            ->prefix('client')
            ->name('client.')
            ->group(base_path('routes/client.php'));

            Route::middleware('api')
            ->prefix('waiter')
            ->name('waiter.')
            ->group(base_path('routes/waiter.php'));

            Route::middleware('api')
            ->prefix('storage')
            ->name('storage.')
            ->group(base_path('routes/storage.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->alias([
            'IsKitchen' => KitchenMiddleware::class,
            'IsAdmin' => AdminMiddleware::class,
            'IsCustomer' => CustomerMiddleware::class,
            'IsDelivery' => DeliveryMiddleware::class,
            'IsCaptain' => CaptainMiddleware::class,
            'IsCashier' => CashierMiddleware::class,
            'IsBranch' => BranchMiddleware::class,
            'IsWaiter' => WaiterMiddleware::class,
            'IsAdminOrBranch' => AdmiOrBranchMiddleware::class,
            'IsStorage' => StorageMiddleware::class,
        ]);

        // Fix redirectGuestsTo
        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {

            // Allow API to continue without redirect
            if ($request->is('api/*')) {
                return null;
            }

            // Non-API requests
            return response()->json(['errors' => 'you must login'], 400);
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // Fix API unauthorized handling
        $exceptions->render(function (
            \Illuminate\Auth\AuthenticationException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 401);
            }
        });
    })
    ->create();
