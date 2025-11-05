<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseRecipeDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_id',  
        'weight',  
        'store_category_id',
        'store_product_id', 
    ];

    public function recipe(){
        return $this->belongsTo(PurchaseRecipe::class, "recipe_id");
    }

    public function category(){
        return $this->belongsTo(MaterialCategory::class, "store_category_id");
    }

    public function product(){
        return $this->belongsTo(Material::class, "store_product_id");
    }
}
