<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Services\MedicalAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MedicalAiController extends Controller
{
    public function __invoke(Request $request, MedicalAiService $medicalAi): JsonResponse
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['diagnosis', 'summary', 'prescription', 'record_draft'])],
            'record_id' => ['nullable', 'integer', 'exists:medical_records,id'],
            'symptoms' => ['nullable', 'string', 'max:4000'],
            'medical_history' => ['nullable', 'string', 'max:4000'],
            'lab_results' => ['nullable', 'string', 'max:4000'],
            'vital_signs' => ['nullable', 'string', 'max:2000'],
            'allergies' => ['nullable', 'string', 'max:2000'],
            'current_medications' => ['nullable', 'string', 'max:4000'],
            'note' => ['nullable', 'string', 'max:4000'],
        ]);

        $record = null;

        if (! empty($data['record_id'])) {
            $doctor = $request->user()->doctor;
            $record = MedicalRecord::query()
                ->with(['user', 'doctor', 'appointment.service'])
                ->findOrFail($data['record_id']);

            abort_unless($doctor && $record->doctor_id === $doctor->id, 403);
        }

        return response()->json([
            'data' => $medicalAi->assist($data, $request->user(), $record),
        ]);
    }
}
