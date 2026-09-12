<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class PurchaseMaterial extends Model
{
    use HasFactory, LogChanges;

    protected $table = 'purchase_materials';

    protected $fillable = [
        'purchase_id',
        'category_material_id',
        'material_id',
        'unit_id',
        'count',
    ];

    public function getIdAttribute($value){
        return (int) $value;
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function category()
    {
        return $this->belongsTo(MaterialCategory::class, 'category_material_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
