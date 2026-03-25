<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'description',
        'logo',
        'status',
        'type',
        'order',
        'feez_status',
        'feez_amount',
    ];
    protected $appends = ['logo_link'];

    public function getLogoLinkAttribute(){
        if(isset($this->attributes['logo'])){
            return url('storage/' . $this->attributes['logo']);
        }
        return null;
    }

    public function payment_method_data(){
        return $this->hasOne(PaymentMethodAuto::class);
    }
    
    protected static function booted()
    {
        static::addGlobalScope('order', function ($query) {
            $query->orderBy('order', 'asc');
        });
    }
}
