<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements  ToModel, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function model(array $row)
    {
        $product = Product::
        create([
            'name' => $row['name'] ?? null,
            'description' => $row['description'] ?? null,
            'item_type' => $row['item_type'],
            'stock_type' => $row['stock_type'],
            'number' => $row['number'],
            'price' => $row['price'],
            'product_time_status' => $row['product_time_status'],
            'from' => $row['from'],
            'to' => $row['to'], 
            'points' => $row['points'], 
            'order' => $row['order'],
            'recipe' => $row['recipe_status'],
            'weight_point' => $row['weight_point'],
            'favourite' => $row['favourite'],
            'recommended' => $row['recommended'],
            'status' => $row['status'],
            'weight_point' => $row['weight_point'],
            'weight_status' => $row['weight_status'],
            'product_code' => $row['product_code'], 
        ]);
        $product->translations()->create([  
            'locale' => "en",
            'key' => $row['name'],
            'value' => $row['name']
        ]);
        $product->translations()->create([  
            'locale' => "ar",
            'key' => $row['name'],
            'value' => $row['ar_name']
        ]);
    }
}
