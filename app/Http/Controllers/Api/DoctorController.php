<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DoctorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $doctors = Doctor::query()
            ->with(['user', 'department'])
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('specialization', 'like', "%{$search}%")
                        ->orWhere('degree', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($doctors);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);

        return response()->json(['data' => Doctor::create($data)->load(['user', 'department'])], 201);
    }

    public function show(Doctor $doctor): JsonResponse
    {
        return response()->json(['data' => $doctor->load(['user', 'department', 'schedules', 'appointments.service'])]);
    }

    public function update(Request $request, Doctor $doctor): JsonResponse
    {
        $data = $this->validateData($request, $doctor);
        $doctor->update($data);

        return response()->json(['data' => $doctor->fresh(['user', 'department'])]);
    }

    public function destroy(Doctor $doctor): JsonResponse
    {
        $doctor->delete();

        return response()->json(['message' => 'Đã xóa bác sĩ.']);
    }

    private function validateData(Request $request, ?Doctor $doctor = null): array
    {
        return $request->validate([
            'user_id' => ['nullable', 'exists:users,id', Rule::unique('doctors', 'user_id')->ignore($doctor?->id)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'name' => [$doctor ? 'sometimes' : 'required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('doctors', 'email')->ignore($doctor?->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'degree' => ['nullable', 'string', 'max:255'],
            'experience_years' => ['sometimes', 'integer', 'min:0', 'max:255'],
            'bio' => ['nullable', 'string'],
            'consultation_fee' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
