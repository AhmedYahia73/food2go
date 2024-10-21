<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use App\Models\Product;
use App\Models\Addon;
use App\Models\Delivery;
use App\Models\Offer;

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
        'address',
        'paid_by',
        'delivery_id',
        // address {phone, floor, road, address, latitude, longitude}
    ];

    public function customer(){
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function delivery(){
        return $this->belongsTo(Delivery::class, 'delivery_id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function products(){
        return $this->belongsToMany(Product::class, 'order_product', 'order_id', 'product_id');
    }

    public function addons(){
        return $this->belongsToMany(Addon::class, 'order_product', 'order_id', 'addon_id');
    }

    public function offers(){
        return $this->belongsToMany(Offer::class, 'order_product', 'order_id', 'offer_id');
    }
}
