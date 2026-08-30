<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

use App\Models\Branch;
use App\Models\User;
use App\Models\Product;
use App\Models\Addon;
use App\Models\Delivery;
use App\Models\Offer;
use App\Models\Deal;
use App\Models\ExtraProduct;
use App\Models\ExcludeProduct;
use App\Models\VariationProduct;
use App\Models\OptionProduct;
use Carbon\Carbon;

class Order extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'date',
        'rate',
        'prepare_order',
        'comment',
        'service_fees',
        'service_fees_id',
        'pos',
        'user_id',
        'branch_id',
        'amount',
        'order_status',
        'order_type',
        'payment_status',
        'total_tax',
        'total_discount',
        'address_id', 
        'delivery_id',
        'notes',
        'coupon_discount',
        'order_number',
        'payment_method_id', 
        'status',
        'points',
        'order_details',
        'rejected_reason',
        'transaction_id',
        'receipt',
        'cancel_reason',
        'customer_cancel_reason',
        'admin_cancel_reason',
        'table_id',
        'captain_id',
        'cashier_man_id',
        'cashier_id', 
        'shift',
        'admin_id',
        'operation_status',
        'sechedule_slot_id',
        'canceled_noti',
        'customer_id',
        'deleted_at',
        'source',
        'take_away_status',
        'delivery_status',
        'delivery_fees',
        'coupon_id',
        'from_table_order',
        'due',
        'dicount_id',
        'preparation_read_status',
        'due_from_delivery',
        'void_financial_id',
        'is_void',
        'is_cancel_evaluate',
        'free_discount',
        'module_id',
        'module_order_number',
        'due_module',
        'transfer_from_id',
        'void_id',
        'void_reason', 
        "is_read",
        "prepare_order",
        'order_active' // ده عشان لو مكملش طلب الاوردر يتحفظ فقط
    ];
    protected $appends = ['order_date', 'status_payment', 'order_details_data'];

    protected $casts = [
        'id' => 'string',
        'order_number' => 'string',
        'module_order_number' => 'string',
    ];



    public function getIdAttribute($value){
        return (int) $value;
    }
    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->id)) {
                // نجيب أكبر رقم طبيعي في الطلبات العادية (ونتجاهل أي قفزات زي 999995 أو الأرقام الضخمة)
                $maxId = \Illuminate\Support\Facades\DB::table('orders')
                            ->where('id', '<', 9000000)
                            ->max('id');
                            
                $newId = ($maxId ?? 0) + 1;
                
                // Safety check to ensure we don't hit a duplicate key exception
                while (\Illuminate\Support\Facades\DB::table('orders')->where('id', $newId)->exists()) {
                    $newId++;
                }
                
                $order->id = $newId;
            }
        });

        static::addGlobalScope('hide_deleted_orders', function ($query) {
            $user = auth()->user();
            
            if ($user && isset($user->show_deleted_data) && $user->show_deleted_data == true) {
                return;
            }

            $query->where('deleted_at', 0);
        });
    }

    protected $hidden = [
        'pivot', 
    ];

    public function financials(){
        return $this->belongsToMany(FinantiolAcounting::class, 'order_financials', "order_id", "financial_id");
    }

    public function getdateAttribute(){
        return $this->created_at 
        ? $this->created_at->format('H:i:s') 
        : null;

    }

    public function service_fees_item(){
        return $this->belongsTo(ServiceFees::class, 'service_fees_id');
    }
    
    public function transfer_from(){
        return $this->belongsTo(Branch::class, 'transfer_from_id');
    }
    
    public function bundles(){
        return $this->hasMany(OrderBundle::class, 'bundle_id');
    }

    public function group_module(){
        return $this->belongsTo(GroupProduct::class, 'module_id');
    }

    public function getorderNumberAttribute(){
        if (isset($this->attributes["order_number"]) && $this->attributes["order_number"] !== null) {
            return $this->attributes["order_number"];
        }
        $time_settings = TimeSittings::
        where("branch_id", $this->branch_id)
        ->orderByDesc("created_at")
        ->first();
        if (empty($time_settings)) {
            return $this->created_at->format('d') . $this->created_at->format('m') . 
            $this->created_at->format('y') . $this->id;
        }
        else{
            $from = $time_settings->from;
            $to = $this->created_at->format('H:i:s');
            if ($from > $to) {
                $date = Carbon::parse($this->created_at)->subDay();
            }
            else{
                $date = $this->created_at;
            }
            return $date->format('d') . $date->format('m') . 
            $date->format('y') . $this->id;
        }
    }

    public function getStatusPaymentAttribute(){
        if (isset($this->attributes['status']) && $this->attributes['status'] == 1) {
            return 'approved';
        } 
        elseif (!isset($this->attributes['status'])) { // Use isset to check if it's null or not set
            return 'pending';
        } 
        elseif (isset($this->attributes['status']) && $this->attributes['status'] == 0) {
            return 'rejected';
        } 
        elseif (isset($this->attributes['status']) && $this->attributes['status'] == 2) {
            return 'faild';
        } 
    }
    
    public function getOrderDateAttribute(){
        if (isset($this->attributes['created_at'] )&& !empty($this->attributes['created_at'])) {
            return $this->created_at
            ? $this->created_at->format('Y-m-d')
            : null;
        } 
        else {
            return null;
        }
    }

    public function getOrderDetailsDataAttribute(){
        try {
            if(isset($this->attributes['order_details'])){
                $decoded = json_decode($this->attributes['order_details'], true) ?? [];
                if (!empty($decoded) && is_array($decoded)) {
                    $first = reset($decoded);
                    if (is_array($first) && (isset($first['product_id']) || (!isset($first['product']) && !isset($first['extras'])))) {
                        return $this->normalizeRawOrderDetails($decoded);
                    }
                }
                return $decoded;
            }
            return [];
        } catch (\Throwable $th) {
            return [];
        }
    }

    private function normalizeRawOrderDetails(array $rawItems): array {
        $normalized = [];
        foreach ($rawItems as $item) {
            $productRecord = isset($item['product_id']) ? Product::find($item['product_id']) : null;
            $product = [];
            if ($productRecord) {
                $product = [
                    [
                        'product' => [
                            'id' => $productRecord->id,
                            'name' => $productRecord->name,
                            'image_link' => $productRecord->image_link ?? $productRecord->image ?? null,
                            'price' => $productRecord->price,
                            'price_after_discount' => $productRecord->final_price ?? $productRecord->price,
                            'price_after_tax' => $productRecord->price,
                        ],
                        'count' => $item['count'] ?? 1,
                        'notes' => $item['note'] ?? $item['notes'] ?? null,
                    ]
                ];
            }

            // Extras
            $extras = [];
            $extraIds = $item['extra_id'] ?? $item['extras'] ?? [];
            if (is_array($extraIds)) {
                foreach ($extraIds as $extraEl) {
                    if (is_array($extraEl) && isset($extraEl['id'], $extraEl['name'])) {
                        $extras[] = $extraEl;
                    } elseif (is_numeric($extraEl) || is_string($extraEl)) {
                        $extRecord = ExtraProduct::find($extraEl);
                        if ($extRecord) {
                            $extras[] = [
                                'id' => $extRecord->id,
                                'name' => $extRecord->name,
                                'price' => $extRecord->price,
                            ];
                        }
                    }
                }
            }

            // Addons
            $addons = [];
            $addonItems = $item['addons'] ?? [];
            if (is_array($addonItems)) {
                foreach ($addonItems as $addonEl) {
                    if (is_array($addonEl) && isset($addonEl['addon'])) {
                        $addons[] = $addonEl;
                    } elseif (is_array($addonEl) && isset($addonEl['addon_id'])) {
                        $adRecord = Addon::find($addonEl['addon_id']);
                        if ($adRecord) {
                            $addons[] = [
                                'addon' => [
                                    'id' => $adRecord->id,
                                    'name' => $adRecord->name,
                                    'price' => $adRecord->price,
                                ],
                                'price' => $addonEl['price'] ?? $adRecord->price,
                                'count' => $addonEl['count'] ?? 1,
                            ];
                        }
                    }
                }
            }

            // Excludes
            $excludes = [];
            $excludeIds = $item['exclude_id'] ?? $item['excludes'] ?? [];
            if (is_array($excludeIds)) {
                foreach ($excludeIds as $excEl) {
                    if (is_array($excEl) && isset($excEl['id'], $excEl['name'])) {
                        $excludes[] = $excEl;
                    } elseif (is_numeric($excEl) || is_string($excEl)) {
                        $excRecord = ExcludeProduct::find($excEl);
                        if ($excRecord) {
                            $excludes[] = [
                                'id' => $excRecord->id,
                                'name' => $excRecord->name,
                            ];
                        }
                    }
                }
            }

            // Variations
            $variations = [];
            $varItems = $item['variation'] ?? $item['variations'] ?? [];
            if (is_array($varItems)) {
                foreach ($varItems as $varEl) {
                    if (is_array($varEl) && isset($varEl['variation']['id'])) {
                        $variations[] = $varEl;
                    } elseif (is_array($varEl) && isset($varEl['variation_id'])) {
                        $varRecord = VariationProduct::find($varEl['variation_id']);
                        $optionIds = is_array($varEl['option_id'] ?? null) ? $varEl['option_id'] : (isset($varEl['option_id']) ? [$varEl['option_id']] : []);
                        $options = [];
                        foreach ($optionIds as $oid) {
                            $optRecord = OptionProduct::find($oid);
                            if ($optRecord) {
                                $options[] = [
                                    'id' => $optRecord->id,
                                    'name' => $optRecord->name,
                                    'price' => $optRecord->price,
                                    'total_option_price' => $optRecord->price,
                                ];
                            }
                        }
                        if ($varRecord) {
                            $variations[] = [
                                'variation' => [
                                    'id' => $varRecord->id,
                                    'name' => $varRecord->name,
                                ],
                                'options' => $options,
                            ];
                        }
                    }
                }
            }

            $normalized[] = [
                'product' => $product,
                'extras' => $extras,
                'addons' => $addons,
                'excludes' => $excludes,
                'variations' => $variations,
            ];
        }
        return $normalized;
    }

    public function getorderDetailsAttribute($data){
        try { 
            return json_decode($data);
        } catch (\Throwable $th) {
            return collect([]);
        }
    }

    public function void(){
        return $this->belongsTo(VoidReason::class, 'void_id');
    }

    public function financial_accountigs(){
        return $this->belongsToMany(FinantiolAcounting::class, 'order_financials', 'order_id', 'financial_id');
    }

    public function financial_amount(){
        return $this->hasMany(OrderFinancial::class, 'order_id');
    }

    public function captain(){
        return $this->belongsTo(CaptainOrder::class, 'captain_id');
    }

    public function delivery(){
        return $this->belongsTo(Delivery::class, 'delivery_id');
    }

    public function table(){
        return $this->belongsTo(CafeTable::class, 'table_id');
    }

    public function payment_method(){
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function cashier_man(){
        return $this->belongsTo(CashierMan::class, 'cashier_man_id');
    }

    public function casheir(){
        return $this->belongsTo(Cashier::class, 'cashier_id');
    }

    public function products(){
        return $this->belongsToMany(Product::class, 'order_product', 'order_id', 'product_id')
        ->withPivot('created_at');
    }

    public function addons(){
        return $this->belongsToMany(Addon::class, 'order_product', 'order_id', 'addon_id');
    }

    public function offers(){
        return $this->belongsToMany(Offer::class, 'order_product', 'order_id', 'offer_id');
    }

    public function deal(){
        return $this->belongsToMany(Deal::class, 'order_product', 'order_id', 'deal_id');
    }

    public function address(){
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function order_address(){
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function admin(){
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function schedule(){
        return $this->belongsTo(ScheduleSlot::class, 'sechedule_slot_id');
    }

    public function details(){
        return $this->hasMany(OrderDetail::class, 'order_id');
    }
}

