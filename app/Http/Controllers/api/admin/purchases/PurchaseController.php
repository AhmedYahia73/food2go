<?php

namespace App\Http\Controllers\api\admin\purchases;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\trait\image;

use App\Models\Purchase;
use App\Models\PurchaseCategory;
use App\Models\PurchaseProduct;
use App\Models\PurchaseStore; 
use App\Models\FinantiolAcounting;
use App\Models\PurchaseStock;
use App\Models\MaterialCategory;
use App\Models\MaterialStock;
use App\Models\Material;
use App\Models\Unit;
use App\Models\Supplier;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceFinancial;
use App\Models\PurchaseMaterial;
use App\Models\PurchaseProductItem;

class PurchaseController extends Controller
{
    use image;

    public function __construct(
        private Purchase $purchases,
        private PurchaseProduct $products,
        private PurchaseCategory $categories,
        private PurchaseStore $stores,
        private FinantiolAcounting $financial,
        private Material $materials,
        private PurchaseStock $stock,
        private Unit $units,
        private MaterialCategory $material_categories,
        private MaterialStock $material_stock,
        private Supplier $suppliers,
        private PurchaseInvoice $purchase_invoices,
        private PurchaseInvoiceFinancial $purchase_invoice_financials,
        private PurchaseMaterial $purchase_materials,
        private PurchaseProductItem $purchase_product_items,
    ){}

    public function view(Request $request){
        $perPage = (int) $request->get('per_page', 10);
        $paginated = $this->purchases
            ->with([
                'admin:id,name',
                'store:id,name',
                'supplier:id,name',
                'materials.material:id,name',
                'materials.category:id,name',
                'materials.unit:id,name',
                'products.product:id,name',
                'products.category:id,name',
                'products.unit:id,name',
                'invoices.financials.financial:id,name',
            ])
            ->latest()
            ->paginate($perPage);

        $purchases = collect($paginated->items())->map(function($item){
            $type = $item->type;
            if ($item->materials->isNotEmpty() && $item->products->isNotEmpty()) {
                $type = 'all';
            } elseif ($item->materials->isNotEmpty()) {
                $type = 'material';
            } elseif ($item->products->isNotEmpty()) {
                $type = 'product';
            }

            return [
                'id' => $item->id,
                'type' => $type,
                'total_coast' => (float) $item->total_coast,
                'payment' => (float) ($item->payment ?? 0),
                'due' => (float) ($item->due ?? 0),
                'quintity' => (float) ($item->quintity ?? 0),
                'date' => $item->date,
                'receipt_link' => $item->receipt_link,
                'admin_id' => $item->admin_id,
                'admin' => $item?->admin?->name,
                'store_id' => $item->store_id,
                'store' => $item?->store?->name,
                'supplier_id' => $item->supplier_id,
                'supplier' => $item?->supplier?->name,
                'materials' => $item->materials->map(function($m){
                    return [
                        'id' => $m->id,
                        'material_id' => $m->material_id,
                        'material' => $m->material?->name,
                        'category_material_id' => $m->category_material_id,
                        'category' => $m->category?->name,
                        'unit_id' => $m->unit_id,
                        'unit' => $m->unit?->name,
                        'count' => (float) ($m->count ?? 1),
                    ];
                }),
                'products' => $item->products->map(function($p){
                    return [
                        'id' => $p->id,
                        'product_id' => $p->product_id,
                        'product' => $p->product?->name,
                        'category_id' => $p->category_id,
                        'category' => $p->category?->name,
                        'unit_id' => $p->unit_id,
                        'unit' => $p->unit?->name,
                        'count' => (float) ($p->count ?? 1),
                    ];
                }),
                'invoices' => $item->invoices->map(function($invoice){
                    return [
                        'id' => $invoice->id,
                        'payment' => (float) $invoice->payment,
                        'due' => (float) $invoice->due,
                        'date' => $invoice->date,
                        'created_at' => $invoice->created_at?->format('Y-m-d H:i'),
                        'financials' => $invoice->financials->map(function($f){
                            return [
                                'id' => $f->id,
                                'financial_id' => $f->financial_id,
                                'name' => $f->financial?->name ?? '-',
                                'amount' => (float) $f->amount,
                            ];
                        }),
                    ];
                }),
            ];
        });

        return response()->json([
            'purchases' => $purchases,
            'pagination' => [
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
            ],
        ]);
    }

    public function lists(Request $request){
        $categories = $this->categories
            ->select('id', 'name', 'category_id')
            ->where('status', 1)
            ->get();
        $products = $this->products
            ->select('id', 'name', 'category_id')
            ->where('status', 1)
            ->get();
        $stores = $this->stores
            ->select('id', 'name')
            ->where('status', 1)
            ->get();
        $financials = $this->financial
            ->select('id', 'name', 'logo')
            ->where('status', 1)
            ->get();
        $units = $this->units
            ->select("name", "id")
            ->where("status", 1)
            ->get();
        $material_categories = $this->material_categories
            ->select("name", "id", 'category_id')
            ->where("status", 1)
            ->get();
        $materials = $this->materials
            ->select("name", "id", 'category_id')
            ->where("status", 1)
            ->get();
        $suppliers = $this->suppliers
            ->select("name", "id")
            ->where("status", 1)
            ->get();
        $types = [
            "material",
            "product",
            "all",
        ];

        return response()->json([ 
            'categories' => $categories,
            'products' => $products,
            'stores' => $stores,
            'financials' => $financials,
            'units' => $units,
            'material_categories' => $material_categories,
            'materials' => $materials,
            'suppliers' => $suppliers,
            'types' => $types,
        ]);
    }

    public function purchase_item(Request $request, $id){
        $purchase = $this->purchases
            ->with([
                'admin:id,name',
                'store:id,name',
                'supplier:id,name',
                'materials.material:id,name',
                'materials.category:id,name',
                'materials.unit:id,name',
                'products.product:id,name',
                'products.category:id,name',
                'products.unit:id,name',
                'invoices.financials.financial:id,name',
            ])
            ->where('id', $id)
            ->first();

        if (!$purchase) {
            return response()->json(['errors' => 'Purchase not found'], 404);
        }

        $type = $purchase->type;
        if ($purchase->materials->isNotEmpty() && $purchase->products->isNotEmpty()) {
            $type = 'all';
        } elseif ($purchase->materials->isNotEmpty()) {
            $type = 'material';
        } elseif ($purchase->products->isNotEmpty()) {
            $type = 'product';
        }

        return response()->json([
            'purchase' => [
                'id' => $purchase->id,
                'type' => $type,
                'total_coast' => (float) $purchase->total_coast,
                'payment' => (float) ($purchase->payment ?? 0),
                'due' => (float) ($purchase->due ?? 0),
                'quintity' => (float) ($purchase->quintity ?? 0),
                'date' => $purchase->date,
                'receipt_link' => $purchase->receipt_link,
                'admin_id' => $purchase->admin_id,
                'admin' => $purchase?->admin?->name,
                'store_id' => $purchase->store_id,
                'store' => $purchase?->store?->name,
                'supplier_id' => $purchase->supplier_id,
                'supplier' => $purchase?->supplier?->name,
                'materials' => $purchase->materials->map(function($m){
                    return [
                        'id' => $m->id,
                        'material_id' => $m->material_id,
                        'material' => $m->material?->name,
                        'category_material_id' => $m->category_material_id,
                        'category' => $m->category?->name,
                        'unit_id' => $m->unit_id,
                        'unit' => $m->unit?->name,
                        'count' => (float) ($m->count ?? 1),
                    ];
                }),
                'products' => $purchase->products->map(function($p){
                    return [
                        'id' => $p->id,
                        'product_id' => $p->product_id,
                        'product' => $p->product?->name,
                        'category_id' => $p->category_id,
                        'category' => $p->category?->name,
                        'unit_id' => $p->unit_id,
                        'unit' => $p->unit?->name,
                        'count' => (float) ($p->count ?? 1),
                    ];
                }),
                'invoices' => $purchase->invoices->map(function($invoice){
                    return [
                        'id' => $invoice->id,
                        'payment' => (float) $invoice->payment,
                        'due' => (float) $invoice->due,
                        'date' => $invoice->date,
                        'created_at' => $invoice->created_at?->format('Y-m-d H:i'),
                        'financials' => $invoice->financials->map(function($f){
                            return [
                                'id' => $f->id,
                                'financial_id' => $f->financial_id,
                                'name' => $f->financial?->name ?? '-',
                                'amount' => (float) $f->amount,
                            ];
                        }),
                    ];
                }),
                'financials' => $purchase->invoices->flatMap(function($inv) {
                    return $inv->financials->map(function($f) {
                        return [
                            'id' => $f->financial_id,
                            'amount' => (float) $f->amount,
                            'name' => $f->financial?->name ?? '-',
                        ];
                    });
                })->values(),
            ],
        ]);
    }

    public function create(Request $request){
        $products = is_array($request->products) ? $request->products : [];
        $materials = is_array($request->materials) ? $request->materials : [];

        // Backward compatibility: items array
        if (empty($products) && empty($materials) && is_array($request->items)) {
            foreach ($request->items as $it) {
                if (($it['type'] ?? $request->type) === 'material') {
                    $materials[] = $it;
                } else {
                    $products[] = $it;
                }
            }
        } elseif (empty($products) && empty($materials)) {
            if ($request->type === 'material' && !empty($request->material_id)) {
                $materials[] = [
                    'item_id' => $request->material_id,
                    'unit_id' => $request->unit_id,
                    'count' => $request->quintity ?? 1,
                ];
            } elseif (!empty($request->product_id)) {
                $products[] = [
                    'item_id' => $request->product_id,
                    'unit_id' => $request->unit_id,
                    'count' => $request->quintity ?? 1,
                ];
            }
        }

        if (empty($products) && empty($materials)) {
            return response()->json([
                'errors' => ['items' => ['Please add at least one product or material.']],
            ], 400);
        }

        $request->merge([
            'products' => $products,
            'materials' => $materials,
        ]);

        $validator = Validator::make($request->all(), [
            'store_id' => ['required', 'exists:purchase_stores,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'total_coast' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'receipt' => ['nullable'],

            'products' => ['nullable', 'array'],
            'products.*.item_id' => ['required_with:products', 'exists:purchase_products,id'],
            'products.*.unit_id' => ['nullable', 'exists:units,id'],
            'products.*.count' => ['nullable', 'numeric', 'min:0.01'],

            'materials' => ['nullable', 'array'],
            'materials.*.item_id' => ['required_with:materials', 'exists:materials,id'],
            'materials.*.unit_id' => ['nullable', 'exists:units,id'],
            'materials.*.count' => ['nullable', 'numeric', 'min:0.01'],

            'financial' => ['nullable', 'array'],
            'financial.*.id' => ['required_with:financial', 'exists:finantiol_acountings,id'],
            'financial.*.amount' => ['required_with:financial', 'numeric', 'min:0.01'],

            'due_invoices' => ['nullable', 'array'],
            'due_invoices.*.due' => ['required_with:due_invoices', 'numeric', 'min:0.01'],
            'due_invoices.*.date' => ['required_with:due_invoices', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 400);
        }

        $totalCost = (float) $request->total_coast;
        $totalPayment = 0;
        if (!empty($request->financial)) {
            foreach ($request->financial as $f) {
                $totalPayment += (float) ($f['amount'] ?? 0);
            }
        }
        $due = max(0, $totalCost - $totalPayment);

        $totalQuantity = 0;
        foreach ($products as $p) {
            $totalQuantity += (float) ($p['count'] ?? 1);
        }
        foreach ($materials as $m) {
            $totalQuantity += (float) ($m['count'] ?? 1);
        }

        if (!empty($products) && !empty($materials)) {
            $purchaseType = 'all';
        } elseif (!empty($materials)) {
            $purchaseType = 'material';
        } else {
            $purchaseType = 'product';
        }

        $purchaseData = [
            'type' => $purchaseType,
            'store_id' => $request->store_id,
            'supplier_id' => $request->supplier_id,
            'total_coast' => $totalCost,
            'payment' => $totalPayment,
            'due' => $due,
            'quintity' => $totalQuantity,
            'date' => $request->date,
            'admin_id' => $request->user() ? $request->user()->id : null,
        ];

        if ($request->hasFile('receipt')) {
            $purchaseData['receipt'] = $this->upload($request, 'receipt', 'admin/purchases/receipt');
        }

        try {
            $purchase = $this->purchases->create($purchaseData);
        } catch (\Exception $e) {
            $purchaseData['type'] = 'product';
            $purchase = $this->purchases->create($purchaseData);
        }

        // Add due to supplier balance
        if ($due > 0 && $purchase->supplier_id) {
            $supplier = $this->suppliers->find($purchase->supplier_id);
            if ($supplier) {
                $supplier->balance = (float) ($supplier->balance ?? 0) + $due;
                $supplier->save();
            }
        }

        // Save materials & update stock
        foreach ($materials as $item) {
            $material = Material::find($item['item_id']);
            $itemCount = (float) ($item['count'] ?? 1);
            $unitId = !empty($item['unit_id']) ? $item['unit_id'] : $material?->unit_id;

            $this->purchase_materials->create([
                'purchase_id' => $purchase->id,
                'category_material_id' => $material?->category_id,
                'material_id' => $item['item_id'],
                'unit_id' => $unitId,
                'count' => $itemCount,
            ]);

            $matStock = $this->material_stock
                ->where('material_id', $item['item_id'])
                ->where('store_id', $request->store_id)
                ->first();

            if (!$matStock) {
                $this->material_stock->create([
                    'category_id' => $material?->category_id,
                    'material_id' => $item['item_id'],
                    'store_id' => $request->store_id,
                    'quantity' => $itemCount,
                    'actual_quantity' => $itemCount,
                    'unit_id' => $unitId,
                ]);
            } else {
                $matStock->quantity += $itemCount;
                $matStock->actual_quantity += $itemCount;
                $matStock->save();
            }
        }

        // Save products & update stock
        foreach ($products as $item) {
            $product = PurchaseProduct::find($item['item_id']);
            $itemCount = (float) ($item['count'] ?? 1);
            $unitId = !empty($item['unit_id']) ? $item['unit_id'] : null;

            $this->purchase_product_items->create([
                'purchase_id' => $purchase->id,
                'category_id' => $product?->category_id,
                'product_id' => $item['item_id'],
                'unit_id' => $unitId,
                'count' => $itemCount,
            ]);

            $prodStock = $this->stock
                ->where('product_id', $item['item_id'])
                ->where('store_id', $request->store_id)
                ->first();

            if (!$prodStock) {
                $this->stock->create([
                    'category_id' => $product?->category_id,
                    'product_id' => $item['item_id'],
                    'store_id' => $request->store_id,
                    'quantity' => $itemCount,
                    'actual_quantity' => $itemCount,
                    'unit_id' => $unitId,
                ]);
            } else {
                $prodStock->quantity += $itemCount;
                $prodStock->actual_quantity += $itemCount;
                $prodStock->save();
            }
        }

        // 1. Create initial paid invoice if payment > 0
        if ($totalPayment > 0) {
            $invoice = $this->purchase_invoices->create([
                'purchase_id' => $purchase->id,
                'payment' => $totalPayment,
                'due' => 0,
                'date' => $request->date ?? now()->toDateString(),
            ]);

            if (!empty($request->financial)) {
                foreach ($request->financial as $f) {
                    $amount = (float) ($f['amount'] ?? 0);
                    if ($amount > 0) {
                        $this->purchase_invoice_financials->create([
                            'purchase_invoice_id' => $invoice->id,
                            'financial_id' => $f['id'],
                            'amount' => $amount,
                        ]);

                        $finAccount = FinantiolAcounting::find($f['id']);
                        if ($finAccount) {
                            $finAccount->balance -= $amount;
                            $finAccount->save();
                        }
                    }
                }
            }
        }

        // 2. Create scheduled due invoices / installments if due > 0
        if ($due > 0) {
            if (!empty($request->due_invoices) && is_array($request->due_invoices)) {
                foreach ($request->due_invoices as $inv) {
                    $invDue = (float) ($inv['due'] ?? 0);
                    if ($invDue > 0) {
                        $this->purchase_invoices->create([
                            'purchase_id' => $purchase->id,
                            'payment' => 0,
                            'due' => $invDue,
                            'date' => $inv['date'] ?? $request->date,
                        ]);
                    }
                }
            } else {
                $this->purchase_invoices->create([
                    'purchase_id' => $purchase->id,
                    'payment' => 0,
                    'due' => $due,
                    'date' => $request->date ?? now()->toDateString(),
                ]);
            }
        }

        return response()->json([
            'success' => 'You add data success',
            'purchase_id' => $purchase->id,
        ]);
    }

    public function modify(Request $request, $id){
        $purchase = $this->purchases->where('id', $id)->first();
        if (!$purchase) {
            return response()->json(['errors' => 'Purchase not found'], 404);
        }

        $products = is_array($request->products) ? $request->products : [];
        $materials = is_array($request->materials) ? $request->materials : [];

        if (empty($products) && empty($materials) && is_array($request->items)) {
            foreach ($request->items as $it) {
                if (($it['type'] ?? $request->type) === 'material') {
                    $materials[] = $it;
                } else {
                    $products[] = $it;
                }
            }
        } elseif (empty($products) && empty($materials)) {
            if ($request->type === 'material' && !empty($request->material_id)) {
                $materials[] = [
                    'item_id' => $request->material_id,
                    'unit_id' => $request->unit_id,
                    'count' => $request->quintity ?? 1,
                ];
            } elseif (!empty($request->product_id)) {
                $products[] = [
                    'item_id' => $request->product_id,
                    'unit_id' => $request->unit_id,
                    'count' => $request->quintity ?? 1,
                ];
            }
        }

        if (empty($products) && empty($materials)) {
            return response()->json([
                'errors' => ['items' => ['Please add at least one product or material.']],
            ], 400);
        }

        $request->merge([
            'products' => $products,
            'materials' => $materials,
        ]);

        $validator = Validator::make($request->all(), [
            'store_id' => ['required', 'exists:purchase_stores,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'total_coast' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'receipt' => ['nullable'],

            'products' => ['nullable', 'array'],
            'products.*.item_id' => ['required_with:products', 'exists:purchase_products,id'],
            'products.*.unit_id' => ['nullable', 'exists:units,id'],
            'products.*.count' => ['nullable', 'numeric', 'min:0.01'],

            'materials' => ['nullable', 'array'],
            'materials.*.item_id' => ['required_with:materials', 'exists:materials,id'],
            'materials.*.unit_id' => ['nullable', 'exists:units,id'],
            'materials.*.count' => ['nullable', 'numeric', 'min:0.01'],

            'financial' => ['nullable', 'array'],
            'financial.*.id' => ['required_with:financial', 'exists:finantiol_acountings,id'],
            'financial.*.amount' => ['required_with:financial', 'numeric', 'min:0.01'],

            'due_invoices' => ['nullable', 'array'],
            'due_invoices.*.due' => ['required_with:due_invoices', 'numeric', 'min:0.01'],
            'due_invoices.*.date' => ['required_with:due_invoices', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 400);
        }

        $totalCost = (float) $request->total_coast;
        $totalPayment = 0;
        if (!empty($request->financial)) {
            foreach ($request->financial as $f) {
                $totalPayment += (float) ($f['amount'] ?? 0);
            }
        }
        $due = max(0, $totalCost - $totalPayment);

        $totalQuantity = 0;
        foreach ($products as $p) {
            $totalQuantity += (float) ($p['count'] ?? 1);
        }
        foreach ($materials as $m) {
            $totalQuantity += (float) ($m['count'] ?? 1);
        }

        if (!empty($products) && !empty($materials)) {
            $purchaseType = 'all';
        } elseif (!empty($materials)) {
            $purchaseType = 'material';
        } else {
            $purchaseType = 'product';
        }

        $oldDue = (float) ($purchase->due ?? 0);
        $oldSupplierId = $purchase->supplier_id;
        $newDue = $due;
        $newSupplierId = $request->supplier_id;

        $purchaseData = [
            'type' => $purchaseType,
            'store_id' => $request->store_id,
            'supplier_id' => $request->supplier_id,
            'total_coast' => $totalCost,
            'payment' => $totalPayment,
            'due' => $due,
            'quintity' => $totalQuantity,
            'date' => $request->date,
            'admin_id' => $request->user() ? $request->user()->id : $purchase->admin_id,
        ];

        if ($request->hasFile('receipt')) {
            $purchaseData['receipt'] = $this->upload($request, 'receipt', 'admin/purchases/receipt');
            if ($purchase->receipt) {
                $this->deleteImage($purchase->receipt);
            }
        }

        try {
            $purchase->update($purchaseData);
        } catch (\Exception $e) {
            $purchaseData['type'] = 'product';
            $purchase->update($purchaseData);
        }

        // Adjust supplier balance for due change
        if ($oldSupplierId == $newSupplierId) {
            $diff = $newDue - $oldDue;
            if ($diff != 0 && $newSupplierId) {
                $supplier = $this->suppliers->find($newSupplierId);
                if ($supplier) {
                    $supplier->balance = (float) ($supplier->balance ?? 0) + $diff;
                    $supplier->save();
                }
            }
        } else {
            if ($oldDue > 0 && $oldSupplierId) {
                $oldSupplier = $this->suppliers->find($oldSupplierId);
                if ($oldSupplier) {
                    $oldSupplier->balance = (float) ($oldSupplier->balance ?? 0) - $oldDue;
                    $oldSupplier->save();
                }
            }
            if ($newDue > 0 && $newSupplierId) {
                $newSupplier = $this->suppliers->find($newSupplierId);
                if ($newSupplier) {
                    $newSupplier->balance = (float) ($newSupplier->balance ?? 0) + $newDue;
                    $newSupplier->save();
                }
            }
        }

        // Remove old items
        $this->purchase_materials->where('purchase_id', $id)->delete();
        $this->purchase_product_items->where('purchase_id', $id)->delete();

        // Save materials if any
        foreach ($materials as $item) {
            $material = Material::find($item['item_id']);
            $itemCount = (float) ($item['count'] ?? 1);
            $unitId = !empty($item['unit_id']) ? $item['unit_id'] : $material?->unit_id;

            $this->purchase_materials->create([
                'purchase_id' => $id,
                'category_material_id' => $material?->category_id,
                'material_id' => $item['item_id'],
                'unit_id' => $unitId,
                'count' => $itemCount,
            ]);
        }

        // Save products if any
        foreach ($products as $item) {
            $product = PurchaseProduct::find($item['item_id']);
            $itemCount = (float) ($item['count'] ?? 1);
            $unitId = !empty($item['unit_id']) ? $item['unit_id'] : null;

            $this->purchase_product_items->create([
                'purchase_id' => $id,
                'category_id' => $product?->category_id,
                'product_id' => $item['item_id'],
                'unit_id' => $unitId,
                'count' => $itemCount,
            ]);
        }

        // Refund previous invoice financials
        $oldInvoices = $this->purchase_invoices->where('purchase_id', $id)->with('financials')->get();
        foreach ($oldInvoices as $inv) {
            foreach ($inv->financials as $fin) {
                $account = FinantiolAcounting::find($fin->financial_id);
                if ($account) {
                    $account->balance += $fin->amount;
                    $account->save();
                }
            }
            $inv->financials()->delete();
            $inv->delete();
        }

        // Create new invoice and financials if totalPayment > 0
        if ($totalPayment > 0) {
            $invoice = $this->purchase_invoices->create([
                'purchase_id' => $id,
                'payment' => $totalPayment,
                'due' => 0,
                'date' => now()->toDateString(),
            ]);

            if (!empty($request->financial)) {
                foreach ($request->financial as $f) {
                    $amount = (float) ($f['amount'] ?? 0);
                    if ($amount > 0) {
                        $this->purchase_invoice_financials->create([
                            'purchase_invoice_id' => $invoice->id,
                            'financial_id' => $f['id'],
                            'amount' => $amount,
                        ]);

                        $finAccount = FinantiolAcounting::find($f['id']);
                        if ($finAccount) {
                            $finAccount->balance -= $amount;
                            $finAccount->save();
                        }
                    }
                }
            }
        }

        // Create scheduled due invoices / installments if newDue > 0
        if ($newDue > 0) {
            if (!empty($request->due_invoices) && is_array($request->due_invoices)) {
                foreach ($request->due_invoices as $inv) {
                    $invDue = (float) ($inv['due'] ?? 0);
                    if ($invDue > 0) {
                        $this->purchase_invoices->create([
                            'purchase_id' => $id,
                            'payment' => 0,
                            'due' => $invDue,
                            'date' => $inv['date'] ?? $request->date,
                        ]);
                    }
                }
            } else {
                $this->purchase_invoices->create([
                    'purchase_id' => $id,
                    'payment' => 0,
                    'due' => $newDue,
                    'date' => $request->date ?? now()->toDateString(),
                ]);
            }
        }

        return response()->json([
            'success' => 'You update data success',
        ]);
    }

    public function invoices(Request $request, $id){
        $invoices = $this->purchase_invoices
            ->with('financials.financial:id,name')
            ->where('purchase_id', $id)
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function($invoice){
                $due = (float) $invoice->due;
                $payment = (float) $invoice->payment;
                $today = now()->toDateString();
                $status = ($due <= 0 && $payment > 0)
                    ? 'paid'
                    : (($invoice->date && $invoice->date < $today) ? 'overdue' : 'upcoming');

                return [
                    'id' => $invoice->id,
                    'purchase_id' => $invoice->purchase_id,
                    'payment' => $payment,
                    'due' => $due,
                    'date' => $invoice->date,
                    'status' => $status,
                    'created_at' => $invoice->created_at?->format('Y-m-d H:i'),
                    'financials' => $invoice->financials->map(function($f){
                        return [
                            'id' => $f->id,
                            'financial_id' => $f->financial_id,
                            'name' => $f->financial?->name ?? '-',
                            'amount' => (float) $f->amount,
                        ];
                    }),
                ];
            });

        return response()->json([
            'invoices' => $invoices,
        ]);
    }

    public function add_invoice(Request $request, $id){
        $purchase = $this->purchases->where('id', $id)->first();
        if (!$purchase) {
            return response()->json(['errors' => 'Purchase not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'invoice_id' => ['nullable', 'exists:purchase_invoices,id'],
            'payment' => ['required', 'numeric', 'min:0.01'],
            'due' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'financial' => ['required', 'array', 'min:1'],
            'financial.*.id' => ['required', 'exists:finantiol_acountings,id'],
            'financial.*.amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 400);
        }

        $payment = (float) $request->payment;
        $currentDue = (float) $purchase->due;

        if ($payment > ($currentDue + 0.001)) {
            return response()->json([
                'errors' => ['payment' => ['Payment amount cannot exceed remaining due (' . $currentDue . ').']],
            ], 400);
        }

        $newRemainingDue = max(0, $currentDue - $payment);

        $targetInvoice = null;
        if (!empty($request->invoice_id)) {
            $targetInvoice = $this->purchase_invoices->where('purchase_id', $id)->where('id', $request->invoice_id)->first();
        }

        if ($targetInvoice) {
            $targetInvoice->payment = (float) $targetInvoice->payment + $payment;
            $targetInvoice->due = max(0, (float) $targetInvoice->due - $payment);
            $targetInvoice->date = $request->date ?? $targetInvoice->date;
            $targetInvoice->save();
            $invoice = $targetInvoice;
        } else {
            $earliestUnpaid = $this->purchase_invoices
                ->where('purchase_id', $id)
                ->where('due', '>', 0)
                ->orderBy('date', 'asc')
                ->orderBy('id', 'asc')
                ->first();

            if ($earliestUnpaid) {
                $earliestUnpaid->payment = (float) $earliestUnpaid->payment + $payment;
                $earliestUnpaid->due = max(0, (float) $earliestUnpaid->due - $payment);
                $earliestUnpaid->date = $request->date ?? $earliestUnpaid->date;
                $earliestUnpaid->save();
                $invoice = $earliestUnpaid;
            } else {
                $invoiceDue = $request->has('due') ? (float) $request->due : $newRemainingDue;
                $invoice = $this->purchase_invoices->create([
                    'purchase_id' => $id,
                    'payment' => $payment,
                    'due' => $invoiceDue,
                    'date' => $request->date,
                ]);
            }
        }

        foreach ($request->financial as $f) {
            $amount = (float) ($f['amount'] ?? 0);
            if ($amount > 0) {
                $this->purchase_invoice_financials->create([
                    'purchase_invoice_id' => $invoice->id,
                    'financial_id' => $f['id'],
                    'amount' => $amount,
                ]);

                $account = FinantiolAcounting::find($f['id']);
                if ($account) {
                    $account->balance -= $amount;
                    $account->save();
                }
            }
        }

        // Deduct payment from supplier balance (settling the debt)
        if ($payment > 0 && $purchase->supplier_id) {
            $supplier = $this->suppliers->find($purchase->supplier_id);
            if ($supplier) {
                $supplier->balance = (float) ($supplier->balance ?? 0) - $payment;
                $supplier->save();
            }
        }

        // Update purchase total payment and due
        $purchase->payment = (float) $purchase->payment + $payment;
        $purchase->due = $newRemainingDue;
        $purchase->save();

        return response()->json([
            'success' => 'Invoice payment added successfully',
            'invoice' => $invoice,
        ]);
    }

    public function delete(Request $request, $id){
        $purchase = $this->purchases->where('id', $id)->first();
        if (!$purchase) {
            return response()->json(['errors' => 'Purchase not found'], 404);
        }

        // 1. Deduct remaining unpaid due from supplier balance
        if ($purchase->due > 0 && $purchase->supplier_id) {
            $supplier = $this->suppliers->find($purchase->supplier_id);
            if ($supplier) {
                $supplier->balance = (float) ($supplier->balance ?? 0) - (float) $purchase->due;
                $supplier->save();
            }
        }

        // 2. Refund financial accounts from invoice payments
        $invoices = $this->purchase_invoices->where('purchase_id', $id)->with('financials')->get();
        foreach ($invoices as $inv) {
            foreach ($inv->financials as $fin) {
                $account = FinantiolAcounting::find($fin->financial_id);
                if ($account) {
                    $account->balance += $fin->amount;
                    $account->save();
                }
            }
            $inv->financials()->delete();
            $inv->delete();
        }

        // 3. Revert stock
        if ($purchase->type === 'material') {
            $materials = $this->purchase_materials->where('purchase_id', $id)->get();
            foreach ($materials as $m) {
                $matStock = $this->material_stock
                    ->where('material_id', $m->material_id)
                    ->where('store_id', $purchase->store_id)
                    ->first();
                if ($matStock) {
                    $matStock->quantity = max(0, $matStock->quantity - $m->count);
                    $matStock->actual_quantity = max(0, $matStock->actual_quantity - $m->count);
                    $matStock->save();
                }
            }
            $this->purchase_materials->where('purchase_id', $id)->delete();
        } else {
            $products = $this->purchase_product_items->where('purchase_id', $id)->get();
            foreach ($products as $p) {
                $prodStock = $this->stock
                    ->where('product_id', $p->product_id)
                    ->where('store_id', $purchase->store_id)
                    ->first();
                if ($prodStock) {
                    $prodStock->quantity = max(0, $prodStock->quantity - $p->count);
                    $prodStock->actual_quantity = max(0, $prodStock->actual_quantity - $p->count);
                    $prodStock->save();
                }
            }
            $this->purchase_product_items->where('purchase_id', $id)->delete();
        }

        // 4. Delete receipt image if exists
        if ($purchase->receipt) {
            $this->deleteImage($purchase->receipt);
        }

        // 5. Delete purchase record
        $purchase->delete();

        return response()->json([
            'success' => 'You delete data success',
        ]);
    }
}
