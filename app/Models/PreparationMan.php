<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreparationMan extends Model
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'password',
        'branch_id',
        'status',
    ];
    protected $appends = ['role'];

    public function getRoleAttribute(){
        return 'preparation_man';
    }

    public function branch(){
        return $this->belongsTo(Branch::class, "branch_id");
    }
    
    protected $hidden = [
        'password',
        'remember_token',
    ];
}
