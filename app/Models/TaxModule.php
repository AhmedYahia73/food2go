<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxModule extends Model
{

    protected $fillable = [
        'tax_id',
        'status',
    ];

    public function tax(){
        return $this->belongsTo(Tax::class, 'tax_id');
    }
    public function module(){
        return $this->hasMany(TaxModuleBranch::class, 'tax_module_id');
    }

    public function products(){
        return $this->belongsToMany(Product::class, 'tax_module_product', 'tax_module_id', 'product_id');
    }
}
