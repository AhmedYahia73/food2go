<?php

namespace App\Http\Controllers\api\customer\make_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ProductCart;
use App\Models\VariationCart;
use App\Models\OptionCart;
use App\Models\AddonCart;
use App\Models\ExtraCart;
use App\Models\Product;

class CartController extends Controller
{
    public function getCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required_without:address_id',
            'address_id' => 'required_without:branch_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userId = auth()->id();
        $locale = $request->locale;
        
        $branch_id = 0;
        $module = "delivery";
        if ($request->branch_id && !empty($request->branch_id)) {
            $branch_id = $request->branch_id;
            $module = "take_away";
        }
        if ($request->address_id && !empty($request->address_id)) {
            $address = \App\Models\Address::find($request->address_id);
            $branch_id = $address?->zone?->branch_id;
        }

        $carts = ProductCart::with([
            'product.translations', 'product.tax.tax_module.module',
            'product.discount' => fn($q) => $q->where(fn($d) => $d->whereJsonContains("module", "app")->orWhereJsonContains("module", "all")),
            'product.product_pricing' => fn($q) => $q->where('branch_id', $branch_id),
            'variations_cart.variation.translations', 
            'variations_cart.options_cart.option.translations', 
            'variations_cart.options_cart.option.option_pricing' => fn($q) => $q->where('branch_id', $branch_id),
            'addons_cart.addon.translations', 'addons_cart.addon.tax',
            'extras_cart.extra.translations',
            'extras_cart.extra.pricing',
        ])
        ->where('user_id', $userId)
        ->get();

        $products = [];
        $cart_total_price = 0;
        $cart_total_tax = 0;
        $cart_total_discount = 0;
        
        $getTranslation = function($model, $field = 'name') use ($locale) {
            if (!$model) return '';
            if (!$locale) return $model->$field ?? $model->name;
            $translation = $model->translations->where('locale', $locale)->where('column_name', $field)->first()
                        ?? $model->translations->where('locale', $locale)->first();
            return $translation ? $translation->value : ($model->$field ?? $model->name);
        };
        
        foreach($carts as $cart) {
            $product = $cart->product;
            if(!$product) continue;

            // Product Base Price & Branch Pricing
            $product_price = $product->product_pricing->first()?->price ?? $product->price;
            
            // Tax Module filtering logic
            $tax_module = $product->tax_module?->map(function ($taxItem) use ($module, $branch_id) {
                $isFound = $taxItem->module
                ->where('module', $module) 
                ->whereIn('app_type', ['online', 'all'])
                ->where("branch_id", $branch_id);
                if($isFound->count() > 0){
                    return $taxItem->tax;
                }
            })->filter()->first();
            
            $product->tax = !empty($tax_module) ? $tax_module : $product->tax;

            // Discount filtering
            $my_discount = $product->discount?->start_date <= date("Y-m-d") && $product->discount?->end_date >= date("Y-m-d") ? $product->discount : null;

            $product_tax_val = 0;
            $product_discount_val = 0;
            
            if ($product->taxes?->setting == 'included') {
                if (!empty($my_discount)) {
                    if ($my_discount->type == 'precentage') {
                        $discounted_price = $product_price - $my_discount->amount * $product_price / 100;
                    } else {
                        $discounted_price = $product_price - $my_discount->amount;
                    }
                    $price_with_tax = empty($product->tax) ? $discounted_price : 
                    ($product->tax->type == 'value' ? $discounted_price + $product->tax->amount 
                    : $discounted_price + $product->tax->amount * $discounted_price / 100);
                }
                else {
                    $discounted_price = $product_price;
                    $price_with_tax = empty($product->tax) ? $discounted_price : 
                    ($product->tax->type == 'value' ? $discounted_price + $product->tax->amount 
                    : $discounted_price + $product->tax->amount * $discounted_price / 100);
                }
                $product_tax_val = $price_with_tax - $discounted_price; 
                $product_discount_val = $product_price - $discounted_price; 
                $base_product_price = $price_with_tax;
            } else {
                if (!empty($my_discount)) {
                    if ($my_discount->type == 'precentage') {
                        $discounted_price = $product_price - $my_discount->amount * $product_price / 100;
                    } else {
                        $discounted_price = $product_price - $my_discount->amount;
                    }
                } else {
                    $discounted_price = $product_price;
                }
                
                if (!empty($product->tax)) {
                    if ($product->tax->type == 'precentage') {
                        $tax_amt = $discounted_price + $product->tax->amount * $discounted_price / 100;
                    } else {
                        $tax_amt = $discounted_price + $product->tax->amount;
                    }
                } else {
                    $tax_amt = $discounted_price;
                }
                $product_tax_val = $tax_amt - $discounted_price;
                $product_discount_val = $product_price - $discounted_price;
                $base_product_price = $product_price;
            }

            $variations = [];
            $addons = [];
            $extras = [];
            $options_total_price = 0;
            $addon_total_tax = 0;
            $addon_total_discount = 0;
            $addon_total_price = 0;
            $extra_total_price = 0;
            $extra_total_tax = 0;
            
            // Process Addons
            foreach($cart->addons_cart as $addon_cart) {
                $addon = $addon_cart->addon;
                if(!$addon) continue;
                
                $addon_price = $addon->price;
                $addon_tax_val = 0;
                $addon_discount_val = 0;
                
                if ($addon->taxes?->setting == 'included') {
                    $addon_price_with_tax = empty($addon->tax) ? $addon_price: 
                    ($addon->tax->type == 'value' ? $addon_price + $addon->tax->amount : $addon_price + $addon->tax->amount * $addon_price / 100);

                    $addon_tax_val = $addon_price_with_tax - $addon_price;
                }
                else {
                    if (!empty($addon->tax)) {
                        if ($addon->tax->type == 'precentage') {
                            $tax_amt = $addon_price + $addon->tax->amount * $addon_price / 100;
                        } else {
                            $tax_amt = $addon_price + $addon->tax->amount;
                        }
                    }
                    else{
                        $tax_amt = $addon_price;
                    }
                    
                    $addon_tax_val = $tax_amt - $addon_price;
                }

                $addon_total_tax += ($addon_tax_val * $addon_cart->quantity);
                $addon_total_discount += ($addon_discount_val * $addon_cart->quantity);
                $addon_total_price += ($addon_price * $addon_cart->quantity);

                $addons[] = [
                    'id' => $addon_cart->addon_id,
                    'name' => $getTranslation($addon),
                    'price' => $addon_price * $addon_cart->quantity,
                    'quantity' => $addon_cart->quantity,
                    'tax_val' => $addon_tax_val,
                    'discount_val' => $addon_discount_val,
                ];
            }
            
            // Process Variations & Options
            $options_total_tax = 0;
            foreach($cart->variations_cart as $var_cart) {
                $options = [];
                foreach($var_cart->options_cart as $opt_cart) {
                    $opt = $opt_cart->option;
                    if($opt) {
                        $opt_price = $opt->option_pricing->first()?->price ?? $opt->price;
                        $opt_tax_val = 0;

                        if ($product->taxes?->setting == 'included') {
                            if (!empty($product->tax)) {
                                if ($product->tax->type == 'precentage') {
                                    $opt_tax_val = $opt_price - ($opt_price / (1 + ($product->tax->amount / 100)));
                                    $opt_price_without_tax = $opt_price - $opt_tax_val;
                                    // Wait, backend logic usually just treats included tax as simple percentage, 
                                    // but let's stick to what the original code did for included:
                                    // ($discounted_price + $product->tax->amount * $discounted_price / 100) - $discounted_price
                                    // Wait, the original code added tax to price_with_tax instead of extracting it! 
                                    // Let's extract it properly as it is included
                                    $opt_tax_val = $opt_price - ($opt_price / (1 + ($product->tax->amount / 100)));
                                }
                            }
                        } else {
                            if (!empty($product->tax)) {
                                if ($product->tax->type == 'precentage') {
                                    $opt_tax_val = $opt_price * $product->tax->amount / 100;
                                }
                            }
                        }

                        $options_total_tax += ($opt_tax_val * $opt_cart->quantity);
                        $options_total_price += ($opt_price * $opt_cart->quantity);
                        $options[] = [
                            'id' => $opt_cart->option_id,
                            'name' => $getTranslation($opt),
                            'quantity' => $opt_cart->quantity,
                            'price' => $opt_price,
                            'tax_val' => $opt_tax_val,
                        ];
                    }
                }
                $variations[] = [
                    'id' => $var_cart->variation_id,
                    'name' => $getTranslation($var_cart->variation),
                    'options' => $options
                ];
            }

            // Process Extras
            foreach($cart->extras_cart as $extra_cart) {
                $extra = $extra_cart->extra;
                if(!$extra) continue;

                // Use branch-specific pricing if available
                $extra_price = $extra->pricing->first()?->price ?? $extra->price;
                $extra_tax_val = 0;

                // Apply tax if product has tax (extras share product tax)
                if (!empty($product->tax)) {
                    if ($product->tax->type == 'precentage') {
                        $extra_tax_val = $extra_price * $product->tax->amount / 100;
                        $extra_price_after_tax = $extra_price + $extra_tax_val;
                    } else {
                        $extra_tax_val = $product->tax->amount;
                        $extra_price_after_tax = $extra_price + $extra_tax_val;
                    }
                } else {
                    $extra_price_after_tax = $extra_price;
                }

                $extra_total_price += ($extra_price_after_tax * $extra_cart->quantity);
                $extra_total_tax += ($extra_tax_val * $extra_cart->quantity);

                $extras[] = [
                    'id' => $extra_cart->extra_id,
                    'name' => $getTranslation($extra),
                    'price' => $extra_price_after_tax,
                    'quantity' => $extra_cart->quantity,
                    'tax_val' => $extra_tax_val,
                ];
            }
            
            // Total: (base_product + options) × qty + addons + extras
            $product_total = $cart->quantity * ($options_total_price + $base_product_price) + $addon_total_price + $extra_total_price;
            
            $total_tax = ($product_tax_val * $cart->quantity) + $options_total_tax + $addon_total_tax + $extra_total_tax;
            $total_discount = ($product_discount_val * $cart->quantity) + $addon_total_discount;

            $cart_total_price += $product_total;
            $cart_total_tax += $total_tax;
            $cart_total_discount += $total_discount;
            
            $products[] = [
                'cart_id' => $cart->id,
                'id' => $cart->product_id,
                'name' => $getTranslation($cart->product),
                'description' => $getTranslation($cart->product, 'description'),
                'image_link' => $cart->product->image_link,
                'price' => $product_price,
                'discount_val' => $product_discount_val,
                'total_price' => $product_total,
                'total_tax' => $total_tax,
                'total_discount' => $total_discount,
                'note' => $cart->note,
                'quantity' => $cart->quantity,
                'variations' => $variations,
                'addons' => $addons,
                'extras' => $extras,
            ];
        }

        return response()->json([
            'products' => $products,
            'cart_summary' => [
                'total_price' => $cart_total_price,
                'total_tax' => $cart_total_tax,
                'total_discount' => $cart_total_discount,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.note' => 'nullable|string',
            
            'products.*.variations' => 'nullable|array',
            'products.*.variations.*.id' => 'required|exists:variation_products,id',
            
            'products.*.variations.*.options' => 'required|array',
            'products.*.variations.*.options.*.id' => 'required|exists:option_products,id',
            'products.*.variations.*.options.*.quantity' => 'required|integer|min:1',
            
            'products.*.addons' => 'nullable|array',
            'products.*.addons.*.id' => 'required|exists:addons,id',
            'products.*.addons.*.quantity' => 'required|integer|min:1',

            'products.*.extras' => 'nullable|array',
            'products.*.extras.*.id' => 'required|exists:extra_products,id',
            'products.*.extras.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userId = auth()->id();
        
        foreach ($request->products as $productData) {
            $this->createProductCart($userId, $productData);
        }

        return response()->json(['message' => 'Added to cart successfully']);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'products' => 'required|array',
            'products.0.id' => 'required|exists:products,id',
            'products.0.quantity' => 'required|integer|min:1',
            'products.0.note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userId = auth()->id();
        $cart = ProductCart::where('user_id', $userId)->find($id);

        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }

        // Delete existing relations
        foreach ($cart->variations_cart as $var) {
            $var->options_cart()->delete();
        }
        $cart->variations_cart()->delete();
        $cart->addons_cart()->delete();
        $cart->extras_cart()->delete();
        $cart->delete();

        // Recreate it using the new data
        $productData = $request->products[0];
        $this->createProductCart($userId, $productData);

        return response()->json(['message' => 'Cart updated successfully']);
    }

    public function destroy($id)
    {
        $userId = auth()->id();
        $cart = ProductCart::where('user_id', $userId)->find($id);

        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }

        $cart->delete();
        
        return response()->json(['message' => 'Cart item deleted successfully']);
    }

    private function createProductCart($userId, $productData)
    {
        $cart = ProductCart::create([
            'user_id' => $userId,
            'product_id' => $productData['id'],
            'quantity' => $productData['quantity'],
            'note' => $productData['note'] ?? null,
        ]);

        if (!empty($productData['variations'])) {
            foreach ($productData['variations'] as $variationData) {
                $variation = VariationCart::create([
                    'product_cart_id' => $cart->id,
                    'variation_id' => $variationData['id'],
                    'product_id' => $productData['id'],
                ]);

                if (!empty($variationData['options'])) {
                    foreach ($variationData['options'] as $optionData) {
                        OptionCart::create([
                            'variation_cart_id' => $variation->id,
                            'option_id' => $optionData['id'],
                            'variation_id' => $variationData['id'],
                            'product_id' => $productData['id'],
                            'quantity' => $optionData['quantity'],
                        ]);
                    }
                }
            }
        }

        if (!empty($productData['addons'])) {
            foreach ($productData['addons'] as $addonData) {
                AddonCart::create([
                    'product_cart_id' => $cart->id,
                    'addon_id' => $addonData['id'],
                    'product_id' => $productData['id'],
                    'quantity' => $addonData['quantity'],
                ]);
            }
        }

        // Save extras
        if (!empty($productData['extras'])) {
            foreach ($productData['extras'] as $extraData) {
                ExtraCart::create([
                    'product_cart_id' => $cart->id,
                    'extra_id' => $extraData['id'],
                    'product_id' => $productData['id'],
                    'quantity' => $extraData['quantity'],
                ]);
            }
        }
    }
}
