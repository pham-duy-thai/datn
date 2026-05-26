<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MedicalRecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $records = MedicalRecord::query()
            ->with(['appointment.service', 'user', 'doctor.department'])
            ->tap(fn ($query) => $this->applyRoleScope($query, $request))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('doctor_id'), fn ($query) => $query->where('doctor_id', $request->integer('doctor_id')))
            ->when($request->filled('appointment_id'), fn ($query) => $query->where('appointment_id', $request->integer('appointment_id')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('examined_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('examined_at', '<=', $request->date('to')))
            ->latest('examined_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json($records);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);
        $this->authorizeMedicalRecordMutation($request, $data['doctor_id'] ?? null);

        return response()->json(['data' => MedicalRecord::create($data)->load(['appointment', 'user', 'doctor'])], 201);
    }

    public function show(MedicalRecord $medicalRecord): JsonResponse
    {
        $this->authorizeMedicalRecordAccess(request(), $medicalRecord);

        return response()->json(['data' => $medicalRecord->load(['appointment.service', 'user', 'doctor.department'])]);
    }

    public function update(Request $request, MedicalRecord $medicalRecord): JsonResponse
    {
        $this->authorizeMedicalRecordAccess($request, $medicalRecord, mutation: true);

        $data = $this->validateData($request, $medicalRecord);
        $medicalRecord->update($data);

        return response()->json(['data' => $medicalRecord->fresh(['appointment', 'user', 'doctor'])]);
    }

    public function destroy(MedicalRecord $medicalRecord): JsonResponse
    {
        $this->authorizeMedicalRecordAccess(request(), $medicalRecord, mutation: true);

        $medicalRecord->delete();

        return response()->json(['message' => 'Đã xóa hồ sơ bệnh án.']);
    }

    private function validateData(Request $request, ?MedicalRecord $medicalRecord = null): array
    {
        return $request->validate([
            'appointment_id' => ['nullable', 'exists:appointments,id', Rule::unique('medical_records', 'appointment_id')->ignore($medicalRecord?->id)],
            'user_id' => ['nullable', 'exists:users,id'],
            'doctor_id' => ['nullable', 'exists:doctors,id'],
            'examined_at' => ['nullable', 'date'],
            'symptoms' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'treatment' => ['nullable', 'string'],
            'prescription' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'follow_up_date' => ['nullable', 'date'],
        ]);
    }

    private function applyRoleScope($query, Request $request): void
    {
        $user = $request->user();

        if (! $user) {
            return;
        }

        if ($user->role === 'patient') {
            $query->where('user_id', $user->id);
        }

        if ($user->role === 'doctor') {
            $doctorId = $user->doctor?->id;
            $query->where('doctor_id', $doctorId ?: 0);
        }
    }

    private function authorizeMedicalRecordAccess(Request $request, MedicalRecord $medicalRecord, bool $mutation = false): void
    {
        $user = $request->user();

        if (! $user || in_array($user->role, ['admin', 'receptionist'], true)) {
            return;
        }

        if (! $mutation && $user->role === 'patient') {
            abort_unless($medicalRecord->user_id === $user->id, 403);

            return;
        }

        if ($user->role === 'doctor') {
            abort_unless($user->doctor && $medicalRecord->doctor_id === $user->doctor->id, 403);

            return;
        }

        abort(403);
    }

    private function authorizeMedicalRecordMutation(Request $request, ?int $doctorId): void
    {
        $user = $request->user();

        if (! $user || in_array($user->role, ['admin', 'receptionist'], true)) {
            return;
        }

        if ($user->role === 'doctor') {
            abort_unless($user->doctor && (! $doctorId || $doctorId === $user->doctor->id), 403);

            return;
        }

        abort(403);
    }
}
