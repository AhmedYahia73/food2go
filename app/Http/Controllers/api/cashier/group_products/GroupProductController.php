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
        ->select("id", "name", "module", "due", "icon")
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

    public function group_lists(Request $request){
        // /captain/lists// 1. التحقق من البيانات
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'group_id'  => 'required|exists:group_products,id',
            'products'  => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.extras' => 'array',
            'products.*.extras.*' => 'required|exists:extra_products,id',
            'products.*.addons' => 'array',
            'products.*.addons.*' => 'required|exists:addons,id',
            'products.*.variations' => 'array',
            'products.*.variations.*.id' => 'required|exists:variation_products,id',
            'products.*.variations.*.options' => 'required|array',
            'products.*.variations.*.options.*' => 'required|exists:option_products,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // 2. جلب البيانات الأساسية
        $group_product = $this->group_product->findOrFail($request->group_id);
        $branch_id = $request->branch_id;
        $locale = $request->locale ?? app()->getLocale();

        // جلب الإعدادات الخاصة بالفرع (المنتجات/الأصناف المعطلة)
        $branch_off = $this->branch_off->where('branch_id', $branch_id)->get();
        $product_off  = $branch_off->pluck('product_id')->filter()->toArray();
        $category_off = $branch_off->pluck('category_id')->filter()->toArray();
        $option_off   = $branch_off->pluck('option_id')->filter()->toArray();

        // 3. استعلام المنتجات مع التحميل المسبق للعلاقات (Eager Loading)
        $product_ids = $request->input('products.*.id');
        $products = $this->products
            ->withLocale($locale)
            ->whereIn("id", $product_ids)
            ->where('item_type', '!=', 'online')
            ->where('status', 1)
            ->with([
                'addons' => fn($q) => $q->withLocale($locale),
                'category_addons' => fn($q) => $q->withLocale($locale),
                'excludes' => fn($q) => $q->withLocale($locale),
                'discount', 'extra', 'sales_count', 'tax',
                'product_pricing' => fn($q) => $q->where('branch_id', $branch_id),
                // العلاقات الضرورية للحسابات داخل الـ map لتجنب N+1
                'group_price' => fn($q) => $q->where('group_product_id', $group_product->id),
                'group_product_status', 
                'variations' => fn($q) => $q->withLocale($locale)->with([
                    'options' => fn($q) => $q->withLocale($locale)
                        ->with(['option_pricing' => fn($q) => $q->where('branch_id', $branch_id)])
                        // إضافة أسعار المجموعة للخيارات أيضاً
                        ->with(['group_price' => fn($q) => $q->where('group_product_id', $group_product->id)])
                ]),
            ])
            ->orderBy('order')
            ->get();

        // 4. معالجة البيانات (Logic)
        $processedProducts = $products->map(function($product) use ($category_off, $product_off, $option_off, $group_product, $branch_id) {
            
            // أولاً: التحقق من استبعاد المنتج بناءً على الفرع أو القسم
            if (in_array($product->category_id, $category_off) || 
                in_array($product->sub_category_id, $category_off) || 
                in_array($product->id, $product_off)) {
                return null;
            }

            // ثانياً: التحقق من حالة المنتج داخل المجموعة (Status)
            $is_active_in_group = $product->group_product_status->where('id', $group_product->id)->count() <= 0;
            if (!$is_active_in_group) return null;

            // ثالثاً: حساب السعر بناءً على المجموعة
            $groupPriceEntry = $product->group_price->first();
            if ($groupPriceEntry && $groupPriceEntry->price > 0) {
                $product->price = $groupPriceEntry->price;
            } else {
                // حساب السعر بناءً على النسبة المئوية للمجموعة
                $percentage = $group_product->increase_precentage - $group_product->decrease_precentage;
                $product->price = $product->price + ($percentage * $product->price / 100);
            }

            // رابعاً: إدارة المخزون
            $product->favourite = false;
            $sales_total = 0;
            if ($product->stock_type == 'fixed') {
                $sales_total = $product->sales_count->sum('count');
            } elseif ($product->stock_type == 'daily') {
                $sales_total = $product->sales_count->where('date', date('Y-m-d'))->sum('count');
            }
            $product->in_stock = $product->number > $sales_total;

            // خامساً: معالجة الخيارات (Variations & Options)
            $product->variations = $product->variations->map(function ($variation) use ($option_off, $group_product) {
                $variation->setRelation('options', $variation->options->reject(fn($opt) => in_array($opt->id, $option_off))
                    ->map(function($option) use ($group_product) {
                        // تسعير الخيارات بناءً على المجموعة
                        $optGroupPrice = $option->group_price->first();
                        if ($optGroupPrice && $optGroupPrice->price > 0) {
                            $option->price = $optGroupPrice->price;
                        } else {
                            $percentage = $group_product->increase_precentage - $group_product->decrease_precentage;
                            $option->price = $option->price + ($percentage * $option->price / 100);
                        }
                        return $option;
                    })
                );
                return $variation;
            });

            return $product;
        })->filter()->values();

        // 5. تحويل البيانات للـ Response
        $final_data = ProductResource::collection($processedProducts)->resolve();
        $catalogProducts = collect($final_data); // تحويلها لـ Collection عشان يسهل البحث فيها

        // 6. حساب المجاميع بناءً على اختيارات العميل في الـ Request
        $requestedProducts = $request->input('products', []);
        $products_items = [];

        foreach ($requestedProducts as $reqProduct) {
            $reqProductId = $reqProduct['id'];
            
            // نجيب المنتج بكامل بياناته وتفاصيله بعد الخصومات من الـ Resource
            $catalogProduct = $catalogProducts->firstWhere('id', $reqProductId);

            // لو المنتج مش موجود (مثلاً مقفول في الفرع وتم استبعاده في الخطوات السابقة) نتجاهله
            if (!$catalogProduct) {
                continue; 
            }

            // تهيئة الأسعار المبدئية بسعر المنتج الأساسي
            $totalPrice               = $catalogProduct['price'];
            $totalPriceAfterDiscount  = $catalogProduct['price_after_discount'] ?? $catalogProduct['price'];
            $totalPriceAfterTax       = $catalogProduct['price_after_tax'] ?? $catalogProduct['price'];
            $totalFinalPrice          = $catalogProduct['final_price'] ?? $catalogProduct['price'];

            // أ. تجميع أسعار الـ Addons
            if (isset($reqProduct['addons']) && is_array($reqProduct['addons'])) {
                $catalogAddons = collect($catalogProduct['addons'] ?? []);
                foreach ($reqProduct['addons'] as $addonId) {
                    $addon = $catalogAddons->firstWhere('id', $addonId);
                    if ($addon) {
                        $totalPrice               += $addon['price'] ?? 0;
                        $totalPriceAfterDiscount  += $addon['price_after_discount'] ?? ($addon['price'] ?? 0);
                        $totalPriceAfterTax       += $addon['price_after_tax'] ?? ($addon['price'] ?? 0);
                        $totalFinalPrice          += $addon['final_price'] ?? ($addon['price'] ?? 0);
                    }
                }
            }

            // ب. تجميع أسعار الـ Extras
            if (isset($reqProduct['extras']) && is_array($reqProduct['extras'])) {
                $catalogExtras = collect($catalogProduct['allExtras'] ?? []);
                foreach ($reqProduct['extras'] as $extraId) {
                    $extra = $catalogExtras->firstWhere('id', $extraId);
                    if ($extra) {
                        $totalPrice               += $extra['price'] ?? 0;
                        $totalPriceAfterDiscount  += $extra['price_after_discount'] ?? ($extra['price'] ?? 0);
                        $totalPriceAfterTax       += $extra['price_after_tax'] ?? ($extra['price'] ?? 0);
                        $totalFinalPrice          += $extra['final_price'] ?? ($extra['price'] ?? 0);
                    }
                }
            }

            // ج. تجميع أسعار الـ Variations & Options
            if (isset($reqProduct['variations']) && is_array($reqProduct['variations'])) {
                $catalogVariations = collect($catalogProduct['variations'] ?? []);
                
                foreach ($reqProduct['variations'] as $reqVariation) {
                    $catalogVariation = $catalogVariations->firstWhere('id', $reqVariation['id']);
                    
                    if ($catalogVariation && isset($reqVariation['options']) && is_array($reqVariation['options'])) {
                        $catalogOptions = collect($catalogVariation['options'] ?? []);
                        
                        foreach ($reqVariation['options'] as $optionId) {
                            $option = $catalogOptions->firstWhere('id', $optionId);
                            if ($option) {
                                $totalPrice               += $option['price'] ?? 0;
                                // ملاحظة: بناءً على الـ JSON الخاص بك، حقل الخصم في الخيارات اسمه after_disount (يوجد به خطأ إملائي)
                                $totalPriceAfterDiscount  += $option['after_disount'] ?? $option['price_after_discount'] ?? ($option['price'] ?? 0);
                                $totalPriceAfterTax       += $option['price_after_tax'] ?? ($option['price'] ?? 0);
                                $totalFinalPrice          += $option['final_price'] ?? ($option['price'] ?? 0);
                            }
                        }
                    }
                }
            }

            // إضافة النتيجة النهائية للمنتج داخل الـ Array
            $products_items[] = [
                "product_id"           => $reqProductId,
                "price"                => $totalPrice,
                "price_after_discount" => $totalPriceAfterDiscount,
                "price_after_tax"      => $totalPriceAfterTax,
                "final_price"          => $totalFinalPrice,
            ];
        }

        return response()->json([
            'products_items' => $products_items,
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
