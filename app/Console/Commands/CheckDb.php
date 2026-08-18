<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckDb extends Command
{
    protected $signature = 'check:db';
    protected $description = 'Check db orders';

    public function handle()
    {
        $orders = DB::table('orders')->orderBy('id', 'desc')->limit(5)->get(['id', 'order_number']);
        foreach ($orders as $order) {
            $this->info("ID: {$order->id}, Order Number: {$order->order_number}");
        }
    }
}
