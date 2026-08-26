<?php

namespace App\Http\Controllers\api\admin\marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Category;

class MarketingController extends Controller
{
    public function category_clicks(Request $request)
    {      
        $validator = Validator::make($request->all(), [
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d'],
            'app_type' => ['sometimes', 'in:all,mobile,web'],
            'locale' => ['required', 'in:ar,en'],
            'sort' => ['sometimes', 'in:asc,desc'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $locale = $request->locale ?? "en";
        $categories = Category::with(['translations', 'clicks' => function ($query) use ($request) {
            if ($request->from) {
                $query->whereDate('created_at', '>=', $request->from);
            }
            if ($request->to) {
                $query->whereDate('created_at', '<=', $request->to);
            }
            if ($request->app_type && $request->app_type != "all") {
                $query->where('app_type', $request->app_type);
            }
        }])
        ->get()
        ->map(function ($category) use ($locale) {
            return [
                'id' => $category->id,
                'name' => $category->translations
                ->where("locale", $locale)
                ->where("key", $category->name)
                ->first()
                ?->value
                ?? $category->name,
                'clicks_count' => $category->clicks->count(),
            ];
        });

        if ($request->sort == 'desc') {
            $categories = $categories->sortByDesc('clicks_count');
        }
        else {
            $categories = $categories->sortBy('clicks_count');
        }

        return response()->json([
            'categories' => $categories->values(),
        ]);
    }
}
