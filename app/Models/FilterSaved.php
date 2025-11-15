<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FilterSaved extends Model
{
    protected $fillable = [
        'name',
        'filter_obj',
        'type', // financial_report, order_report
    ];
    
    public function getfilterObjAttribute(){
        if (isset($this->attributes['filter_obj'])) {
            return json_decode($this->attributes['filter_obj']);
        }
        else{
            return null;
        }
    }
}
