<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bundle extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image',
        'discount_id',
        'tax_id',
        'price',
        'status',
        'points',
    ];
    protected $appends = ['image_link'];

    public function getImageLinkAttribute(){
        if(isset($this->attributes['image'])){
            return url("storage/" . $this->attributes['image']);
        }
        return null;
    }

    public function products(){
        return $this->belongsToMany(Product::class, 'bundle_product', 'bundle_id', 'product_id');
    }

    public function discount(){
        return $this->belongsTo(Discount::class, 'discount_id');
    }

    public function tax(){
        return $this->belongsTo(Tax::class, 'tax_id');
    }
}
