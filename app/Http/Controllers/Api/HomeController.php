<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\News;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'banners' => Banner::where('is_active', true)->orderBy('sort_order')->limit(5)->get(),
            'departments' => Department::where('is_active', true)->withCount('doctors')->limit(8)->get(),
            'doctors' => Doctor::where('is_active', true)->with('department')->latest()->limit(8)->get(),
            'services' => Service::where('is_active', true)->with('department')->limit(8)->get(),
            'news' => News::where('status', 'published')->latest('published_at')->limit(6)->get(),
        ]);
    }
}