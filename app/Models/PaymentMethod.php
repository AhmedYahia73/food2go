<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'logo',
        'status',
        'type',
        'order',
    ];
    protected $appends = ['logo_link'];

    public function getLogoLinkAttribute(){
        return url('storage/' . $this->attributes['logo']);
    }

    public function payment_method_data(){
        return $this->hasOne(PaymentMethodAuto::class);
    }
}
