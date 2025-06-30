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
    ];
    protected $appends = ['order_date', 'status_payment'];

    public function getdateAttribute(){
        return $this->created_at->format('H:i:s');
    }

    public function getorderNumberAttribute(){
        $time_settings = TimeSittings::
        where('branch_id', $this->branch_id)
        ->orderByDesc('id')
        ->first();
        if (empty($time_settings)) {
            return $this->created_at->format('d') . $this->created_at->format('m') . 
            $this->created_at->format('y') . $this->id;
        }
        else{
            $from = $time_settings->from;
            $to = $this->created_at->format('H:i:s');
            if ($from > $to) {
                $date = Carbon::parse($this->created_at)->subDay();
            }
            else{
                $date = $this->created_at;
            }
            return $date->format('d') . $date->format('m') . 
            $date->format('y') . $this->id;
        }
    }

    public function getStatusPaymentAttribute(){
        if (isset($this->attributes['status']) && $this->attributes['status'] == 1) {
            return 'approved';
        } 
        elseif (!isset($this->attributes['status'])) { // Use isset to check if it's null or not set
            return 'pending';
        } 
        elseif (isset($this->attributes['status']) && $this->attributes['status'] == 0) {
            return 'rejected';
        } 
        elseif (isset($this->attributes['status']) && $this->attributes['status'] == 2) {
            return 'faild';
        } 
    }
    
    public function getOrderDateAttribute(){
        if (isset($this->attributes['created_at'] )&& !empty($this->attributes['created_at'])) {
            return Carbon::parse($this->attributes['created_at'])->format('Y-m-d');
        } 
        else {
            return null;
        }
    }

    public function getorderDetailsAttribute($data){
        return json_decode($data);
    }

    public function delivery(){
        return $this->belongsTo(Delivery::class, 'delivery_id');
    }

    public function payment_method(){
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function casheir(){
        return $this->belongsTo(Cashier::class, 'cashier_id');
    }

    public function products(){
        return $this->belongsToMany(Product::class, 'order_product', 'order_id', 'product_id')
        ->withPivot('created_at');
    }

    public function addons(){
        return $this->belongsToMany(Addon::class, 'order_product', 'order_id', 'addon_id');
    }

    public function offers(){
        return $this->belongsToMany(Offer::class, 'order_product', 'order_id', 'offer_id');
    }

    public function deal(){
        return $this->belongsToMany(Deal::class, 'order_product', 'order_id', 'deal_id');
    }

    public function address(){
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function order_address(){
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function admin(){
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function schedule(){
        return $this->belongsTo(ScheduleSlot::class, 'sechedule_slot_id');
    }

    public function details(){
        return $this->hasMany(OrderDetail::class, 'order_id');
    }
}
