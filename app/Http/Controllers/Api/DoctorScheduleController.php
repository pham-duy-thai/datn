<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $schedules = DoctorSchedule::query()
            ->with('doctor.department')
            ->when($request->filled('doctor_id'), fn ($query) => $query->where('doctor_id', $request->integer('doctor_id')))
            ->when($request->filled('weekday'), fn ($query) => $query->where('weekday', $request->integer('weekday')))
            ->when($request->filled('date'), fn ($query) => $query->where('weekday', Carbon::parse($request->date('date'))->dayOfWeekIso))
            ->when($request->filled('available'), fn ($query) => $query->where('is_available', $request->boolean('available')))
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->paginate($request->integer('per_page', 15));

        return response()->json($schedules);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);

        return response()->json(['data' => DoctorSchedule::create($data)->load('doctor')], 201);
    }

    public function show(DoctorSchedule $doctorSchedule): JsonResponse
    {
        return response()->json(['data' => $doctorSchedule->load(['doctor.department', 'appointments'])]);
    }

    public function update(Request $request, DoctorSchedule $doctorSchedule): JsonResponse
    {
        $data = $this->validateData($request, true);
        $doctorSchedule->update($data);

        return response()->json(['data' => $doctorSchedule->fresh('doctor')]);
    }

    public function destroy(DoctorSchedule $doctorSchedule): JsonResponse
    {
        $doctorSchedule->delete();

        return response()->json(['message' => 'Đã xóa lịch làm việc của bác sĩ.']);
    }

    private function validateData(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'doctor_id' => [$partial ? 'sometimes' : 'required', 'exists:doctors,id'],
            'weekday' => [$partial ? 'sometimes' : 'required', 'integer', 'between:1,7'],
            'start_time' => [$partial ? 'sometimes' : 'required', 'date_format:H:i'],
            'end_time' => [$partial ? 'sometimes' : 'required', 'date_format:H:i', 'after:start_time'],
            'room' => ['nullable', 'string', 'max:255'],
            'max_patients' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'is_available' => ['sometimes', 'boolean'],
        ]);
    }
}
