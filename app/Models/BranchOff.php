<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchOff extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'option_id',
        'category_id',
        'branch_id',
    ];

    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function option(){
        return $this->belongsTo(OptionProduct::class, 'option_id');
    }

    public function category(){
        return $this->belongsTo(Category::class);
    }
}
