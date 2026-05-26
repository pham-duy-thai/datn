<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $services = Service::query()
            ->with('department')
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($services);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);

        return response()->json(['data' => Service::create($data)->load('department')], 201);
    }

    public function show(Service $service): JsonResponse
    {
        return response()->json(['data' => $service->load('department')]);
    }

    public function update(Request $request, Service $service): JsonResponse
    {
        $data = $this->validateData($request, $service);
        $service->update($data);

        return response()->json(['data' => $service->fresh('department')]);
    }

    public function destroy(Service $service): JsonResponse
    {
        $service->delete();

        return response()->json(['message' => 'Đã xóa dịch vụ.']);
    }

    private function validateData(Request $request, ?Service $service = null): array
    {
        return $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'name' => [$service ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => [$service ? 'sometimes' : 'required', 'string', 'max:255', Rule::unique('services', 'slug')->ignore($service?->id)],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'image' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
