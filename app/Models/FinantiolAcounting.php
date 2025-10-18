<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinantiolAcounting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'description_status',
        'details', 
        'balance',
        'status',
        'logo',
    ];
    protected $appends = ['logo_link'];

    public function getLogoLinkAttribute(){
        if (isset($this->attributes['logo'])) {
            return url('storage/' . $this->attributes['logo']);
        }
        else{
            return null;
        }
    }

    public function branch(){
        return $this->belongsToMany(Branch::class, 'financial_branch', 'financial_id', 'branch_id');
    }
}
