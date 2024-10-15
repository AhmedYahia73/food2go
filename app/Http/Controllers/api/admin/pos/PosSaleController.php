<?php

namespace App\Http\Controllers\api\admin\pos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Category;
use App\Models\Customer;
use App\Models\User;
use App\Models\Discount;
use App\Models\Tax;
use App\Models\Branch;

class PosSaleController extends Controller
{
    public function __construct(private Category $categories, private Customer $customers,
    private User $users, private Discount $discounts, private Tax $taxes,
    private Branch $branches){}

    public function sale(){
        $categories = $this->categories
        ->with(['sub_categories.products.addons'])
        ->get();
        $customers = $this->customers->get();
        $users = $this->users->get();
        foreach ($customers as $item) {
            $item->id = '0' . $item->id;
        }
        $discounts = $this->discounts->get();
        $taxes = $this->taxes->get();
        $branches = $this->branches->get();

        return response()->json([
            'categories' => $categories,
            'customers' => $customers,
            'users' => $users,
            'discounts' => $discounts,
            'taxes' => $taxes,
            'branches' => $branches,
        ]);
    }
}
