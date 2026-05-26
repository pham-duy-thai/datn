<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NewsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $news = News::query()
            ->with('user')
            ->when($request->boolean('published'), fn ($query) => $query->where('status', 'published'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            })
            ->latest('published_at')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($news);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);

        return response()->json(['data' => News::create($data)->load('user')], 201);
    }

    public function show(string $news): JsonResponse
    {
        return response()->json(['data' => $this->findByIdOrSlug($news)->load('user')]);
    }

    public function update(Request $request, string $news): JsonResponse
    {
        $record = $this->findByIdOrSlug($news);
        $data = $this->validateData($request, $record);
        $record->update($data);

        return response()->json(['data' => $record->fresh('user')]);
    }

    public function destroy(string $news): JsonResponse
    {
        $this->findByIdOrSlug($news)->delete();

        return response()->json(['message' => 'Đã xóa tin tức.']);
    }

    private function validateData(Request $request, ?News $news = null): array
    {
        return $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'title' => [$news ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => [$news ? 'sometimes' : 'required', 'string', 'max:255', Rule::unique('news', 'slug')->ignore($news?->id)],
            'excerpt' => ['nullable', 'string'],
            'content' => [$news ? 'sometimes' : 'required', 'string'],
            'thumbnail' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
        ]);
    }

    private function findByIdOrSlug(string $value): News
    {
        return News::query()
            ->where('id', $value)
            ->orWhere('slug', $value)
            ->firstOrFail();
    }
}
