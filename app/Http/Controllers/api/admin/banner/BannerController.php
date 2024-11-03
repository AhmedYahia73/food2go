<?php

namespace App\Http\Controllers\api\admin\banner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\trait\image;
use App\Http\Requests\admin\banner\BannerRequest;

use App\Models\Banner;
use App\Models\Translation;

class BannerController extends Controller
{
    public function __construct(private Banner $banner, private Translation $translations){}
    protected $bannerRequest = [
        'order',
        'translation_id',
        'link'
    ];
    use image;

    public function view(){
        // https://backend.food2go.pro/admin/banner
        $banners = $this->banner
        ->orderBy('order')
        ->get();
        $translations = $this->translations
        ->get();

        return response()->json([
            'banners' => $banners,
            'translations' => $translations,
        ]);
    }
    
    public function create(BannerRequest $request){
        // https://backend.food2go.pro/admin/banner/add
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
        // https://backend.food2go.pro/admin/banner/update/{id}
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
        // https://backend.food2go.pro/admin/banner/delete/{id}
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