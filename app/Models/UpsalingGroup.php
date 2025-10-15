<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UpsalingGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
    ];

    public function products(){
        return $this->belongsToMany(Product::class, "upsaling_group_product", "upsaling_group_id", "product_id");
    }
}
