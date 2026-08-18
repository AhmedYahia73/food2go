<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Zone;

class Address extends Model
{
    use HasFactory, LogChanges;
    use SoftDeletes;
    
    protected $appends = ['customer_id'];
    public $temp_customer_id = null;

    public function getSyncAppends() {
        return ['customer_id' => $this->customer_id];
    }

    public function getCustomerIdAttribute(){
        return $this->temp_customer_id ?? $this->users()->first()?->id;
    }

    protected $fillable = [
        'zone_id',
        'address',
        'street',
        'building_num',
        'floor_num',
        'apartment',
        'additional_data',
        'type',
        'map',
        'customer_id',
        'city_id',
    ];

    public function zone(){
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function city(){
        return $this->belongsTo(City::class, 'city_id');
    }

    public function address(){
        return $this->belongsToMany(User::class ,'user_address')->using(UserAddress::class);
    }

    public function users(){
        return $this->belongsToMany(User::class ,'user_address')->using(UserAddress::class);
    }
}
