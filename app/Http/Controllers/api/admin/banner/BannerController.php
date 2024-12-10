<?php

namespace App\Http\Controllers\api\admin\banner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\trait\image;
use App\Http\Requests\admin\banner\BannerRequest;

use App\Models\Banner;
use App\Models\Translation;
use App\Models\TranslationTbl;
use App\Models\Category;
use App\Models\Product;
use App\Models\Deal;

class BannerController extends Controller
{
    public function __construct(private Banner $banner, private Translation $translations,
    private Category $categories, private Product $products, private Deal $deals,
    private TranslationTbl $translation_tbl){}
    protected $bannerRequest = [
        'order',
        'category_id',
        'product_id',
        'deal_id',
    ];
    use image;

    public function view(){
        // https://bcknd.food2go.online/admin/banner
        $banners = $this->banner
        ->orderBy('order')
        ->with('category_banner', 'product', 'deal')
        ->get();
        $translations = $this->translations
        ->where('status', 1)
        ->get();
        $categories = $this->categories
        ->get();
        $products = $this->products
        ->get();
        $deals = $this->deals
        ->get();
        foreach ($banners as $item) {
            $arr = [];
            foreach ($translations as $key => $element) {
                $image = $this->translation_tbl
                ->where('locale', $element->name)
                ->where('translatable_id', $item->id)
                ->first();
                if ($key == 0) {
                    $arr[] = [
                        'image' => $item->image_link,
                        'tranlation_id' => $element->id,
                        'tranlation_name' => $element->name
                    ];
                }
                elseif (!empty($image)) {
                    $image = url('storage/' . $image->value);
                    $arr[] = [
                        'image' => $image,
                        'tranlation_id' => $element->id,
                        'tranlation_name' => $element->name
                    ];
                }
                else {
                    $arr[] = [
                        'image' => null,
                        'tranlation_id' => $element->id,
                        'tranlation_name' => $element->name
                    ];
                }
            }
            $item->images = $arr;
        }

        return response()->json([
            'banners' => $banners,
            'translations' => $translations,
            'categories' => $categories,
            'products' => $products,
            'deals' => $deals,
        ]);
    }
    
    public function create(BannerRequest $request){
        // https://bcknd.food2go.online/admin/banner/add
        // Keys
        // order, category_id, product_id, deal_id
        // images [{translation_id, tranlation_name, image}]
        $bannerRequest = $request->only($this->bannerRequest);
        $banner = [];
        $image_path = null;
        foreach ($request->images as $key => $item) {
            if ($key == 0) {
                if (!is_string($item['image'])) {
                    $image_path = $this->uploadFile($item['image'], 'admin/banner/image');
                    $bannerRequest['image'] = $image_path;
                }
                $bannerRequest['translation_id'] = $item['translation_id'];
                $banner = $this->banner
                ->create($bannerRequest);
            }
            else{
                if (!is_string($item['image'])) {
                    $image_translation_path = $this->uploadFile($item['image'], 'admin/banner/image');
                    $bannerRequest['image'] = $image_translation_path;
                }
                $banner->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $image_path,
                    'value' => $image_translation_path
                ]);
            }
        }

        return response()->json([
            'success' => $request->all()
        ]);
    }
    
    public function modify(BannerRequest $request, $id){
        // https://bcknd.food2go.online/admin/banner/update/{id}
        // Keys
        // order, category_id, product_id, deal_id
        // images [{translation_id, tranlation_name, image}]
        $bannerRequest = $request->only($this->bannerRequest);
        $banner = $this->banner
        ->where('id', $id)
        ->first();
        $banner_image = $banner->image;
        $image_path = null;
        foreach ($request->images as $key => $item) {
            if ($key == 0) {
                if (!is_string($item['image'])) {
                    $image_path = $this->uploadFile($item['image'], 'admin/banner/image');
                    $bannerRequest['image'] = $image_path;
                    $this->deleteImage($banner->image);
                }
                $bannerRequest['translation_id'] = $item['translation_id'];
                $banner->update($bannerRequest);
            }
            else{
                if (!is_string($item['image'])) {
                    $translation_tbl = $this->translation_tbl
                    ->where('key', $banner_image)
                    ->where('translatable_id', $banner->id)
                    ->where('locale', $item['tranlation_name'])
                    ->first();
                    $this->deleteImage($translation_tbl->value);
                    if (!empty($translation_tbl)) {
                        $translation_tbl->delete();
                    }
                    $banner->translations()->create([
                        'locale' => $item['tranlation_name'],
                        'key' => $banner->image,
                        'value' => $item['image']
                    ]);
                }
            }
        }

        return response()->json([
            'success' => 'You update data success'
        ]);
    }
    
    public function delete($id){
        // https://bcknd.food2go.online/admin/banner/delete/{id}
        $banner = $this->banner
        ->where('id', $id)
        ->first();
        $this->deleteImage($banner->image);
        $translation_tbl = $this->translation_tbl
        ->where('key', $banner->image)
        ->where('translatable_id', $banner->id)
        ->get();
        foreach ($translation_tbl as $item) {
            $this->deleteImage($item->value);
            if (!empty($translation_tbl)) {
                $item->delete();
            }
        }
        $banner->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
