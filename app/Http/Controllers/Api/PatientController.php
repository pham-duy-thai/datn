<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $patients = User::query()
            ->where('role', 'patient')
            ->withCount(['appointments', 'medicalRecords'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search');

                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($patients);
    }

    public function show(User $patient): JsonResponse
    {
        $this->ensurePatient($patient);

        return response()->json([
            'data' => $patient->load([
                'appointments.doctor.department',
                'appointments.service',
                'appointments.medicalRecord',
                'medicalRecords.doctor.department',
                'medicalRecords.appointment.service',
            ]),
        ]);
    }

    public function update(Request $request, User $patient): JsonResponse
    {
        $this->ensurePatient($patient);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($patient->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:255'],
        ]);

        $patient->update($data);

        return response()->json(['data' => $patient->fresh()]);
    }

    public function appointments(User $patient): JsonResponse
    {
        $this->ensurePatient($patient);

        return response()->json([
            'data' => $patient->appointments()
                ->with(['doctor.department', 'service', 'medicalRecord'])
                ->latest('appointment_date')
                ->latest('appointment_time')
                ->get(),
        ]);
    }

    public function medicalRecords(User $patient): JsonResponse
    {
        $this->ensurePatient($patient);

        return response()->json([
            'data' => $patient->medicalRecords()
                ->with(['appointment.service', 'doctor.department'])
                ->latest('examined_at')
                ->get(),
        ]);
    }

    private function ensurePatient(User $patient): void
    {
        abort_unless($patient->role === 'patient', 404, 'Không tìm thấy bệnh nhân.');
    }
}
