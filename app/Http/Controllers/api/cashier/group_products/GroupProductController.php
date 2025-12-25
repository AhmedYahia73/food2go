<?php

namespace App\Http\Controllers\api\cashier\group_products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Validator;

use App\Models\BranchOff;
use App\Models\Product;
use App\Models\Category;
use App\Models\GroupProduct;
use App\Models\GroupPrice;
use App\Models\CafeLocation;

class GroupProductController extends Controller
{
    public function __construct(
        private BranchOff $branch_off, 
        private Category $category, private GroupPrice $group_price,
        private Product $products, private GroupProduct $group_product,
        private CafeLocation $cafe_location,){}

    public function groups_product(Request $request){
        $group_product = $this->group_product
        ->select("id", "name", "module", "due")
        ->where("status", 1)
        ->get();

        return response()->json([
            "group_product" => $group_product
        ]);
    }

    public function lists(Request $request){
        // /captain/lists
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'group_id' => 'required|exists:group_products,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $group_id = $request->group_id;
        // Group Product
        $group_product = $this->group_product
        ->where("id", $request->group_id)
        ->first();
        // ___________________________
        $branch_id = $request->branch_id; 

// ghgfhgfgfhhhhhhhhhhhhhhhhhhhhhhh 
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
        $branch_off = $this->branch_off
        ->where('branch_id', $branch_id)
        ->get();
        $product_off = $branch_off->pluck('product_id')->filter();
        $category_off = $branch_off->pluck('category_id')->filter();
        $option_off = $branch_off->pluck('option_id')->filter();
 
        $categories = $this->category
        ->with(['sub_categories' => function($query) use($locale){
            $query->withLocale($locale);
        }, 
        'addons' => function($query) use($locale){
            $query->withLocale($locale);
        }])
        ->withLocale($locale)
        ->where('status', 1)
        ->orderBy("priority")
        ->where('category_id', null)
        ->get()
        ->filter(function($item) use($category_off){
            return !$category_off->contains($item->id);
        });
            
        $products = $this->products
        ->orderBy('order')
        ->with([
            'addons' => fn($q) => $q->withLocale($locale),
            'category_addons' => fn($q) => $q->withLocale($locale),
            'sub_category_addons' => fn($q) => $q->withLocale($locale),
            'excludes' => fn($q) => $q->withLocale($locale),
            'discount', 'extra', 'sales_count', 'tax',
            'product_pricing' => fn($q) => $q->where('branch_id', $branch_id),
            'variations' => fn($q) => $q->withLocale($locale)->with([
                'options' => fn($q) => $q
                    ->with(['option_pricing' => fn($q) => $q->where('branch_id', $branch_id)])
                    ->withLocale($locale),
            ]),
                'group_products' => fn($q) => $q
                ->with(['products' => fn($q) => $q
                ->select("products.id", "products.name")->withLocale($locale)]),
        ])
        ->withLocale($locale)
        ->where('item_type', '!=', 'online') 
        ->where('status', 1)
        ->get()
        ->map(function($product) use($category_off, $product_off, $option_off, 
        $branch_id, $request, $group_id, $group_product){
            //get count of sales of product to detemine stock
            // Price of group
            $price = $product->price;
            $new_price = $product?->group_price
            ?->where("group_product_id", $request->group_id)
            ?->first()?->price ?? null;
            if(empty($new_price)){
                $new_price = $group_product->increase_precentage - $group_product->decrease_precentage;
                $new_price = $price + $new_price * $price / 100;
            }
            $product->price = $new_price;
            $status = $product->group_product_status
            ->where("id", $group_product->id)->count()
            <= 0;
            if(!$status){
                return null;
            }
            // ____________________________________
            $product->favourite = false;
            if ($product->stock_type == 'fixed') {
                $product->count = $product->sales_count->sum('count');
                $product->in_stock = $product->number > $product->count ? true : false;
            }
            elseif ($product->stock_type == 'daily') {
                $product->count = $product->sales_count
                ->where('date', date('Y-m-d'))
                ->sum('count');
                $product->in_stock = $product->number > $product->count ? true : false;
            }
            // return !$category_off->contains($item->id);
            // $category_off, $product_off, $option_off
            if ($category_off->contains($product->category_id) || 
            $category_off->contains($product->sub_category_id)
            || $product_off->contains($product->id)) {
                return null;
            }
            $product->variations = $product->variations->map(function ($variation) 
            use ($option_off, $product, $branch_id, $group_id, $group_product) {
                $variation->options = $variation->options->reject(fn($option) => $option_off->contains($option->id));
                $variation->options = $variation->options->map(function($element) use($branch_id, $group_id, $group_product){
                     $price = $element?->group_price
                    ?->where("group_product_id", $group_id)
                    ?->where("option_id", $element->id)
                    ?->first()?->price ?? null;
                    if(empty($price)){
                        $price = $group_product->increase_precentage - $group_product->decrease_precentage;
                        $price = $element->price + $price * $element->price / 100;
                    }
                    $status = $element->group_product_status
                    ->where("id", $group_product->id)->count()
                    <= 0; 
                    $element->price = $price;
                    return $element;
                });
              
                return $variation;
            });
            $product->addons = $product->addons->map(function ($addon) 
            use ($product) {
                $addon->discount = $product->discount;
              
                return $addon;
            });
            return $product;
        })->filter(); 
        $cafe_location = $this->cafe_location
        ->with(['tables' => function($query){
            return $query
            ->where('status', 1)
            ->where('is_merge', 0)
            ->with('sub_table:id,table_number,capacity,main_table_id', 'call_payment');
        }])
        ->where('branch_id', $branch_id)
        ->get()
        ->map(function($item){
            $item->tables =  $item?->tables?->map(function($element){
                $element->call_payment_status = $element->call_payment->count() > 0 ? true: false;
                $element->makeHidden(['call_payment']);
                return $element; 
            });
            return $item;
        });
        $favourite_products = $this->products 
        ->orderBy('order')
        ->with([
            'addons' => fn($q) => $q->withLocale($locale),
            'category_addons' => fn($q) => $q->withLocale($locale),
            'sub_category_addons' => fn($q) => $q->withLocale($locale),
            'excludes' => fn($q) => $q->withLocale($locale),
            'discount', 'extra', 'sales_count', 'tax',
            'product_pricing' => fn($q) => $q->where('branch_id', $branch_id),
            'variations' => fn($q) => $q->withLocale($locale)->with([
                'options' => fn($q) => $q
                    ->with(['option_pricing' => fn($q) => $q->where('branch_id', $branch_id)])
                    ->withLocale($locale),
            ]),
                'group_products' => fn($q) => $q
                ->with(['products' => fn($q) => $q
                ->select("products.id", "products.name")->withLocale($locale)]),
        ])
        ->withLocale($locale)
        ->where('item_type', '!=', 'online') 
        ->where("favourite", 1)
        ->where('status', 1)
        ->get()
        ->map(function($product) use($category_off, $product_off, $option_off, 
        $branch_id, $request, $group_id, $group_product){
            //get count of sales of product to detemine stock
            // Price of group
            $price = $product->price;
            $new_price = $product?->group_price
            ?->where("group_product_id", $request->group_id)
            ?->first()?->price ?? null;
            if(empty($new_price)){
                $new_price = $group_product->increase_precentage - $group_product->decrease_precentage;
                $new_price = $price + $new_price * $price / 100;
            }
            $product->price = $new_price;
            $status = $product->group_product_status
            ->where("id", $group_product->id)->count()
            <= 0;
            if(!$status){
                return null;
            }
            // ____________________________________
            $product->favourite = false;
            if ($product->stock_type == 'fixed') {
                $product->count = $product->sales_count->sum('count');
                $product->in_stock = $product->number > $product->count ? true : false;
            }
            elseif ($product->stock_type == 'daily') {
                $product->count = $product->sales_count
                ->where('date', date('Y-m-d'))
                ->sum('count');
                $product->in_stock = $product->number > $product->count ? true : false;
            }
            // return !$category_off->contains($item->id);
            // $category_off, $product_off, $option_off
            if ($category_off->contains($product->category_id) || 
            $category_off->contains($product->sub_category_id)
            || $product_off->contains($product->id)) {
                return null;
            }
            $product->variations = $product->variations->map(function ($variation) 
            use ($option_off, $product, $branch_id, $group_id, $group_product) {
                $variation->options = $variation->options->reject(fn($option) => $option_off->contains($option->id));
                $variation->options = $variation->options->map(function($element) use($branch_id, $group_id, $group_product){
                     $price = $element?->group_price
                    ?->where("group_product_id", $group_id)
                    ?->where("option_id", $element->id)
                    ?->first()?->price ?? null;
                    if(empty($price)){
                        $price = $group_product->increase_precentage - $group_product->decrease_precentage;
                        $price = $element->price + $price * $element->price / 100;
                    }
                    $status = $element->group_product_status
                    ->where("id", $group_product->id)->count()
                    <= 0; 
                    $element->price = $price;
                    return $element;
                });
              
                return $variation;
            });
            $product->addons = $product->addons->map(function ($addon) 
            use ($product) {
                $addon->discount = $product->discount;
              
                return $addon;
            });
            return $product;
        })->filter(); 
        $cafe_location = $this->cafe_location
        ->with(['tables' => function($query){
            return $query
            ->where('status', 1)
            ->where('is_merge', 0)
            ->with('sub_table:id,table_number,capacity,main_table_id', 'call_payment');
        }])
        ->where('branch_id', $branch_id)
        ->get()
        ->map(function($item){
            $item->tables =  $item?->tables?->map(function($element){
                $element->call_payment_status = $element->call_payment->count() > 0 ? true: false;
                $element->makeHidden(['call_payment']);
                return $element; 
            });
            return $item;
        });
        $products_count = $products->where("weight_status", 0)
        ->values();
        $products_weight = $products->where("weight_status", 1)
        ->values();
        $favourite_products_count = $favourite_products->where("weight_status", 0)
        ->values();
        $favourite_products_weight = $favourite_products->where("weight_status", 1)
        ->values();
        $categories = CategoryResource::collection($categories);
        $products = ProductResource::collection($products_count); 
        $favourite_products = ProductResource::collection($favourite_products_count); 
        $favourite_products_weight = ProductResource::collection($favourite_products_weight); 
        $products_weight = ProductResource::collection($products_weight); 

        return response()->json([
            'categories' => $categories,
            'products' => $products, 
            'products_weight' => $products_weight, 
            'favourite_products' => $favourite_products, 
            'favourite_products_weight' => $favourite_products_weight, 
            'cafe_location' => $cafe_location,
        ]);
    }

    public function product_category_lists(Request $request, $id){
        // /captain/product_category_lists/{id}
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'group_id' => 'required|exists:group_products,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        // Group Product
        $group_product = $this->group_product
        ->where("id", $request->group_id)
        ->first();
        // ___________________________
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
        $branch_id = $request->branch_id;
        $branch_off = $this->branch_off
        ->where('branch_id', $branch_id)
        ->get();
        $product_off = $branch_off->pluck('product_id')->filter();
        $category_off = $branch_off->pluck('category_id')->filter();
        $option_off = $branch_off->pluck('option_id')->filter();

        $products = $this->products
        ->with(['addons' => function($query) use($locale){
            $query->withLocale($locale);
        },'sub_category_addons' => function($query) use($locale){
            $query->withLocale($locale);
        }, 'category_addons' => function($query) use($locale){
            $query->withLocale($locale);
        }, 'excludes' => function($query) use($locale){
            $query->withLocale($locale);
        }, 'extra', 'discount', 
        'variations' => function($query) use($locale){
            $query->withLocale($locale)
            ->with(['options' => function($query_option) use($locale){
                $query_option->with(['extra' => function($query_extra) use($locale){
                    $query_extra->with('parent_extra')
                    ->withLocale($locale);
                }])
                ->withLocale($locale);
            }]);
        }, 'sales_count', 'tax', "group_price", 
        "group_product_status"])
        ->withLocale($locale)
        ->where('item_type', '!=', 'online')
        ->where(function($query) use($id){
            $query->where("category_id", $id)
            ->orWhere("sub_category_id", $id);
        })
        ->where('status', 1)
        ->get()
        ->map(function($product) use($category_off, $product_off, $option_off, 
        $branch_id, $request, $group_product){
            //get count of sales of product to detemine stock
            // Price of group
            $price = $product->price;
            $new_price = $product?->group_price
            ?->where("group_product_id", $request->group_id)
            ?->first()?->price ?? null;
            if(empty($new_price)){
                $new_price = $group_product->increase_precentage - $group_product->decrease_precentage;
                $new_price = $price + $new_price * $price / 100;
            }
            $product->price = $new_price;
            $status = $product->group_product_status
            ->where("id", $group_product->id)->count()
            <= 0;
            if(!$status){
                return null;
            }
            // ____________________________________
            $product->favourite = false;
            if ($product->stock_type == 'fixed') {
                $product->count = $product->sales_count->sum('count');
                $product->in_stock = $product->number > $product->count ? true : false;
            }
            elseif ($product->stock_type == 'daily') {
                $product->count = $product->sales_count
                ->where('date', date('Y-m-d'))
                ->sum('count');
                $product->in_stock = $product->number > $product->count ? true : false;
            }
            // return !$category_off->contains($item->id);
            // $category_off, $product_off, $option_off
            if ($category_off->contains($product->category_id) || 
            $category_off->contains($product->sub_category_id)
            || $product_off->contains($product->id)) {
                return null;
            }
            $product->variations = $product->variations->map(function ($variation) 
            use ($option_off, $product, $branch_id) {
                $variation->options = $variation->options->reject(fn($option) => $option_off->contains($option->id));
                $variation->options = $variation->options->map(function($element) use($branch_id){
                    $element->price = $element?->option_pricing->where('branch_id', $branch_id)
                    ->first()?->price ?? $element->price;
                    return $element;
                });
              
                return $variation;
            });
            $product->addons = $product->addons->map(function ($addon) 
            use ($product) {
                $addon->discount = $product->discount;
              
                return $addon;
            });
            return $product;
        })->filter();
        $products = ProductResource::collection($products); 

        return response()->json([
            'products' => $products, 
        ]);
    }
}
