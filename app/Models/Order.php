<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Branch;
use App\Models\User;
use App\Models\Product;
use App\Models\Addon;
use App\Models\Delivery;
use App\Models\Offer;
use App\Models\Deal;
use Carbon\Carbon;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'rate',
        'prepare_order',
        'comment',
        'service_fees',
        'service_fees_id',
        'pos',
        'user_id',
        'branch_id',
        'amount',
        'order_status',
        'order_type',
        'payment_status',
        'total_tax',
        'total_discount',
        'address_id', 
        'delivery_id',
        'notes',
        'coupon_discount',
        'order_number',
        'payment_method_id', 
        'status',
        'points',
        'order_details',
        'rejected_reason',
        'transaction_id',
        'receipt',
        'cancel_reason',
        'customer_cancel_reason',
        'admin_cancel_reason',
        'table_id',
        'captain_id',
        'cashier_man_id',
        'cashier_id', 
        'shift',
        'admin_id',
        'operation_status',
        'sechedule_slot_id',
        'canceled_noti',
        'customer_id',
        'deleted_at',
        'source',
        'take_away_status',
        'delivery_status',
        'delivery_fees',
        'coupon_id',
        'from_table_order',
        'due',
        'dicount_id',
        'preparation_read_status',
        'due_from_delivery',
        'void_financial_id',
        'is_void',
        'is_cancel_evaluate',
        'free_discount',
        'module_id',
        'module_order_number',
        'due_module',
        'transfer_from_id',
        'void_id',
        'void_reason', 
        "is_read",
        "prepare_order",
        'order_active' // ده عشان لو مكملش طلب الاوردر يتحفظ فقط
    ];
    protected $appends = ['order_date', 'status_payment', 'order_details_data'];

    protected $hidden = [
        'pivot', 
    ];

    public function financials(){
    }

    public function getdateAttribute(){
    }

    public function service_fees_item(){
    }
    
    public function transfer_from(){
    }
    
    public function bundles(){
    }

    public function group_module(){
    }

    public function getorderNumberAttribute()
    {
    }


    public function getStatusPaymentAttribute(){
    }
    
    public function getOrderDateAttribute(){
    }

    public function getOrderDetailsDataAttribute(){
    }

    public function getorderDetailsAttribute($data){
    }

    public function void(){
    }

    public function financial_accountigs(){
    }

    public function financial_amount(){
    }

    public function captain(){
    }

    public function delivery(){
    }

    public function table(){
    }

    public function payment_method(){
    }

    public function user(){
    }

    public function branch(){
    }

    public function cashier_man(){
    }

    public function casheir(){
    }

    public function products(){
    }

    public function addons(){
    }

    public function offers(){
    }

    public function deal(){
    }

    public function address(){
    }

    public function order_address(){
    }

    public function admin(){ 
    }

    public function schedule(){ 
    }

    public function details(){ 
    }
}
