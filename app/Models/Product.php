<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Addon;
use App\Models\ExcludeProduct;
use App\Models\ExtraProduct;
use App\Models\VariationProduct;
use App\Models\Discount;
use App\Models\ProductSale;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image',
        'category_id',
        'sub_category_id',
        'item_type',
        'stock_type',
        'number',
        'price',
        'product_time_status',
        'from',
        'to',
        'discount_id',
        'tax_id',
        'status',
        'recommended',
        'points',
    ];

    public function discount(){
        return $this->belongsTo(Discount::class, 'discount_id');
    }

    public function addons(){
        return $this->belongsToMany(Addon::class, 'product_addon', 'product_id', 'addon_id');
    }

    public function excludes(){
        return $this->hasMany(ExcludeProduct::class, 'product_id');
    }

    public function extra(){
        return $this->hasMany(ExtraProduct::class, 'product_id');
    }

    public function variations(){
        return $this->hasMany(VariationProduct::class, 'product_id');
    }

    public function favourite_product(){
        return $this->belongsToMany(User::class, 'favourit_product')
        ->where('users.id', auth()->user()->id);
    }

    public function sales_count(){
        return $this->hasMany(ProductSale::class, 'product_id');
    }
}
