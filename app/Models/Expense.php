<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_id',
        'admin_id',
        'branch_id',
        'cashier_id',
        'cahier_man_id',
        'financial_account_id',
        'category_id',
        'amount',
        'shift',
        'note',
    ];

    public function expense(){
        return $this->belongsTo(ExpenseList::class, 'expense_id');
    }

    public function admin(){
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function cashier(){
        return $this->belongsTo(Cashier::class, 'cashier_id');
    }

    public function cahier_man(){
        return $this->belongsTo(CashierMan::class, 'cahier_man_id');
    }

    public function financial_account(){
        return $this->belongsTo(FinantiolAcounting::class, 'financial_account_id');
    }

    public function category(){
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }
}
