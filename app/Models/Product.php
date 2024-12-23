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
use App\Models\Tax;

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
    protected $appends = ['image_link'];

    public function getImageLinkAttribute(){
        return url('storage/' . $this->attributes['image']);
    }

    public function category(){
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subCategory(){
        return $this->belongsTo(Category::class, 'sub_category_id');
    }
    
    public function discount(){
        return $this->belongsTo(Discount::class, 'discount_id');
    }

    public function tax(){
        return $this->belongsTo(Tax::class, 'tax_id');
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
    
    public function translations()
    {
        return $this->morphMany(TranslationTbl::class, 'translatable');
    }
}
