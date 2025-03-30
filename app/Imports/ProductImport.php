<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\VariationProduct;
use App\Models\OptionProduct;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Keys
        // name, description
        // id, item_type, stock_type, number, price
        // product_time_status, from, to, points 

        // variations1_id, variations1_name, variations1_type, variations1_min, 
        // variations1_max, variations1_required, variations1_required
        // variations1_option1_id, variations1_option1_name, variations1_option1_price
        // variations1_option1_status, variations1_option1_points
        // variations1_option2_id, variations1_option2_name, variations1_option2_price
        // variations1_option2_status, variations1_option2_points
        // variations1_option3_id, variations1_option3_name, variations1_option3_price
        // variations1_option3_status, variations1_option3_points

        // variations2_id, variations2_name, variations2_type, variations2_min, 
        // variations2_max, variations2_required, variations2_required
        // variations2_option1_id, variations2_option1_name, variations2_option1_price
        // variations2_option1_status, variations2_option1_points
        // variations2_option2_id, variations2_option2_name, variations2_option2_price
        // variations2_option2_status, variations2_option2_points
        // variations2_option3_id, variations2_option3_name, variations2_option3_price
        // variations2_option3_status, variations2_option3_points
        Product::where('id', $row['id'])
        ->update([
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
        ]);
        if (isset($row['variations1_id']) && is_numeric($row['variations1_id'])) {
            VariationProduct::where('id', $row['variations1_id'])
            ->update([
                'name' => $row['variations1_name'],
                'type' => $row['variations1_type'],
                'min' => empty($row['variations1_min']) ? null: $row['variations1_min'],
                'min' => empty($row['variations1_max']) ? null: $row['variations1_max'], 
                'required' => $row['variations1_required'], 
            ]);
            if (isset($row['variations1_option1_id']) && is_numeric($row['variations1_option1_id'])) {
                OptionProduct::where('id', $row['variations1_option1_id'])
                ->update([
                    'name' => $row['variations1_option1_name'],
                    'price' => $row['variations1_option1_price'],
                    'status' => $row['variations1_option1_status'],
                    'points' => is_numeric($row['variations1_option1_points']) ?$row['variations1_option1_points']  :null ,
                ]);
            }
            elseif (!empty($row['variations1_option1_name'])) {  
                OptionProduct::create([
                    'name' => $row['variations1_option1_name'],
                    'price' => $row['variations1_option1_price'],
                    'status' => $row['variations1_option1_status'],
                    'points' => is_numeric($row['variations1_option1_points']) ?$row['variations1_option1_points']  :null ,
                    'product_id' => $row['id'], 
                    'variation_id' => $row['variations1_id'], 
                ]);
            }
            // ___________________________________________________
            
            if (isset($row['variations1_option2_id']) && is_numeric($row['variations1_option2_id'])) {
                OptionProduct::where('id', $row['variations1_option2_id'])
                ->update([
                    'name' => $row['variations1_option2_name'],
                    'price' => $row['variations1_option2_price'],
                    'status' => $row['variations1_option2_status'],
                    'points' => is_numeric($row['variations1_option2_points']) ?$row['variations1_option2_points']  :null ,
                ]);
            }
            elseif (!empty($row['variations1_option2_name'])) {  
                OptionProduct::create([
                    'name' => $row['variations1_option2_name'],
                    'price' => $row['variations1_option2_price'],
                    'status' => $row['variations1_option2_status'],
                    'points' => is_numeric($row['variations1_option2_points']) ?$row['variations1_option2_points']  :null ,
                    'product_id' => $row['id'], 
                    'variation_id' => $row['variations1_id'], 
                ]);
            }
            // ___________________________________________________
            
            if (isset($row['variations1_option3_id']) && is_numeric($row['variations1_option3_id'])) {
                OptionProduct::where('id', $row['variations1_option3_id'])
                ->update([
                    'name' => $row['variations1_option3_name'],
                    'price' => $row['variations1_option3_price'],
                    'status' => $row['variations1_option3_status'],
                    'points' => is_numeric($row['variations1_option3_points']) ?$row['variations1_option3_points']  :null ,
                ]);
            }
            elseif (!empty($row['variations1_option3_name'])) {  
                OptionProduct::create([
                    'name' => $row['variations1_option3_name'],
                    'price' => $row['variations1_option3_price'],
                    'status' => $row['variations1_option3_status'],
                    'points' => is_numeric($row['variations1_option3_points']) ?$row['variations1_option3_points']  :null ,
                    'product_id' => $row['id'], 
                    'variation_id' => $row['variations1_id'], 
                ]);
            }
        }
        elseif (!empty($row['variations1_name'])) {
            $variation = VariationProduct::
            create([
                'name' => $row['variations1_name'],
                'type' => $row['variations1_type'],
                'min' => $row['variations1_min'],
                'max' => $row['variations1_max'],
                'required' => $row['variations1_required'], 
                'product_id' => $row['id'], 
            ]);
            if (!empty($row['variations1_option1_name'])) {  
                OptionProduct::create([
                    'name' => $row['variations1_option1_name'],
                    'price' => $row['variations1_option1_price'],
                    'status' => $row['variations1_option1_status'],
                    'points' => is_numeric($row['variations1_option1_points']) ?$row['variations1_option1_points']  :null ,
                    'product_id' => $row['id'], 
                    'variation_id' => $variation->id, 
                ]);
            } 
            if (!empty($row['variations1_option2_name'])) {  
                OptionProduct::create([
                    'name' => $row['variations1_option2_name'],
                    'price' => $row['variations1_option2_price'],
                    'status' => $row['variations1_option2_status'],
                    'points' => is_numeric($row['variations1_option2_points']) ?$row['variations1_option2_points']  :null ,
                    'product_id' => $row['id'],  
                    'variation_id' => $variation->id, 
                ]);
            }
            if (!empty($row['variations1_option3_name'])) {  
                OptionProduct::create([
                    'name' => $row['variations1_option3_name'],
                    'price' => $row['variations1_option3_price'],
                    'status' => $row['variations1_option3_status'],
                    'points' => is_numeric($row['variations1_option3_points']) ?$row['variations1_option3_points']  :null ,
                    'product_id' => $row['id'], 
                    'variation_id' => $variation->id, 
                ]);
            }
        }
        // -------------------------------------------------
        if (isset($row['variations2_id']) && is_numeric($row['variations2_id'])) {
            VariationProduct::where('id', $row['variations2_id'])
            ->update([
                'name' => $row['variations2_name'],
                'type' => $row['variations2_type'],
                'min' => $row['variations2_min'],
                'max' => $row['variations2_max'],
                'required' => $row['variations2_required'], 
            ]);
            if (isset($row['variations2_option1_id']) && is_numeric($row['variations2_option1_id'])) {
                OptionProduct::where('id', $row['variations2_option1_id'])
                ->update([
                    'name' => $row['variations2_option1_name'],
                    'price' => $row['variations2_option1_price'],
                    'status' => $row['variations2_option1_status'],
                    'points' => is_numeric($row['variations2_option1_points']) ?$row['variations2_option1_points']  :null ,
                ]);
            }
            elseif (!empty($row['variations2_option1_name'])) {  
                OptionProduct::create([
                    'name' => $row['variations2_option1_name'],
                    'price' => $row['variations2_option1_price'],
                    'status' => $row['variations2_option1_status'],
                    'points' => is_numeric($row['variations2_option1_points']) ?$row['variations2_option1_points']  :null ,
                    'product_id' => $row['id'], 
                    'variation_id' => $row['variations2_id'], 
                ]);
            }
            // ___________________________________________________
            
            if (isset($row['variations2_option2_id']) && is_numeric($row['variations2_option2_id'])) {
                OptionProduct::where('id', $row['variations2_option2_id'])
                ->update([
                    'name' => $row['variations2_option2_name'],
                    'price' => $row['variations2_option2_price'],
                    'status' => $row['variations2_option2_status'],
                    'points' => is_numeric($row['variations2_option2_points']) ?$row['variations2_option2_points']  :null ,
                ]);
            }
            elseif (!empty($row['variations2_option2_name'])) {  
                OptionProduct::create([
                    'name' => $row['variations2_option2_name'],
                    'price' => $row['variations2_option2_price'],
                    'status' => $row['variations2_option2_status'],
                    'points' => is_numeric($row['variations2_option2_points']) ?$row['variations2_option2_points']  :null ,
                    'product_id' => $row['id'], 
                    'variation_id' => $row['variations2_id'], 
                ]);
            }
            // ___________________________________________________
            
            if (isset($row['variations2_option3_id']) && is_numeric($row['variations2_option3_id'])) {
                OptionProduct::where('id', $row['variations2_option3_id'])
                ->update([
                    'name' => $row['variations2_option3_name'],
                    'price' => $row['variations2_option3_price'],
                    'status' => $row['variations2_option3_status'],
                    'points' => is_numeric($row['variations2_option3_points']) ?$row['variations2_option3_points']  :null ,
                ]);
            }
            elseif (!empty($row['variations2_option3_name'])) {  
                OptionProduct::create([
                    'name' => $row['variations2_option3_name'],
                    'price' => $row['variations2_option3_price'],
                    'status' => $row['variations2_option3_status'],
                    'points' => is_numeric($row['variations2_option3_points']) ?$row['variations2_option3_points']  :null ,
                    'product_id' => $row['id'], 
                    'variation_id' => $row['variations2_id'], 
                ]);
            }
        }
        elseif (!empty($row['variations2_name'])) {
            $variation = VariationProduct::
            create([
                'name' => $row['variations2_name'],
                'type' => $row['variations2_type'], 
                'min' => empty($row['variations2_min']) ? null: $row['variations2_min'],
                'min' => empty($row['variations2_max']) ? null: $row['variations2_max'], 
                'required' => $row['variations2_required'], 
                'product_id' => $row['id'], 
            ]);
            if (!empty($row['variations2_option1_name'])) {  
                OptionProduct::create([
                    'name' => $row['variations2_option1_name'],
                    'price' => $row['variations2_option1_price'],
                    'status' => $row['variations2_option1_status'],
                    'points' => is_numeric($row['variations2_option1_points']) ?$row['variations2_option1_points']  :null ,
                    'product_id' => $row['id'], 
                    'variation_id' => $variation->id, 
                ]);
            } 
            if (!empty($row['variations2_option2_name'])) {  
                OptionProduct::create([
                    'name' => $row['variations2_option2_name'],
                    'price' => $row['variations2_option2_price'],
                    'status' => $row['variations2_option2_status'],
                    'points' => is_numeric($row['variations2_option2_points']) ?$row['variations2_option2_points']  :null ,
                    'product_id' => $row['id'],  
                    'variation_id' => $variation->id, 
                ]);
            }
            if (!empty($row['variations2_option3_name'])) {  
                OptionProduct::create([
                    'name' => $row['variations2_option3_name'],
                    'price' => $row['variations2_option3_price'],
                    'status' => $row['variations2_option3_status'],
                    'points' => is_numeric($row['variations2_option3_points']) ?$row['variations2_option3_points']  :null ,
                    'product_id' => $row['id'], 
                    'variation_id' => $variation->id, 
                ]);
            }
        }
        
    }
}
