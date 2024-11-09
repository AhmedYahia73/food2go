<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'product_id',
        'exclude_id',
        'addon_id',
        'offer_id',
        'extra_id',
        'variation_id',
        'option_id',
        'order_id',
        'count',
        'deal_id',
        'product_index',
        'addon_count',
    ];

    public function addon(){
        return $this->belongsTo(Addon::class);
    }

    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function exclude(){
        return $this->belongsTo(ExcludeProduct::class, 'exclude_id');
    }

    public function extra(){
        return $this->belongsTo(ExtraProduct::class, 'extra_id');
    }

    public function variation(){
        return $this->belongsTo(VariationProduct::class, 'variation_id');
    }

    public function option(){
        return $this->belongsTo(OptionProduct::class, 'option_id');
    }
}
