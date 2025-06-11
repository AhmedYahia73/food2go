<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'status',
    ];

    public function extra(){
        return $this->hasMany(ExtraGroup::class, 'group_id');
    }
}
