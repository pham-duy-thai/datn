<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $departments = Department::query()
            ->withCount(['doctors', 'services'])
            ->when($request->filled('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($departments);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);

        return response()->json(['data' => Department::create($data)], 201);
    }

    public function show(Department $department): JsonResponse
    {
        return response()->json(['data' => $department->load(['doctors', 'services'])]);
    }

    public function update(Request $request, Department $department): JsonResponse
    {
        $data = $this->validateData($request, $department);
        $department->update($data);

        return response()->json(['data' => $department->fresh()]);
    }

    public function destroy(Department $department): JsonResponse
    {
        $department->delete();

        return response()->json(['message' => 'Đã xóa chuyên khoa.']);
    }

    private function validateData(Request $request, ?Department $department = null): array
    {
        return $request->validate([
            'name' => [$department ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => [$department ? 'sometimes' : 'required', 'string', 'max:255', Rule::unique('departments', 'slug')->ignore($department?->id)],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
