<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class Group extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'name', 
        'status',
    ];

    public function extra(){
        return $this->hasMany(ExtraGroup::class, 'group_id');
    }

    public function getIdAttribute($value){
        return (int) $value;
    }
}
