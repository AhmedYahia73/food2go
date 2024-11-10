<?php

namespace App\Http\Controllers\api\admin\banner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\trait\image;
use App\Http\Requests\admin\banner\BannerRequest;

use App\Models\Banner;
use App\Models\Translation;
use App\Models\Category;
use App\Models\Product;
use App\Models\Deal;

class BannerController extends Controller
{
    public function __construct(private Banner $banner, private Translation $translations,
    private Category $categories, private Product $products, private Deal $deals){}
    protected $bannerRequest = [
        'order',
        'translation_id',
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
        ->get();
        $categories = $this->categories
        ->get();
        $products = $this->products
        ->get();
        $deals = $this->deals
        ->get();

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
        $bannerRequest = $request->only($this->bannerRequest);
        if (is_file($request->image)) {
            $image_path = $this->upload($request, 'image', 'admin/banner/image');
            $bannerRequest['image'] = $image_path;
        }
        $this->banner
        ->create($bannerRequest);

        return response()->json([
            'success' => 'You add banner success'
        ]);
    }
    
    public function modify(BannerRequest $request, $id){
        // https://bcknd.food2go.online/admin/banner/update/{id}
        $bannerRequest = $request->only($this->bannerRequest);
        $banner = $this->banner
        ->where('id', $id)
        ->first();
        if (is_file($request->image)) {
            $image_path = $this->upload($request, 'image', 'admin/banner/image');
            $bannerRequest['image'] = $image_path;
            $this->deleteImage($banner->image);
        }
        $banner->update($bannerRequest);

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
        $banner->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
