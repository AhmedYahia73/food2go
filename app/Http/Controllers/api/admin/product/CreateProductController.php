<?php

namespace App\Http\Controllers\api\admin\product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\product\ProductRequest;
use App\trait\image;
use App\trait\translaion;

use App\Models\Product;
use App\Models\VariationProduct;
use App\Models\OptionProduct;
use App\Models\ExcludeProduct;
use App\Models\ExtraProduct;

class CreateProductController extends Controller
{
    public function __construct(private Product $products, private VariationProduct $variations,
    private OptionProduct $option_product, private ExcludeProduct $excludes, private ExtraProduct $extra){}
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

        // variations[][options][][extra][][extra_names][{extra_name, tranlation_id, tranlation_name}], 
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
            }// add excludes
            if ($request->extra) {
                foreach ($request->extra as $item) {
                    $new_extra = $this->extra
                    ->create([
                        'name' => $item['names'][0]['extra_name'],
                        'price' => $item['extra_price'],
                        'product_id' => $product->id
                    ]);
                    $extra_num[] = $new_extra;
                    foreach ($item['names'] as $key => $element) {
                        if (!empty($element['extra_name'])) {
                            $new_extra->translations()->create([
                                'locale' => $element['tranlation_name'],
                                'key' => $item['names'][0]['extra_name'],
                                'value' => $element['extra_name'],
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
                            foreach ($element['extra'] as $key => $extra) {
                                // ['extra_names']
                                $extra_item = $this->extra
                                ->create([
                                    'name' => $extra['extra_names'][0]['extra_name'],
                                    'price' => $extra['extra_price'],
                                    'product_id' => $product->id,
                                    'variation_id' => $variation->id,
                                    'option_id' => $option->id
                                ]);// add extra for option
        
                                foreach ($extra['extra_names'] as $key => $value) {
                                    if (!empty($value['extra_name'])) {
                                        $extra_item->translations()->create([
                                            'locale' => $value['tranlation_name'],
                                            'key' => $extra['extra_names'][0]['extra_name'],
                                            'value' => $value['extra_name'],
                                        ]);
                                    }
                                }
                                foreach ($extra_num as $extra_num_item) {
                                    $extra_num_item->delete();
                                }
                            }
                        }
                    } 
                }
            }
    
            return response()->json([
                'success' => $request->all()
            ]);
        } catch (\Throwable $th) {
            $this->products
            ->where('id', $product_id)
            ->delete();
            return response()->json([
                'faild' => 'Something wrong'
            ], 400);
        } 
    }

    public function modify(ProductRequest $request, $id){
        // https://bcknd.food2go.online/admin/product/update/{id}
        // Keys
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
            foreach ($request->extra as $item) {
                $new_extra = $this->extra
                ->create([
                    'name' => $item['names'][0]['extra_name'],
                    'price' => $item['extra_price'],
                    'product_id' => $product->id
                ]);
                $extra_num[] = $new_extra;
                foreach ($item['names'] as $key => $element) {
                    if (!empty($element['extra_name'])) {
                        $new_extra->translations()->create([
                            'locale' => $element['tranlation_name'],
                            'key' => $item['names'][0]['extra_name'],
                            'value' => $element['extra_name'],
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
                                // ['extra_names']
                                $extra_item = $this->extra
                                ->create([
                                    'name' => $extra['extra_names'][0]['extra_name'],
                                    'price' => $extra['extra_price'],
                                    'product_id' => $product->id,
                                    'variation_id' => $variation->id,
                                    'option_id' => $option->id
                                ]);// add extra for option
        
                                foreach ($extra['extra_names'] as $key => $value) {
                                    if (!empty($value['extra_name'])) {
                                        $extra_item->translations()->create([
                                            'locale' => $value['tranlation_name'],
                                            'key' => $extra['extra_names'][0]['extra_name'],
                                            'value' => $value['extra_name'],
                                        ]);
                                    }
                                }
                                foreach ($extra_num as $extra_num_item) {
                                    $extra_num_item->delete();
                                }
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
}
