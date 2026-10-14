<?php

namespace App\Http\Controllers\api\cashier\make_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Cart;
use App\Models\BranchOff;
use App\Models\Product;
use App\Models\Category;
use App\Models\Addon;
use App\Models\ExtraProduct;
use App\Models\ExcludeProduct;
use App\Models\VariationProduct;
use App\Models\OptionProduct;
use App\Models\TranslationTbl;
use App\Http\Resources\ProductResource;

class CartOrderController extends Controller
{
    /**
     * Store item in cart for the current cashier.
     */
    public function add_to_cart(Request $request)
    {
        // Normalize singular/plural parameter names if needed
        if (!$request->has('exclude') && $request->has('excludes')) {
            $request->merge(['exclude' => $request->excludes]);
        }
        if (!$request->has('variation') && $request->has('variations')) {
            $request->merge(['variation' => $request->variations]);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:takeaway,take_away,delivery',
            'product_id' => 'required|exists:products,id',
            'price' => 'required|numeric',
            'tax' => 'required|numeric',
            'discount' => 'required|numeric',
            'final_price' => 'required|numeric',
            'total' => 'required|numeric',
            'quantity' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',

            'addons' => 'nullable|array',
            'addons.*.addon_id' => 'required|exists:addons,id',
            'addons.*.price' => 'required|numeric',
            'addons.*.tax' => 'required|numeric',
            'addons.*.discount' => 'required|numeric',
            'addons.*.final_price' => 'required|numeric',

            'extras' => 'nullable|array',
            'extras.*.extra_id' => 'required|exists:extra_products,id',
            'extras.*.price' => 'required|numeric',
            'extras.*.tax' => 'required|numeric',
            'extras.*.discount' => 'required|numeric',
            'extras.*.final_price' => 'required|numeric',

            'exclude' => 'nullable|array',
            'exclude.*.exclude_id' => 'required|exists:exclude_products,id',
            'exclude.*.price' => 'required|numeric',
            'exclude.*.tax' => 'required|numeric',
            'exclude.*.discount' => 'required|numeric',
            'exclude.*.final_price' => 'required|numeric',

            'variation' => 'nullable|array',
            'variation.*.variation_id' => 'required|exists:variation_products,id',
            'variation.*.options' => 'required|array|min:1',
            'variation.*.options.*.option_id' => 'required|exists:option_products,id',
            'variation.*.options.*.price' => 'required|numeric',
            'variation.*.options.*.tax' => 'required|numeric',
            'variation.*.options.*.discount' => 'required|numeric',
            'variation.*.options.*.final_price' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user() ?? auth()->user() ?? auth('sanctum')->user();
        $cashierManId = $user?->id ?? $request->cashier_man_id ?? $request->cahier_man_id;
        $cashierId = $user?->cashier_id ?? $request->cashier_id;
        $type = in_array($request->type, ['takeaway', 'take_away']) ? 'takeaway' : 'delivery';

        $cart = Cart::create([
            'cashier_man_id' => $cashierManId,
            'cashier_id' => $cashierId,
            'type' => $type,
            'product_id' => $request->product_id,
            'price' => $request->price,
            'tax' => $request->tax ?? 0,
            'discount' => $request->discount ?? 0,
            'final_price' => $request->final_price,
            'total' => $request->total,
            'quantity' => $request->quantity ?? $request->count ?? 1,
            'notes' => $request->notes ?? $request->note ?? null,
            'addons' => $request->addons ?? [],
            'extras' => $request->extras ?? [],
            'exclude' => $request->exclude ?? [],
            'variation' => $request->variation ?? [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cart item added successfully',
            'cart' => [
                'id' => $cart->id,
                'type' => $cart->type,
                'product_id' => $cart->product_id,
                'price' => $cart->price,
                'tax' => $cart->tax,
                'discount' => $cart->discount,
                'final_price' => $cart->final_price,
                'total' => $cart->total,
                'quantity' => $cart->quantity,
                'addons' => $cart->addons,
                'extras' => $cart->extras,
                'exclude' => $cart->exclude,
                'variation' => $cart->variation,
            ],
            'data' => $cart,
        ], 200);
    }

    
    public function product_item(Request $request, $id) {
        // 1. Validation
        $validator = Validator::make($request->all(), [
            'module' => 'in:take_away,dine_in,delivery'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        } 

        // 2. Initial Setup & Optimization Variables
        $module = $request->module ?? null;
        $locale = $request->locale ?? $request->query('locale', app()->getLocale());
        $branch_id = $request->user()->branch_id;
        $today = date('Y-m-d');
        $current_time = date('H:i:s');
        $current_day = date('l');

        // Get exclusions in one query
        $branch_off = BranchOff::where('branch_id', $branch_id)->get();
        $product_off = BranchOff::pluck('product_id')->filter()->toArray();
        $category_off = BranchOff::pluck('category_id')->filter()->toArray();
        $option_off = BranchOff::pluck('option_id')->filter()->toArray();

        // 3. Fetch Categories
        $categories = Category::
            with([
                'sub_categories' => fn($q) => $q->withLocale($locale), 
                'addons' => fn($q) => $q->withLocale($locale)
            ])
            ->withLocale($locale)
            ->where(function($query){
                $query->where("module", "pos")
                ->orWhere("module", "all");
            })
            ->where('status', 1)
            ->whereNull('category_id')
            ->orderBy("priority") 
            ->get()
            ->each(function($item) use ($category_off) {
                $item->setRelation('sub_categories', $item->sub_categories->reject(fn($sub) => in_array($sub->id, $category_off)));
            })
            ->reject(fn($item) => in_array($item->id, $category_off));

        // 4. Shared Product Processor Method (Optimized to run everything in-memory)
        $processProducts = function($productsQuery) use ($category_off, $product_off, $option_off, $branch_id, $module, $today) {
            return $productsQuery->get()->map(function($product) use ($category_off, $product_off, $option_off, $branch_id, $module, $today) {
                // Exclude early before heavy calculations
                if (in_array($product->category_id, $category_off) || 
                    in_array($product->sub_category_id, $category_off) || 
                    in_array($product->id, $product_off)) {
                    return null;
                }

                // Resolve Pricing
                $new_price = $product->product_pricing->firstWhere('branch_id', $branch_id)?->price;
                if (empty($new_price)) {
                    $new_price = $product->pos_pricing->firstWhere('module', $module)?->price ?? $product->price;
                }
                $product->price = $new_price;
                $product->favourite = false;

                // Stock Calculation (Using in-memory collections instead of queries)
                if ($product->stock_type == 'fixed') {
                    $product->count = $product->sales_count->sum('count');
                    $product->in_stock = $product->number > $product->count;
                } elseif ($product->stock_type == 'daily') {
                    $product->count = $product->sales_count->where('date', $today)->sum('count');
                    $product->in_stock = $product->number > $product->count;
                }

                // Resolve Tax Module efficiently
                $resolved_tax = $product->tax_module->first()?->tax ?? $product->tax ?? null;

                // Map Variations & Options
                $product->variations = $product->variations->map(function ($variation) use ($option_off, $branch_id, $resolved_tax) {
                    $variation->options = $variation->options
                        ->reject(fn($option) => in_array($option->id, $option_off))
                        ->map(function($element) use ($branch_id, $resolved_tax) {
                            $element->price = $element->option_pricing->firstWhere('branch_id', $branch_id)?->price ?? $element->price;
                            $element->new_tax = $resolved_tax;
                            return $element;
                        });
                    return $variation;
                });

                // Map Addons
                $product->addons = $product->addons->map(function ($addon) use ($product) {
                    $addon->discount = $product->discount;
                    return $addon;
                });

                if (!empty($resolved_tax)) {
                    $product->tax = $resolved_tax;
                }

                return $product;
            })->filter();
        };

        // 5. Build Base Product Query with all Eager Loads to avoid N+1
        $baseProductQuery = Product::
            orderBy('order')
            ->with([
                'addons' => fn($q) => $q->withLocale($locale),
                'sub_category_addons' => fn($q) => $q->withLocale($locale), 
                'category_addons' => fn($q) => $q->withLocale($locale), 
                'excludes' => fn($q) => $q->withLocale($locale), 
                'extra', 'sales_count', 'tax', 'product_pricing', 'pos_pricing',
                'variations' => fn($q) => $q->withLocale($locale)->with([
                    'options' => fn($qo) => $qo->where("status", 1)->withLocale($locale)->with([
                        'extra' => fn($qe) => $qe->with('parent_extra')->withLocale($locale),
                        'option_pricing'
                    ])
                ]),
                'tax_module' => fn($q) => $q->whereHas('module', fn($qm) => $qm->where('branch_id', $branch_id)->whereIn('app_type', ['pos', 'all']))
                                            ->with(['tax', 'module']),
                "discount" => fn($q) => $q->where(fn($d) => $d->whereJsonContains("module", "pos")->orWhereJsonContains("module", "all"))
            ])
            ->withLocale($locale)
            ->where("id", $id);

        // Execute product pipelines
        $allProcessedProducts = $processProducts(clone $baseProductQuery);

        // 6. Partition Products by Weight Status (In-memory, avoids extra queries)
        $products_count = $allProcessedProducts->values();
         


        $products = ProductResource::collection($products_count); 

        // 10. Response
        return response()->json([
            'products' => $products
        ]);
    } 

    /**
     * Fetch carts for current cashier with product/addon/extra/exclude/variation/option names translated by lang key.
     */
    public function get_carts(Request $request)
    {
        $user = $request->user() ?? auth()->user() ?? auth('sanctum')->user();
        $cashierManId = $user?->id ?? $request->cashier_man_id ?? $request->cahier_man_id;
        $cashierId = $user?->cashier_id ?? $request->cashier_id;

        $total_price = 0;
        $query = Cart::query();
        if ($cashierManId) {
            $query->where('cashier_man_id', $cashierManId);
        }
        if (!is_null($cashierId)) {
            $query->where('cashier_id', $cashierId);
        }
        if ($request->has('type') && !empty($request->type)) {
            $filterType = in_array($request->type, ['takeaway', 'take_away']) ? 'takeaway' : 'delivery';
            $query->where(function ($q) use ($filterType) {
                $q->where('type', $filterType);
                if ($filterType === 'takeaway') {
                    $q->orWhere('type', 'take_away');
                }
            });
        }

        $carts = $query->orderBy('id', 'desc')->get();

        // Language key sent by admin/cashier (from header or request/query)
        $locale = $request->header('lang')
            ?? $request->header('locale')
            ?? $request->lang
            ?? $request->locale
            ?? $request->query('lang')
            ?? $request->query('locale')
            ?? app()->getLocale()
            ?? 'en';

        // Collect all IDs to fetch models and translations in batches
        $productIds = $carts->pluck('product_id')->filter()->unique()->values();
        $addonIds = collect();
        $extraIds = collect();
        $excludeIds = collect();
        $variationIds = collect();
        $optionIds = collect();

        foreach ($carts as $cart) {
            foreach ($cart->addons ?? [] as $addon) {
                if (!empty($addon['addon_id'])) {
                    $addonIds->push($addon['addon_id']);
                }
            }
            foreach ($cart->extras ?? [] as $extra) {
                if (!empty($extra['extra_id'])) {
                    $extraIds->push($extra['extra_id']);
                }
            }
            foreach ($cart->exclude ?? [] as $ex) {
                if (!empty($ex['exclude_id'])) {
                    $excludeIds->push($ex['exclude_id']);
                }
            }
            foreach ($cart->variation ?? [] as $var) {
                if (!empty($var['variation_id'])) {
                    $variationIds->push($var['variation_id']);
                }
                foreach ($var['options'] ?? [] as $opt) {
                    if (!empty($opt['option_id'])) {
                        $optionIds->push($opt['option_id']);
                    }
                }
            }
        }

        $addonIds = $addonIds->unique()->values();
        $extraIds = $extraIds->unique()->values();
        $excludeIds = $excludeIds->unique()->values();
        $variationIds = $variationIds->unique()->values();
        $optionIds = $optionIds->unique()->values();

        // Eager load models with their locale translations
        $products = Product::with(['translations' => fn($q) => $q->where('locale', $locale)])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $addons = Addon::with(['translations' => fn($q) => $q->where('locale', $locale)])
            ->whereIn('id', $addonIds)
            ->get()
            ->keyBy('id');

        $extras = ExtraProduct::with(['translations' => fn($q) => $q->where('locale', $locale)])
            ->whereIn('id', $extraIds)
            ->get()
            ->keyBy('id');

        $excludes = ExcludeProduct::with(['translations' => fn($q) => $q->where('locale', $locale)])
            ->whereIn('id', $excludeIds)
            ->get()
            ->keyBy('id');

        $variations = VariationProduct::with(['translations' => fn($q) => $q->where('locale', $locale)])
            ->whereIn('id', $variationIds)
            ->get()
            ->keyBy('id');

        $options = OptionProduct::with(['translations' => fn($q) => $q->where('locale', $locale)])
            ->whereIn('id', $optionIds)
            ->get()
            ->keyBy('id');

        // Check TranslationTbl directly for key-based translations
        $allNames = collect()
            ->merge($products->pluck('name'))
            ->merge($addons->pluck('name'))
            ->merge($extras->pluck('name'))
            ->merge($excludes->pluck('name'))
            ->merge($variations->pluck('name'))
            ->merge($options->pluck('name'))
            ->filter()
            ->unique();

        $tblTranslations = TranslationTbl::where('locale', $locale)
            ->whereIn('key', $allNames)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('key')
            ->map(fn($rows) => $rows->first()->value);

        // Helper closure to resolve translated name
        $getTranslatedName = function ($model) use ($locale, $tblTranslations) {
            if (!$model) {
                return '';
            }
            // 1. Check morph relation
            $morphTrans = $model->translations?->where('locale', $locale)->first()?->value;
            if (!empty($morphTrans)) {
                return $morphTrans;
            }
            // 2. Check TranslationTbl by key
            if (isset($tblTranslations[$model->name])) {
                return $tblTranslations[$model->name];
            }
            // 3. Fallback to model name
            return $model->name ?? '';
        };

        // Format cart items
        $formattedCarts = $carts->map(function ($cart) use (
            $products,
            $addons,
            $extras,
            $excludes,
            $variations,
            $options,
            $getTranslatedName
        ) {
            $product = $products->get($cart->product_id);
            $productName = $getTranslatedName($product);

            $addonsFinalSum = 0;
            $addonsSum;
            $addonsSum = 0;
            $formattedAddons = collect($cart->addons ?? [])->map(function ($addonItem) use ($addons, $getTranslatedName, &$addonsFinalSum, &$addonsSum) {
                $addonId = $addonItem['addon_id'] ?? null;
                $addon = $addonId ? $addons->get($addonId) : null;
                $addonName = $getTranslatedName($addon);

                $price = isset($addonItem['price']) ? (float)$addonItem['price'] : 0;
                $tax = isset($addonItem['tax']) ? (float)$addonItem['tax'] : 0;
                $discount = isset($addonItem['discount']) ? (float)$addonItem['discount'] : 0;
                $finalPrice =  ($price + $tax - $discount);

                $addonsFinalSum += $finalPrice;
                $addonsSum += $price;


                return [
                    'addon_id' => $addonId,
                    'addon_name' => $addonName,
                    'name' => $addonName,
                    'price' => $price,
                    'tax' => $tax,
                    'discount' => $discount,
                    'final_price' => $finalPrice,
                ];
            })->values()->all();

            $extrasFinalSum = 0;
            $extrasSum = 0;
            $formattedExtras = collect($cart->extras ?? [])->map(function ($extraItem) use ($extras, $getTranslatedName, &$extrasFinalSum, &$extrasSum) {
                $extraId = $extraItem['extra_id'] ?? null;
                $extra = $extraId ? $extras->get($extraId) : null;
                $extraName = $getTranslatedName($extra);

                $price = isset($extraItem['price']) ? (float)$extraItem['price'] : 0;
                $tax = isset($extraItem['tax']) ? (float)$extraItem['tax'] : 0;
                $discount = isset($extraItem['discount']) ? (float)$extraItem['discount'] : 0;
                $finalPrice =  ($price + $tax - $discount);

                $extrasFinalSum += $finalPrice;
                $extrasSum += $price;

                return [
                    'extra_id' => $extraId,
                    'extra_name' => $extraName,
                    'name' => $extraName,
                    'price' => $price,
                    'tax' => $tax,
                    'discount' => $discount,
                    'final_price' => $finalPrice,
                ];
            })->values()->all();

            $formattedExclude = collect($cart->exclude ?? [])->map(function ($excludeItem) use ($excludes, $getTranslatedName) {
                $excludeId = $excludeItem['exclude_id'] ?? null;
                $exclude = $excludeId ? $excludes->get($excludeId) : null;
                $excludeName = $getTranslatedName($exclude);
 

                return [
                    'exclude_id' => $excludeId,
                    'exclude_name' => $excludeName,
                    'name' => $excludeName,
                    'price' => $price,
                    'tax' => $tax,
                    'discount' => $discount,
                    'final_price' => $finalPrice,
                ];
            })->values()->all();

            $optionsFinalSum = 0;
            $optionsSum = 0;
            $formattedVariation = collect($cart->variation ?? [])->map(function ($varItem) use ($variations, $options, $getTranslatedName, &$optionsFinalSum, &$optionsSum) {
                $varId = $varItem['variation_id'] ?? null;
                $var = $varId ? $variations->get($varId) : null;
                $varName = $getTranslatedName($var);

                $formattedOptions = collect($varItem['options'] ?? [])->map(function ($optItem) use ($options, $getTranslatedName, &$optionsFinalSum) {
                    $optId = $optItem['option_id'] ?? null;
                    $opt = $optId ? $options->get($optId) : null;
                    $optName = $getTranslatedName($opt);

                    $price = isset($optItem['price']) ? (float)$optItem['price'] : 0;
                    $tax = isset($optItem['tax']) ? (float)$optItem['tax'] : 0;
                    $discount = isset($optItem['discount']) ? (float)$optItem['discount'] : 0;
                    $finalPrice = ($price + $tax - $discount);

                    $optionsFinalSum += $finalPrice;
                    $optionsSum += $price;

                    return [
                        'option_id' => $optId,
                        'option_name' => $optName,
                        'name' => $optName,
                        'price' => $price,
                        'tax' => $tax,
                        'discount' => $discount,
                        'final_price' => $finalPrice,
                    ];
                })->values()->all();

                return [
                    'variation_id' => $varId,
                    'variation_name' => $varName,
                    'name' => $varName,
                    'options' => $formattedOptions,
                ];
            })->values()->all();

            // Product base final price
            $productFinalPrice = ((float)$cart->price + (float)$cart->tax - (float)$cart->discount);
            $productSum = (float)$cart->price;
            $total_price = $productSum  + $addonsSum + $extrasSum + $optionsSum;

            // Unit final price for this item (product + addons + extras + variation options)
            $unitFinalPrice = $productFinalPrice + $addonsFinalSum + $extrasFinalSum + $optionsFinalSum;

            // Quantity
            $quantity = (int)($cart->quantity ?? 1);
            if ($quantity < 1) {
                $quantity = 1;
            }

            // Total final price for this cart row (unit * quantity)
            $totalFinalPrice = $unitFinalPrice * $quantity;

            return [
                'id' => $cart->id,
                'type' => $cart->type,
                'product_id' => $cart->product_id,
                'product_name' => $productName,
                'name' => $productName,
                'price' => (float)$cart->price,
                'tax' => (float)$cart->tax,
                'discount' => (float)$cart->discount,
                'final_price' => (float)$cart->final_price,
                'total' => (float)$cart->total,
                'quantity' => $quantity,
                'notes' => $cart->notes,
                'addons' => $formattedAddons,
                'extras' => $formattedExtras,
                'exclude' => $formattedExclude,
                'variation' => $formattedVariation,
                'unit_final_price' => round($unitFinalPrice, 2),
                'total_final_price' => round($totalFinalPrice, 2),
                'total_price' => round($total_price, 2),
            ];
        });

        return response()->json([
            'success' => true,
            'carts' => $formattedCarts, 
            'count' => $formattedCarts->count(),
            'total' => round((float)$formattedCarts->sum('total_final_price'), 2),
            'total_price' => round((float)$formattedCarts->sum('total_price'), 2),
        ], 200);
    }

    /**
     * Update cart item (e.g. quantity or totals).
     */
    public function update_cart(Request $request, $id)
    {
        $user = $request->user() ?? auth()->user() ?? auth('sanctum')->user();
        $cashierManId = $user?->id ?? $request->cashier_man_id ?? $request->cahier_man_id;

        $cart = Cart::where('id', $id)
            ->when($cashierManId, fn($q) => $q->where('cashier_man_id', $cashierManId))
            ->first();

        if (!$cart) {
            return response()->json([
                'errors' => 'Cart item not found'
            ], 404);
        }

        $cart->update($request->only([
            'type',
            'price',
            'tax',
            'discount',
            'final_price',
            'total',
            'quantity',
            'notes',
            'addons',
            'extras',
            'exclude',
            'variation'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Cart item updated successfully',
            'cart' => $cart,
            'data' => $cart,
        ], 200);
    }

    /**
     * Delete cart item.
     */
    public function delete_cart(Request $request, $id)
    {
        $user = $request->user() ?? auth()->user() ?? auth('sanctum')->user();
        $cashierManId = $user?->id ?? $request->cashier_man_id ?? $request->cahier_man_id;

        $cart = Cart::where('id', $id)
            ->when($cashierManId, fn($q) => $q->where('cashier_man_id', $cashierManId))
            ->first();

        if (!$cart) {
            return response()->json([
                'errors' => 'Cart item not found'
            ], 404);
        }

        $cart->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart item deleted successfully'
        ], 200);
    }

    /**
     * Clear all carts for current cashier.
     */
    public function clear_cart(Request $request)
    {
        $user = $request->user() ?? auth()->user() ?? auth('sanctum')->user();
        $cashierManId = $user?->id ?? $request->cashier_man_id ?? $request->cahier_man_id;
        $cashierId = $user?->cashier_id ?? $request->cashier_id;

        $query = Cart::query();
        if ($cashierManId) {
            $query->where('cashier_man_id', $cashierManId);
        }
        if (!is_null($cashierId)) {
            $query->where('cashier_id', $cashierId);
        }
        if ($request->has('type') && !empty($request->type)) {
            $filterType = in_array($request->type, ['takeaway', 'take_away']) ? 'takeaway' : 'delivery';
            $query->where(function ($q) use ($filterType) {
                $q->where('type', $filterType);
                if ($filterType === 'takeaway') {
                    $q->orWhere('type', 'take_away');
                }
            });
        }

        $query->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully'
        ], 200);
    }
}
