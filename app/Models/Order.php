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
    ];
    protected $appends = ['order_date'];
    
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

    public function pament_method(){
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id');
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

    public function details(){
        return $this->hasMany(OrderDetail::class, 'order_id');
    }
}
