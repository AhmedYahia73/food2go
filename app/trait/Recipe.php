<?php

namespace App\trait;

use App\Models\Product;
use App\Models\PurchaseStock;
use App\Models\VariationRecipe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait Recipe
{ 
    /**
     * Pull recipe ingredients from stock for products and their selected options
     *
     * @param array $products e.g. [['id' => 1, 'count' => 2, 'options' => [10, 25]]]
     * @param int $branch_id
     * @return array ['success' => bool, 'msg' => string]
     */
    public function pull_recipe($products, $branch_id)
    {
        if (empty($products) || !is_array($products)) {
            return [
                "success" => true,
            ];
        }

        // Map: stock_id => [ 'stock' => PurchaseStock, 'needed_quantity' => float, 'name' => string ]
        $neededStock = [];

        foreach ($products as $item) {
            if (empty($item["id"])) {
                continue;
            }

            $product = Product::where("id", $item["id"])
                ->with([
                    "unit:id,name",
                    "recipes" => function($q) {
                        $q->where("status", 1)->with(["unit:id,name", "store_product:id,name"]);
                    }
                ])
                ->first();

            if (empty($product)) { 
                return [
                    "success" => false,
                    "msg" => $item["id"] . " id is wrong"
                ];
            }

            $itemCount = max(0, (float)($item['count'] ?? 1));
            if ($itemCount <= 0) {
                continue;
            }

            // 1. Process Main Product Recipes
            if ($product->recipe && $product->recipes && $product->recipes->isNotEmpty()) {
                foreach ($product->recipes as $element) {
                    if (!$element->store_product_id) {
                        continue;
                    }

                    $stock = $this->getProductStockForBranch($element->store_product_id, $branch_id);
                    if (empty($stock)) { 
                        return [
                            "success" => false,
                            "msg" => "Recipe " . ($element?->store_product?->name ?? "item") . ' not enough',
                        ];
                    }

                    $weightPerUnit = (float)($element->weight ?? 0);
                    if ($weightPerUnit <= 0) {
                        $weightPerUnit = 1.0;
                    }

                    $recipeUnit = $element->unit;
                    if ($product->weight_status) {
                        $recipeUnit = $element->unit ?? $product->unit;
                    }

                    $deductedAmount = $this->calculateDeductedAmount($weightPerUnit, $itemCount, $recipeUnit, $stock->unit);

                    $stockId = $stock->id;
                    if (!isset($neededStock[$stockId])) {
                        $neededStock[$stockId] = [
                            'stock' => $stock,
                            'needed_quantity' => 0.0,
                            'name' => $element?->store_product?->name ?? $stock?->product?->name ?? 'item',
                        ];
                    }
                    $neededStock[$stockId]['needed_quantity'] += $deductedAmount;
                }
            }

            // 2. Process Option Recipes (VariationRecipe)
            $optionIds = $this->extractOptionIdsFromProductItem($item);

            if (!empty($optionIds)) {
                $variationRecipes = VariationRecipe::whereIn("option_id", $optionIds)
                    ->where("status", 1)
                    ->with(["unit:id,name", "store_product:id,name"])
                    ->get();

                foreach ($variationRecipes as $varRecipe) {
                    if (!$varRecipe->store_product_id) {
                        continue;
                    }

                    $stock = $this->getProductStockForBranch($varRecipe->store_product_id, $branch_id);
                    if (empty($stock)) {
                        return [
                            "success" => false,
                            "msg" => "Recipe " . ($varRecipe?->store_product?->name ?? "item") . ' not enough',
                        ];
                    }

                    // Weight is the weight to be pulled for ONE unit of this option
                    $weightPerUnit = (float)($varRecipe->weight ?? 0);
                    if ($weightPerUnit <= 0) {
                        $weightPerUnit = 1.0;
                    }

                    $deductedAmount = $this->calculateDeductedAmount($weightPerUnit, $itemCount, $varRecipe->unit, $stock->unit);

                    $stockId = $stock->id;
                    if (!isset($neededStock[$stockId])) {
                        $neededStock[$stockId] = [
                            'stock' => $stock,
                            'needed_quantity' => 0.0,
                            'name' => $varRecipe?->store_product?->name ?? $stock?->product?->name ?? 'item',
                        ];
                    }
                    $neededStock[$stockId]['needed_quantity'] += $deductedAmount;
                }
            }
        }

        // 3. Verify stock availability for ALL ingredients BEFORE deducting
        foreach ($neededStock as $entry) {
            $stock = $entry['stock'];
            $needed = $entry['needed_quantity'];

            if ($needed > $stock->quantity) {
                return [
                    "success" => false,
                    "msg" => "Recipe " . $entry['name'] . ' not enough',
                ];
            }
        }

        // 4. Deduct stock inside DB transaction
        DB::beginTransaction();
        try {
            foreach ($neededStock as $entry) {
                $stock = $entry['stock'];
                $needed = $entry['needed_quantity'];

                $stock->quantity -= $needed;
                $stock->actual_quantity -= $needed;
                $stock->save();
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Failed to pull recipe stock: " . $e->getMessage());
            return [
                "success" => false,
                "msg" => "Stock deduction failed: " . $e->getMessage(),
            ];
        }

        return [
            "success" => true,
        ]; 
    }

    /**
     * Find purchase stock for a raw product at a given branch
     */
    private function getProductStockForBranch($storeProductId, $branchId)
    {
        return PurchaseStock::where("product_id", $storeProductId)
            ->whereHas("store", function($query) use ($branchId) {
                $query->whereHas("branches", function($q) use ($branchId) {
                    $q->where("branches.id", $branchId);
                });
            })
            ->with(["unit:id,name", "product:id,name"])
            ->first();
    }

    /**
     * Calculate deducted weight based on weight per unit, count, and unit conversions
     */
    private function calculateDeductedAmount($weightPerUnit, $count, $recipeUnit, $stockUnit)
    {
        $baseWeight = (float)$weightPerUnit * (float)$count;

        $recipeUnitName = strtolower(trim($recipeUnit?->name ?? ''));
        $stockUnitName = strtolower(trim($stockUnit?->name ?? ''));

        // If same unit id or name
        if (($recipeUnit?->id && $stockUnit?->id && $recipeUnit->id == $stockUnit->id) || 
            ($recipeUnitName && $stockUnitName && $recipeUnitName === $stockUnitName)) {
            return $baseWeight;
        }

        // Weight: Kg vs Gram
        $isStockKg = in_array($stockUnitName, ['kg', 'kilogram', 'كجم', 'كيلو', 'كيلوجرام', 'كيلوغرام']);
        $isRecipeGram = in_array($recipeUnitName, ['gram', 'grams', 'g', 'جرام', 'غرام', 'جرامات', 'غرامات']);

        if ($isStockKg && $isRecipeGram) {
            return $baseWeight / 1000;
        }

        $isStockGram = in_array($stockUnitName, ['gram', 'grams', 'g', 'جرام', 'غرام', 'جرامات', 'غرامات']);
        $isRecipeKg = in_array($recipeUnitName, ['kg', 'kilogram', 'كجم', 'كيلو', 'كيلوجرام', 'كيلوغرام']);

        if ($isStockGram && $isRecipeKg) {
            return $baseWeight * 1000;
        }

        // Weight: Gram vs Milligram
        $isStockGram = in_array($stockUnitName, ['gram', 'grams', 'g', 'جرام', 'غرام']);
        $isRecipeMg = in_array($recipeUnitName, ['mg', 'milligram', 'ملجم']);
        if ($isStockGram && $isRecipeMg) {
            return $baseWeight / 1000;
        }
        if ($isStockMg && $isRecipeGram) {
            return $baseWeight * 1000;
        }

        // Volume: Liter vs Milliliter
        $isStockLiter = in_array($stockUnitName, ['l', 'liter', 'liters', 'لتر', 'ليتر']);
        $isRecipeMl = in_array($recipeUnitName, ['ml', 'milliliter', 'milliliters', 'مل', 'مليلتر']);

        if ($isStockLiter && $isRecipeMl) {
            return $baseWeight / 1000;
        }

        $isStockMl = in_array($stockUnitName, ['ml', 'milliliter', 'milliliters', 'مل', 'مليلتر']);
        $isRecipeLiter = in_array($recipeUnitName, ['l', 'liter', 'liters', 'لتر', 'ليتر']);

        if ($isStockMl && $isRecipeLiter) {
            return $baseWeight * 1000;
        }

        return $baseWeight;
    }

    /**
     * Extract option IDs from product item in various formats
     */
    private function extractOptionIdsFromProductItem($item)
    {
        $optionIds = [];

        // Direct options array: ['options' => [1, 2]] or ['options' => [['id' => 1]]]
        if (!empty($item['options'])) {
            foreach ($item['options'] as $opt) {
                $optId = is_object($opt) ? ($opt->id ?? null) : (is_array($opt) ? ($opt['id'] ?? $opt['option_id'] ?? null) : $opt);
                if ($optId) {
                    $optionIds[] = (int) $optId;
                }
            }
        }

        // Nested variations: ['variations' => [...]] or ['variation' => [...]]
        $variations = $item['variations'] ?? $item['variation'] ?? $item['variation_selected'] ?? [];
        if (!empty($variations)) {
            foreach ($variations as $var) {
                $options = is_object($var) ? ($var->options ?? []) : ($var['options'] ?? $var['option_id'] ?? []);
                foreach ($options as $opt) {
                    $optId = is_object($opt) ? ($opt->id ?? null) : (is_array($opt) ? ($opt['id'] ?? null) : $opt);
                    if ($optId) {
                        $optionIds[] = (int) $optId;
                    }
                }
            }
        }

        return array_values(array_unique(array_filter($optionIds)));
    }

    /**
     * Format order details items into products array for pull_recipe
     */
    public function formatOrderDetailsForRecipePull($orderDetails, $bundles = null)
    {
        $products = [];
        if (!empty($orderDetails)) {
            foreach ($orderDetails as $item) {
                $productItem = is_array($item) ? ($item['product'][0] ?? null) : ($item->product[0] ?? null);
                if (!$productItem) continue;

                $productId = is_array($productItem)
                    ? ($productItem['product']['id'] ?? $productItem['product']->id ?? null)
                    : ($productItem->product->id ?? $productItem->product['id'] ?? null);

                $productCount = is_array($productItem) ? ($productItem['count'] ?? 1) : ($productItem->count ?? 1);

                $optionIds = [];
                $variations = is_object($item) 
                    ? ($item->variations ?? $item->variation_selected ?? []) 
                    : ($item['variations'] ?? $item['variation_selected'] ?? []);

                if (!empty($variations)) {
                    foreach ($variations as $var) {
                        $opts = is_object($var) ? ($var->options ?? []) : ($var['options'] ?? []);
                        foreach ($opts as $opt) {
                            $optId = is_object($opt) ? ($opt->id ?? null) : (is_array($opt) ? ($opt['id'] ?? null) : $opt);
                            if ($optId) $optionIds[] = (int) $optId;
                        }
                    }
                }

                $products[] = [
                    "id" => $productId,
                    "count" => $productCount,
                    "options" => array_values(array_unique(array_filter($optionIds))),
                ];
            }
        }

        if (!empty($bundles)) {
            foreach ($bundles as $item) {
                $bundleId = is_array($item) ? ($item['id'] ?? null) : ($item->id ?? null);
                $bundleCount = is_array($item) ? ($item['count'] ?? 1) : ($item->count ?? 1);
                if (!$bundleId) continue;

                $bundle = \App\Models\Bundle::where("id", $bundleId)->with("products")->first();
                $bundleProducts = $bundle?->products ?? [];

                foreach ($bundleProducts as $element) {
                    $products[] = [
                        "id" => $element->id,
                        "count" => $bundleCount,
                        "options" => [],
                    ];
                }
            }
        }

        return $products;
    }
}
