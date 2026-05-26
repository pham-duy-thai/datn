<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $banners = Banner::query()
            ->when($request->filled('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))
            ->orderBy('sort_order')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($banners);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);

        return response()->json(['data' => Banner::create($data)], 201);
    }

    public function show(Banner $banner): JsonResponse
    {
        return response()->json(['data' => $banner]);
    }

    public function update(Request $request, Banner $banner): JsonResponse
    {
        $data = $this->validateData($request, true);
        $banner->update($data);

        return response()->json(['data' => $banner->fresh()]);
    }

    public function destroy(Banner $banner): JsonResponse
    {
        $banner->delete();

        return response()->json(['message' => 'Đã xóa banner.']);
    }

    private function validateData(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
    }
}
