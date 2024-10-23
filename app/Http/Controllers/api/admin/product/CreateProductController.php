<?php

namespace App\Http\Controllers\api\admin\product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\product\ProductRequest;
use App\trait\image;

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

    public function create(ProductRequest $request){
        // https://backend.food2go.pro/admin/product/add
        // Keys
        // name, description, category_id, sub_category_id, item_type, stock_type, number, price
        // product_time_status, from, to, discount_id, tax_id, status, recommended, image, points
        // addons[]
        // excludes[][exclude_name]
        // extra[][extra_name], extra[][extra_price]
        // variations[][extra][][extra_name], variations[][extra][][extra_price], variations[][extra][][extra_id]
        // variations[][name] ,variations[][type] ,variations[][min] ,variations[][max]
        // variations[][required], variations[][points]
        // variations[][options][][name], variations[][options][][price]
        $productRequest = $request->only($this->productRequest);
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
                    'name' => $item['exclude_name'],
                    'product_id' => $product->id
                ]);
            }
        }// add excludes
        if ($request->extra) {
            foreach ($request->extra as $item) {
                $this->extra
                ->create([
                    'name' => $item['extra_name'],
                    'price' => $item['extra_price'],
                    'product_id' => $product->id
                ]);
            }
        }// add extra
        if ($request->variations) {
            foreach ($request->variations as $item) {
                $variation = $this->variations
                ->create([
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'min' => $item['min'] ?? null,
                    'max' => $item['max'] ?? null,
                    'points' => $item['points'],
                    'required' => $item['required'],
                    'product_id' => $product->id,
                ]); // add variation
                foreach ($item['extra'] as $element) {
                    $this->extra
                    ->create([
                        'name' => $element['extra_name'],
                        'price' => $element['extra_price'],
                        'product_id' => $product->id,
                        'variation_id' => $variation->id,
                        'extra_id' => $element['extra_id'],
                    ]);
                }
                foreach ($item['options'] as $element) {
                    $this->option_product
                    ->create([
                        'name' => $element['name'],
                        'price' => $element['price'],
                        'product_id' => $product->id,
                        'variation_id' => $variation->id,
                    ]);
                } // add options
            }
        }

        return response()->json([
            'success' => 'You add product success'
        ]);
    }

    public function modify(ProductRequest $request, $id){
        // https://backend.food2go.pro/admin/product/update/{id}
        // Keys
        // name, description, category_id, sub_category_id, item_type, stock_type, number, price
        // product_time_status, from, to, discount_id, tax_id, status, recommended, image, points
        // addons[]
        // excludes[][exclude_name]
        // extra[][extra_name], extra[][extra_price]
        // variations[][extra][][extra_name], variations[][extra][][extra_price], variations[][extra][][extra_id]
        // variations[][name] ,variations[][type] ,variations[][min] ,variations[][max]
        // variations[][required], variations[][points]
        // variations[][options][][name], variations[][options][][price]
        $productRequest = $request->only($this->productRequest);
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
                $this->extra
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
                foreach ($item['extra'] as $element) {
                    $this->extra
                    ->create([
                        'name' => $element['extra_name'],
                        'price' => $element['extra_price'],
                        'product_id' => $product->id,
                        'variation_id' => $variation->id,
                        'extra_id' => $element['extra_id'],
                    ]);
                }
                foreach ($item['options'] as $element) {
                    $this->option_product
                    ->create([
                        'name' => $element['name'],
                        'price' => $element['price'],
                        'product_id' => $product->id,
                        'variation_id' => $variation->id,
                    ]);
                } // add options
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
