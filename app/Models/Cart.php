<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class Cart extends Model
{
    use HasFactory;

    protected $table = 'carts';

    protected $fillable = [
        'cashier_man_id',
        'cashier_id',
        'product_id',
        'price',
        'tax',
        'discount',
        'final_price',
        'total',
        'quantity',
        'notes',
        'addons',
        'extras',
        'exclude',
        'variation',
        'type',
    ];

    protected $casts = [
        'price' => 'float',
        'tax' => 'float',
        'discount' => 'float',
        'final_price' => 'float',
        'total' => 'float',
        'quantity' => 'integer',
        'addons' => 'array',
        'extras' => 'array',
        'exclude' => 'array',
        'variation' => 'array',
    ];

    public function getIdAttribute($value)
    {
        return (int) $value;
    }

    // Alias accessors for cahier_man_id (handling the typo if accessed or assigned)
    public function getCahierManIdAttribute()
    {
        return $this->cashier_man_id;
    }

    public function setCahierManIdAttribute($value)
    {
        $this->attributes['cashier_man_id'] = $value;
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function cashier_man()
    {
        return $this->belongsTo(CashierMan::class, 'cashier_man_id');
    }

    public function cashier()
    {
        return $this->belongsTo(Cashier::class, 'cashier_id');
    }
}
