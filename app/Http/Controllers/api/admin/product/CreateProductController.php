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
        // https://backend.food2go.pro/admin/product/add
        // Keys
        // category_id, sub_category_id, item_type, stock_type, number, price
        // product_time_status, from, to, discount_id, tax_id, status, recommended, image, points
        // addons[]
        // excludes[][{exclude_name, tranlation_id, tranlation_name}]
        // extra[][{extra_name, extra_price, tranlation_id, tranlation_name}]
        // variations[][names][{name, tranlation_id, tranlation_name}] ,variations[][type],
        // variations[][min] ,variations[][max]
        // variations[][required], variations[][points]
        // variations[][options][][names][{name, tranlation_id, tranlation_name}], 
        // variations[][options][][price], variations[][options][][status], 
        // variations[][options][][extra_names][{extra_name, tranlation_id, tranlation_name}], 
        // variations[][options][][extra_price], 
        // product_names[{product_name, product_description, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $default = $request->product_names[0];
        foreach ($request->product_names as $item) {
            $this->translate($item['tranlation_name'], $default['product_name'], $item['product_name']);
            $this->translate($item['tranlation_name'], $default['product_description'], $item['product_description']);
        }
        $productRequest = $request->only($this->productRequest);
        $productRequest['name'] = $default['product_name'];
        $productRequest['description'] = $default['product_description'];
        $extra_num = [];

        if (is_file($request->image)) {
            $imag_path = $this->upload($request, 'image', 'admin/product/image');
            $productRequest['image'] = $imag_path;
        } // if send image upload it
        $product = $this->products->create($productRequest); // create product
        if ($request->addons) {
            $product->addons()->attach($request->addons); // add addons of product
        }
        if ($request->excludes) {
            foreach ($request->excludes as $item) {
                $this->excludes
                ->create([
                    'name' => $item[0]['exclude_name'],
                    'product_id' => $product->id
                ]);
                foreach ($item as $key => $element) {
                    $this->translate($element['tranlation_name'], $item[0]['exclude_name'], $element['exclude_name']);
                }
            }
        }// add excludes
        if ($request->extra) {
            foreach ($request->extra as $item) {
                $extra_num[] = $this->extra
                ->create([
                    'name' => $item[0]['extra_name'],
                    'price' => $item[0]['extra_price'],
                    'product_id' => $product->id
                ]);
                foreach ($item as $key => $element) {
                    $this->translate($element['tranlation_name'], $item[0]['extra_name'], $element['extra_name']);
                }
            }
        }// add extra
        if ($request->variations) {
            foreach ($request->variations as $item) {
                $variation = $this->variations
                ->create([
                    'name' => $item['names'][0]['name'],
                    'type' => $item['type'],
                    'min' => $item['min'] ?? null,
                    'max' => $item['max'] ?? null,
                    'points' => $item['points'],
                    'required' => $item['required'],
                    'product_id' => $product->id,
                ]); // add variation
                foreach ($item['names'] as $key => $element) {
                    $this->translate($element['tranlation_name'], $item['names'][0]['name'], $element['name']);
                }
                foreach ($item['options'] as $element) {
                    $option = $this->option_product
                    ->create([
                        'name' => $element['names'][0]['name'],
                        'price' => $element['price'],
                        'status' => $element['status'],
                        'product_id' => $product->id,
                        'variation_id' => $variation->id,
                    ]);// add options
                    foreach ($element['names'] as $key => $value) {
                        $this->translate($value['tranlation_name'], $element['names'][0]['name'], $value['name']);
                    }
                    if ($element['extra_names']) {
                        $this->extra
                        ->create([
                            'name' => $element['extra_names'][0]['extra_name'],
                            'price' => $element['extra_price'],
                            'product_id' => $product->id,
                            'variation_id' => $variation->id,
                            'option_id' => $option->id
                        ]);// add extra for option

                        foreach ($element['extra_names'] as $key => $value) {
                            $this->translate($value['tranlation_name'], $element['extra_names'][0]['extra_name'], $value['extra_name']);
                        }
                    }
                } 
            }
        }

        return response()->json([
            'success' => 'You add product success'
        ]);
    }

    public function modify(ProductRequest $request, $id){
        // https://backend.food2go.pro/admin/product/update/{id}
        // Keys
        // category_id, sub_category_id, item_type, stock_type, number, price
        // product_time_status, from, to, discount_id, tax_id, status, recommended, image, points
        // addons[]
        // excludes[][exclude_name]
        // extra[][extra_name], extra[][extra_price]
        // variations[][name] ,variations[][type] ,variations[][min] ,variations[][max]
        // variations[][required], variations[][points]
        // variations[][options][][name], variations[][options][][price],
        // variations[][options][][extra_name], variations[][options][][extra_price],
        //  variations[][options][][extra_number] ترتيبها
        // product_names[{product_name, product_description, tranlation_id, tranlation_name}]
        //  أول عنصر هو default language
        $default = $request->product_names[0];
        $extra_num = [];
        foreach ($request->product_names as $item) {
            $this->translate($item['tranlation_name'], $default['product_name'], $item['product_name']); 
            $this->translate($item['tranlation_name'], $default['product_description'], $item['product_description']); 
        }
        $productRequest = $request->only($this->productRequest);
        $productRequest['name'] = $default['product_name'];
        $productRequest['description'] = $default['product_description'];
        
        $product = $this->products->
        where('id', $id)
        ->first(); // get product
        if (is_file($request->image)) {
            $imag_path = $this->upload($request, 'image', 'admin/product/image');
            $productRequest['image'] = $imag_path;
            $this->deleteImage($product->image);
        } // if send image upload it and delete old image
        $product->update($productRequest); // create product
        if ($request->addons) {
            $product->addons()->sync($request->addons); // add addons of product
        }
        $this->excludes
        ->where('product_id', $id)
        ->delete(); // delete old excludes
        if ($request->excludes) {
            foreach ($request->excludes as $item) {
                $this->excludes
                ->create([
                    'name' => $item['exclude_name'],
                    'product_id' => $product->id
                ]);
            }
        }// add new excludes
        $this->extra
        ->where('product_id', $id)
        ->delete(); // delete old extra
        if ($request->extra) {
            foreach ($request->extra as $item) {
                $extra_num[] = $this->extra
                ->create([
                    'name' => $item['extra_name'],
                    'price' => $item['extra_price'],
                    'product_id' => $product->id
                ]);
            }
        }// add new extra
        $this->variations
        ->where('product_id', $id)
        ->delete(); // delete old product
        if ($request->variations) {
            foreach ($request->variations as $item) {
                $variation = $this->variations
                ->create([
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'points' => $item['points'],
                    'min' => $item['min'] ?? null,
                    'max' => $item['max'] ?? null,
                    'required' => $item['required'],
                    'product_id' => $product->id,
                ]); // add variation
                if ($item['options']) {
                    foreach ($item['options'] as $element) {
                        $option = $this->option_product
                        ->create([
                            'name' => $element['name'],
                            'price' => $element['price'],
                            'status' => $element['status'],
                            'product_id' => $product->id,
                            'variation_id' => $variation->id,
                        ]);
                        if ($element['extra_name']) {
                            $this->extra
                            ->create([
                                'name' => $element['extra_name'],
                                'price' => $element['extra_price'],
                                'product_id' => $product->id,
                                'variation_id' => $variation->id,
                                'option_id' => $option->id
                            ]);// add extra for option
                        }
                    } // add options
                }
            }
        }

        return response()->json([
            'success' => 'You update product success'
        ]);
    }

    public function delete($id){
        // https://backend.food2go.pro/admin/product/delete/{id}
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
