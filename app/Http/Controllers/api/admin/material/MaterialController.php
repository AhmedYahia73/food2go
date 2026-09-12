<?php

namespace App\Http\Controllers\api\admin\material;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\MaterialCategory;
use App\Models\MaterialStock;
use App\Models\Material;
use App\Models\Purchase;
use App\Models\PurchaseStore;
use App\Models\Unit;

class MaterialController extends Controller
{
    public function __construct(private Material $product,
    private MaterialCategory $categories, private MaterialStock $stock){}

    public function view(Request $request){
        $product = $this->product
        ->with("category", "start_stock.store")
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'status' => $item->status,
                'category_id' => $item->category_id,
                'category' => $item?->category?->name,
                'min_stock' => $item->min_stock,
                'start_stock' => $item->start_stock
                ->map(function($element){
                    return [
                        "id" => $element->id,
                        "start_stock" => $element->start_stock,
                        "cost" => $element->cost,
                        "unit" => $element->unit?->name,
                        "store" => $element->store?->name,
                    ];
                }),
            ];
        }); 
        $categories = $this->categories
        ->select('id', 'name', 'category_id')
        ->where('status', 1)
        ->get();

        return response()->json([
            'materials' => $product,
            'categories' => $categories,
        ]);
    }

    public function lists(Request $request){
        $stores = PurchaseStore::
        select("id", "name")
        ->get();
        $units = Unit::
        select("id", "name")
        ->where("status", 1)
        ->get();

        return response()->json([
            "stores" => $stores,
            "units" => $units,
        ]);
    }

    public function material_stock(Request $request){

        $validator = Validator::make($request->all(), [
            'store_id' => ['required', 'exists:purchase_stores,id'], 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 400);
        }

        $storeId = $request->store_id;

        // 1. جلب المخزون لكل المنتجات في هذا المتجر
        $storeStocks = MaterialStock::where("store_id", $storeId)
            ->pluck('quantity', 'material_id');

        // 2. جلب كل المشتريات للمتجر مرتبة من الأحدث للأقدم مرة واحدة
        // استخدمنا select لجلب الحقول المطلوبة فقط لتوفير الميموري (الرامات)
        $allPurchases = Purchase::where("store_id", $storeId) // تأكد أن اسم الحقل في الداتا بيز quintity كما كتبته أنت
            ->orderByDesc("created_at")
            ->get()
            ->groupBy('material_id'); // تجميع المشتريات لكل منتج معاً

        // 3. جلب المنتجات مع الأقسام وحساب التكلفة
        $products = $this->product->with('category')->get()->map(function($item) use($storeStocks, $allPurchases) {
            
            // المخزون الحالي للمنتج
            $stock = $storeStocks[$item->id] ?? 0; 
            
            // مشتريات هذا المنتج (مرتبة من الأحدث للأقدم بفضل استعلام الداتا بيز)
            $productPurchases = $allPurchases[$item->id] ?? collect();
            
            // حساب آخر تكلفة شراء
            $lastPurchase = $productPurchases->first();
            $lastCost = ($lastPurchase && $lastPurchase->quintity > 0) 
                ? ($lastPurchase->total_coast / $lastPurchase->quintity) 
                : 0;

            // ==========================================
            // اللوجيك الجديد لحساب متوسط تكلفة المخزون المتبقي
            // ==========================================
            $cost = 0;
            
            if ($stock > 0 && $productPurchases->isNotEmpty()) {
                $remainingStockToValuate = $stock; // الكمية التي نحتاج حساب تكلفتها
                $totalValueOfStock = 0; // إجمالي قيمة المخزون

                foreach ($productPurchases as $purchase) {
                    // إذا خلصنا حساب كل المخزون، نوقف اللوب
                    if ($remainingStockToValuate <= 0) break; 
                    
                    // لتجنب القسمة على صفر إذا كانت كمية الشراء صفر بالخطأ
                    if ($purchase->quintity <= 0) continue; 

                    // سعر القطعة في هذه الفاتورة
                    $unitPrice = $purchase->total_coast / $purchase->quintity;

                    // الكمية التي سنأخذها من هذه الفاتورة (إما كمية الفاتورة كلها، أو ما تبقى من المخزون)
                    $qtyToTakeFromPurchase = min($remainingStockToValuate, $purchase->quintity);

                    // إضافة قيمة هذه الكمية لإجمالي قيمة المخزون
                    $totalValueOfStock += ($qtyToTakeFromPurchase * $unitPrice);

                    // خصم الكمية التي حسبناها من إجمالي المخزون المراد حساب تكلفته
                    $remainingStockToValuate -= $qtyToTakeFromPurchase;
                }

                // الكمية الفعلية التي تم تسعيرها (في حال كان المخزون الفعلي أكبر من كل كميات الشراء المسجلة)
                $actualValuatedQty = $stock - $remainingStockToValuate;

                // متوسط التكلفة للقطعة الواحدة من المخزون المتبقي
                if ($actualValuatedQty > 0) {
                    $cost = $totalValueOfStock / $actualValuatedQty;
                }
            }

            return [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'status' => $item->status,
                'category_id' => $item->category_id,
                'category' => $item->category?->name,
                'min_stock' => $item->min_stock,
                'stock' => $stock,
                'cost' => round($cost, 2), // متوسط التكلفة بناءً على المتبقي فقط
                'last_cost' => round($lastCost, 2), // سعر آخر قطعة تم شراؤها
                'total_cost' => round($cost * $stock, 2),
                'total_last_cost' => round($lastCost * $stock, 2),
            ];
        }); 

        // 4. جلب الأقسام
        $categories = $this->categories
            ->select('id', 'name', 'category_id')
            ->where('status', 1)
            ->get();

        return response()->json([
            'products' => $products,
            'categories' => $categories,
            'total_cost' => round($products->sum('total_cost'), 2),
            'total_last_cost' => round($products->sum('total_last_cost'), 2),
        ]);
    }
    
    public function product(Request $request, $id){ 
        $product = $this->product
        ->where('id', $id)
        ->with('category:id,name', "start_stock.store")
        ->first();

        return response()->json([
            'material' => $product,
        ]);
    }

    public function status(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'boolean'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $this->product
        ->where('id', $id)
        ->update([
            'status' => $request->status
        ]);

        return response()->json([
            'status' => $request->status ? 'active' : 'banned'
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'description' => ['sometimes'],
            'status' => ['required', 'boolean'],
            'category_id' => ['required', 'exists:material_categories,id'],
            'min_stock' => ['sometimes', 'numeric'],
            'matrial_store' => ["array"],
            'matrial_store.*.start_stock' => ["required", "numeric"],
            'matrial_store.*.cost' => ["required", "numeric"],
            'matrial_store.*.unit_id' => ["required", "exists:units,id"], 
            'matrial_store.*.store_id' => ["required", "exists:purchase_stores,id"], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $productRequest = $validator->validated();
        $product = $this->product
        ->create($productRequest);
        $matrial_store = $request->matrial_store ?? [];
        foreach ($matrial_store as $item) {
            $product->start_stock()
            ->create([
                "start_stock" => $item['start_stock'],
                "cost" => $item['cost'],
                "unit_id" => $item['unit_id'],
                "store_id" => $item['store_id'],
            ]);
        }

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'description' => ['sometimes'],
            'status' => ['required', 'boolean'],
            'category_id' => ['required', 'exists:material_categories,id'],
            'min_stock' => ['sometimes', 'numeric'],
            'matrial_store' => ["array"],
            'matrial_store.*.start_stock' => ["required", "numeric"],
            'matrial_store.*.cost' => ["required", "numeric"],
            'matrial_store.*.unit_id' => ["required", "exists:units,id"], 
            'matrial_store.*.store_id' => ["required", "exists:purchase_stores,id"],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $productRequest = $validator->validated();
        $product = $this->product
        ->where('id', $id)
        ->first();
        $product->update($productRequest);
        $matrial_store = $request->matrial_store ?? [];
        $product->start_stock()->delete();
        foreach ($matrial_store as $item) {
            $product->start_stock()
            ->create([
                "start_stock" => $item['start_stock'],
                "cost" => $item['cost'],
                "unit_id" => $item['unit_id'],
                "store_id" => $item['store_id'],
            ]);
        }

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete(Request $request, $id){
        $this->product
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
