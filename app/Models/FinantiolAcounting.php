<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinantiolAcounting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'details',
        'branch_id',
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
}
