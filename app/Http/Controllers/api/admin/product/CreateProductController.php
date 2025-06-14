<?php

namespace App\Http\Controllers\api\admin\product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\product\ProductRequest;
use App\trait\image;
use App\trait\translaion;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductImport;
use Illuminate\Support\Facades\Validator;

use App\Models\Product;
use App\Models\VariationProduct;
use App\Models\OptionProduct;
use App\Models\ExcludeProduct;
use App\Models\ExtraProduct;
use App\Models\ExtraPricing;
use App\Models\ExtraGroup;

class CreateProductController extends Controller
{
    public function __construct(private Product $products, private VariationProduct $variations,
    private OptionProduct $option_product, private ExcludeProduct $excludes, 
    private ExtraProduct $extra, private ExtraPricing $extra_pricing, 
    private ExtraGroup $extra_group){}
    protected $productRequest = [
        'name',
        'description',
        'category_id',
        'sub_category_id',
        'item_type',
        'stock_type',
        'number',
        'price',
        'product_time_status',
        'from',
        'to',
        'discount_id',
        'tax_id',
        'status',
        'recommended',
        'points',
    ];
    use image;
    use translaion;

    public function create(ProductRequest $request){
        // http://localhost/food2go/public/admin/product/update/2?category_id=4&sub_category_id=5&item_type=all&stock_type=unlimited&price=100&product_time_status=0&status=1&recommended=1&points=100&excludes[0][0][exclude_name]=Tomatoa&excludes[0][0][tranlation_id]=1&excludes[0][0][tranlation_name]=en&excludes[0][1][exclude_name]=طماطم&excludes[0][1][tranlation_id]=5&excludes[0][1][tranlation_name]=ar&variations[0][names][0][name]=Size&variations[0][names][0][tranlation_id]=1&variations[0][names][0][tranlation_name]=en&variations[0][names][1][name]=المقاس&variations[0][names][1][tranlation_id]=5&variations[0][names][1][tranlation_name]=ar&variations[0][type]=single&variations[0][required]=1&variations[0][points]=100&variations[0][options][0][names][0][name]=Small&variations[0][options][0][names][0][tranlation_id]=1&variations[0][options][0][names][0][tranlation_name]=en&variations[0][options][0][names][1][name]=صغير&variations[0][options][0][names][1][tranlation_id]=5&variations[0][options][0][names][1][tranlation_name]=ar&variations[0][options][0][price]=100&variations[0][options][0][status]=1&variations[0][options][0][extra_names][0][extra_name]=Exatra 00&variations[0][options][0][extra_names][0][tranlation_id]=1&variations[0][options][0][extra_names][0][tranlation_name]=en&variations[0][options][0][extra_names][1][extra_name]=زيادة 00&variations[0][options][0][extra_names][1][tranlation_id]=5&variations[0][options][0][extra_names][1][tranlation_name]=ar&variations[0][options][0][extra_price]=1000&product_names[0][product_name]=Pizza1&product_names[0][tranlation_id]=1&product_names[0][tranlation_name]=en&product_names[1][product_name]=بيتزا 1&product_names[1][tranlation_id]=5&product_names[1][tranlation_name]=ar&product_descriptions[0][product_description]=Pizza description&product_descriptions[0][tranlation_id]=1&product_descriptions[0][tranlation_name]=en&product_descriptions[1][product_description]=وصف البيتزا&product_descriptions[1][tranlation_id]=5&product_descriptions[1][tranlation_name]=ar&extra[0][names][0][extra_name]=Extra 1&extra[0][names][0][tranlation_id]=1&extra[0][names][0][tranlation_name]=en&extra[0][names][1][extra_name]=زيادة 1&extra[0][names][1][tranlation_id]=5&extra[0][names][1][tranlation_name]=ar&extra[0][extra_price]=100
        // https://bcknd.food2go.online/admin/product/add
        // Keys
        // category_id, sub_category_id, item_type[online, offline, all], 
        // stock_type[daily, unlimited, fixed], number, price
        // product_time_status, from, to, discount_id, tax_id, status, recommended, image, points
        // addons[]
        // excludes[][{exclude_name, tranlation_id, tranlation_name}]
        
        // extra[][names][{extra_name, tranlation_id, tranlation_name}]
        // extra[][extra_price]
    
        // variations[][names][{name, tranlation_id, tranlation_name}] ,variations[][type],
        // variations[][min] ,variations[][max]
        // variations[][required]
        // variations[][options][][names][{name, tranlation_id, tranlation_name}], 
        // variations[][options][][price], variations[][options][][status],
        // variations[][options][][points],

        // variations[][options][][extra][][extra_index], 
        // variations[][options][][extra][][extra_price],

        // product_names[{product_name, tranlation_id, tranlation_name}]
        // product_descriptions[{product_description, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $product_id = 0;
        try{
            $default = $request->product_names[0];
            $default_description = $request->product_descriptions[0] ?? null;
            $productRequest = $request->only($this->productRequest);
            $productRequest['name'] = $default['product_name'];
            $productRequest['description'] = $default_description['product_description'] ?? null;
            $extra_num = [];
    
            if (is_file($request->image)) {
                $imag_path = $this->upload($request, 'image', 'admin/product/image');
                $productRequest['image'] = $imag_path;
            } // if send image upload it
            $product = $this->products->create($productRequest); // create product
            $product_id = $product->id;
            foreach ($request->product_names as $item) {
                if (!empty($item['product_name'])) {
                    $product->translations()->create([
                        'locale' => $item['tranlation_name'],
                        'key' => $default['product_name'],
                        'value' => $item['product_name']
                    ]); 
                }
             }
             if ($request->product_descriptions) {
                foreach ($request->product_descriptions as $item) {
                    if (!empty($item['product_description'])) {
                        $product->translations()->create([
                            'locale' => $item['tranlation_name'],
                            'key' => $default_description['product_description'],
                            'value' => $item['product_description']
                        ]); 
                    }
                }
             }
            if ($request->addons) {
                $product->addons()->attach($request->addons); // add addons of product
            }
            if ($request->excludes) {
                foreach ($request->excludes as $item) {
                    $exclude = $this->excludes
                    ->create([
                        'name' => $item['names'][0]['exclude_name'],
                        'product_id' => $product->id
                    ]);
                    foreach ($item['names'] as $key => $element) {
                        if (!empty($element['exclude_name'])) {
                            $exclude->translations()->create([
                                'locale' => $element['tranlation_name'],
                                'key' => $item['names'][0]['exclude_name'],
                                'value' => $element['exclude_name'],
                            ]);
                        }
                    }
                }
            }// add extra
            if ($request->extra) {
                $extra_group = $this->extra_group
                ->whereIn('id', $request->extra)
                ->with('translations')
                ->get();
                foreach ($extra_group as $item) {
                    $new_extra = $this->extra
                    ->create([
                        'name' => $item->name,
                        'product_id' => $product->id,
                        'price' => $item->pricing,
                        'min' => $item->min,
                        'max' => $item->max,
                        'group_id' => $item->group_id,
                    ]);
                    // if (!empty($item['extra_price'])) {
                    //     $this->extra_pricing
                    //     ->create([
                    //         'price' => $item['extra_price'],
                    //         'product_id' => $product->id,
                    //         'extra_id' => $new_extra->id,
                    //     ]);
                    // }
                    $extra_num[] = $new_extra;
                    foreach ($item->translations as $key => $element) {
                        if (!empty($element['extra_name'])) {
                            $new_extra->translations()->create([
                                'locale' => $element->locale,
                                'key' => $element->key,
                                'value' => $element->value,
                            ]); 
                        }
                    }
                }
            }// add extra
            // ______________________________________________________________
            if ($request->variations) {
                foreach ($request->variations as $item) {
                    $variation = $this->variations
                    ->create([
                        'name' => $item['names'][0]['name'],
                        'type' => $item['type'],
                        'min' => $item['min'] ?? null,
                        'max' => $item['max'] ?? null,
                        'required' => $item['required'],
                        'product_id' => $product->id,
                    ]); // add variation
                    foreach ($item['names'] as $key => $element) {
                        if (!empty($element['name'])) {
                            $variation->translations()->create([
                                'locale' => $element['tranlation_name'],
                                'key' => $item['names'][0]['name'],
                                'value' => $element['name'],
                            ]);
                        }
                    }
                    foreach ($item['options'] as $element) {
                        $option = $this->option_product
                        ->create([
                            'name' => $element['names'][0]['name'],
                            'price' => $element['price'],
                            'status' => $element['status'],
                            'product_id' => $product->id,
                            'variation_id' => $variation->id,
                            'points' => $element['points'],
                        ]);// add options
                        foreach ($element['names'] as $key => $value) {
                            if (!empty($value['name'])) {
                                $option->translations()->create([
                                    'locale' => $value['tranlation_name'],
                                    'key' => $element['names'][0]['name'],
                                    'value' => $value['name'],
                                ]);
                            }
                        }
                        if (isset($element['extra']) && $element['extra']) {
                            $extra_group = $this->extra_group
                            ->whereIn('id', $element['extra'])
                            ->with('translations')
                            ->get();
                            foreach ($extra_group as $key => $extra) {
                                // ['extra_names']
                                // $extra_pricing = $this->extra_pricing
                                // ->create([ 
                                //     'price' => $extra['extra_price'],
                                //     'product_id' => $product->id,
                                //     'variation_id' => $variation->id,
                                //     'extra_id' => $extra_num[$extra['extra_index']]->id,
                                //     'option_id' => $option->id,
                                // ]);// add extra for option
                                $new_extra = $this->extra
                                ->create([
                                    'name' => $extra->name,
                                    'product_id' => $product->id,
                                    'price' => $extra->pricing,
                                    'min' => $extra->min,
                                    'max' => $extra->max,
                                    'option_id' => $option->id,
                                    'variation_id' => $variation->id,
                                    'group_id' => $extra->group_id,
                                ]);

                                foreach ($extra->translations as $key => $element) {
                                    if (!empty($element['extra_name'])) {
                                        $new_extra->translations()->create([
                                            'locale' => $element->locale,
                                            'key' => $item->key,
                                            'value' => $element->value,
                                        ]); 
                                    }
                                }
                            }
                        }
                    } 
                }
            }
    
            return response()->json([
                'success' => $request->all()
            ]);
        } catch (QueryException $e) {
            $this->products
            ->where('id', $product_id)
            ->delete();
            return response()->json([
                'faild' => $e
            ], 400);
        } 
    }

    public function modify(ProductRequest $request, $id){
        // https://bcknd.food2go.online/admin/product/update/{id}
        // Keys
        // category_id, sub_category_id, item_type[online, offline, all], 
        // stock_type[daily, unlimited, fixed], number, price
        // product_time_status, from, to, discount_id, tax_id, status, recommended, image, points
        // addons[]
        // excludes[][{exclude_name, tranlation_id, tranlation_name}]
        // extra[][names][{extra_name, tranlation_id, tranlation_name}]
        // extra[][extra_price]
        // variations[][names][{name, tranlation_id, tranlation_name}] ,variations[][type],
        // variations[][min] ,variations[][max]
        // variations[][required]
        // variations[][options][][names][{name, tranlation_id, tranlation_name}], 
        // variations[][options][][price], variations[][options][][status], 
        // variations[][options][][points],
        // variations[][options][][extra_names][{extra_name, tranlation_id, tranlation_name}], 
        // variations[][options][][extra_price], 
        // product_names[{product_name, tranlation_id, tranlation_name}]
        // product_descriptions[{product_description, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $extra_num = [];
        $default = $request->product_names[0];
        $default_description = $request->product_descriptions[0] ?? null;
        $productRequest = $request->only($this->productRequest);
        $productRequest['name'] = $default['product_name'];
        $productRequest['description'] = $default_description['product_description'] ?? null;
        
        $product = $this->products->
        where('id', $id)
        ->first(); // get product
        if (!empty($product->translations)) {
        $product->translations()->delete();            # code...
        }
        foreach ($request->product_names as $item) {
            if (!empty($item['product_name'])) {
                $product->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $default['product_name'],
                    'value' => $item['product_name']
                ]);
            } 
         }
         if ($request->product_descriptions) {
            foreach ($request->product_descriptions as $item) {
                if (!empty($item['product_description'])) {
                    $product->translations()->create([
                        'locale' => $item['tranlation_name'],
                        'key' => $default_description['product_description'],
                        'value' => $item['product_description']
                    ]);
                }
            }
         }
        if (is_file($request->image)) {
            $imag_path = $this->upload($request, 'image', 'admin/product/image');
            $productRequest['image'] = $imag_path;
            $this->deleteImage($product->image);
        } // if send image upload it and delete old image
        $product->update($productRequest); // create product
        if ($request->addons) {
            $product->addons()->sync($request->addons); // add addons of product
        }
        $exclude = $this->excludes
        ->where('product_id', $id)
        ->first();
        if (!empty($exclude->translations)) {
        $exclude->translations()->delete();            # code...
        }

        $this->excludes
        ->where('product_id', $id)
        ->delete(); // delete old excludes
        if ($request->excludes) {
            foreach ($request->excludes as $item) {
                $exclude = $this->excludes
                ->create([
                    'name' => $item['names'][0]['exclude_name'],
                    'product_id' => $product->id
                ]);
                foreach ($item['names'] as $key => $element) {
                    if (!empty($element['exclude_name'])) {
                        $exclude->translations()->create([
                            'locale' => $element['tranlation_name'],
                            'key' => $item['names'][0]['exclude_name'],
                            'value' => $element['exclude_name'],
                        ]); 
                    }
                }
            }
        }// add new excludes
        $extra = $this->extra
        ->where('product_id', $id)
        ->first();
        if (!empty($extra->translations)) {
        $extra->translations()->delete();            # code...
        }
        $this->extra
        ->where('product_id', $id)
        ->delete(); // delete old extra
        if ($request->extra) {
            $extra_group = $this->extra_group
            ->whereIn('id', $request->extra)
            ->with('translations')
            ->get();
            foreach ($extra_group as $item) {
                $new_extra = $this->extra
                ->create([
                    'name' => $item->name,
                    'product_id' => $product->id,
                    'price' => $item->price,
                    'min' => $item->min ?? null,
                    'max' => $item->max ?? null,
                    'group_id' => $item->group_id ?? null,
                ]);
                // if (!empty($item['extra_price'])) {
                //     $this->extra_pricing
                //     ->create([
                //         'price' => $item['extra_price'],
                //         'product_id' => $product->id,
                //         'extra_id' => $new_extra->id,
                //     ]);
                // }
                
                $extra_num[] = $new_extra;
                foreach ($item->translations as $key => $element) {
                    if (!empty($element['extra_name'])) {
                        $new_extra->translations()->create([
                            'locale' => $element->locale,
                            'key' => $element->key,
                            'value' => $element->value,
                        ]);
                    }
                }
            }
        }// add new extra
        $variations = $this->variations
        ->where('product_id', $id)
        ->first();
        if (!empty($variations->translations)) {
        $variations->translations()->delete();            # code...
        }
        $this->variations
        ->where('product_id', $id)
        ->delete(); // delete old product
        if ($request->variations) {
            foreach ($request->variations as $item) {
                $variation = $this->variations
                ->create([
                    'name' => $item['names'][0]['name'],
                    'type' => $item['type'],
                    'min' => $item['min'] ?? null,
                    'max' => $item['max'] ?? null,
                    'required' => $item['required'],
                    'product_id' => $product->id,
                ]); // add variation
                foreach ($item['names'] as $key => $element) {
                    if (!empty($element['name'])) {
                        $variation->translations()->create([
                            'locale' => $element['tranlation_name'],
                            'key' => $item['names'][0]['name'],
                            'value' => $element['name'],
                        ]);
                    }
                }
                if ($item['options']) {
                    foreach ($item['options'] as $element) {
                        $option = $this->option_product
                        ->create([
                            'name' => $element['names'][0]['name'],
                            'price' => $element['price'],
                            'status' => $element['status'],
                            'product_id' => $product->id,
                            'variation_id' => $variation->id,
                            'points' => $element['points'],
                        ]);
                        foreach ($element['names'] as $key => $value) {
                            if (!empty($value['name'])) {
                                $option->translations()->create([
                                    'locale' => $value['tranlation_name'],
                                    'key' => $element['names'][0]['name'],
                                    'value' => $value['name'],
                                ]);
                            }
                        }
                        if (isset($element['extra']) && $element['extra']) {
                            foreach ($element['extra'] as $key => $extra) {
                                $new_extra = $this->extra
                                ->create([
                                    'name' => $extra->name,
                                    'product_id' => $product->id,
                                    'price' => $extra->price,
                                    'min' => $extra->min,
                                    'max' => $extra->max,
                                    'option_id' => $option->id, 
                                    'variation_id' => $variation->id,
                                    'group_id' => $extra->group_id,
                                ]);
                            }
                        }
                    }
                }
            }
        }

        return response()->json([
            'success' => 'You update product success'
        ]);
    }

    public function delete($id){
        // https://bcknd.food2go.online/admin/product/delete/{id}
        $product = $this->products
        ->where('id', $id)
        ->first();
        $this->deleteImage($product->image);
        $product->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }

    public function import_excel(Request $request) {
        // https://bcknd.food2go.online/admin/product/import_excel
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        Excel::import(new ProductImport, $request->file('file'));

        return response()->json(['message' => 'File uploaded successfully']);
    }
}
