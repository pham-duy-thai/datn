<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\LabResult;
use App\Models\MedicalRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DoctorMedicalRecordController extends Controller
{
    public function index(Request $request): View
    {
        $doctor = $this->doctor($request);

        $records = $doctor->medicalRecords()
            ->with(['user', 'appointment.service'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search');

                $query->where(function ($query) use ($search): void {
                    $query->where('diagnosis', 'like', "%{$search}%")
                        ->orWhere('symptoms', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%"))
                        ->orWhereHas('appointment', fn ($query) => $query
                            ->where('patient_name', 'like', "%{$search}%")
                            ->orWhere('patient_email', 'like', "%{$search}%")
                            ->orWhere('patient_phone', 'like', "%{$search}%"));
                });
            })
            ->latest('examined_at')
            ->paginate(12)
            ->withQueryString();

        return view('pages.doctor-records.index', compact('records'));
    }

    public function create(Request $request): View
    {
        return view('pages.doctor-records.form', [
            'record' => new MedicalRecord(),
            'appointments' => $this->availableAppointments($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $doctor = $this->doctor($request);
        $data = $this->validatedData($request);
        $appointment = $this->ownedAppointment($request, (int) $data['appointment_id']);

        $record = MedicalRecord::create([
            ...$data,
            'user_id' => $appointment->user_id,
            'doctor_id' => $doctor->id,
        ]);
        $this->updatePatientProfile($request, $appointment->user);

        return redirect()
            ->route('doctor.records.edit', $record)
            ->with('success', 'Đã tạo hồ sơ bệnh nhân.');
    }

    public function edit(Request $request, MedicalRecord $medicalRecord): View
    {
        $this->authorizeRecord($request, $medicalRecord);

        return view('pages.doctor-records.form', [
            'record' => $medicalRecord->load([
                'user.medicalRecords.doctor',
                'appointment.service',
                'labResults',
                'medicalImages',
            ]),
            'appointments' => $this->availableAppointments($request, $medicalRecord),
        ]);
    }

    public function update(Request $request, MedicalRecord $medicalRecord): RedirectResponse
    {
        $this->authorizeRecord($request, $medicalRecord);
        $data = $this->validatedData($request);
        $appointment = $this->ownedAppointment($request, (int) $data['appointment_id'], $medicalRecord);

        $medicalRecord->update([
            ...$data,
            'user_id' => $appointment->user_id,
            'doctor_id' => $request->user()->doctor->id,
        ]);
        $this->updatePatientProfile($request, $appointment->user);

        return redirect()
            ->route('doctor.records.edit', $medicalRecord)
            ->with('success', 'Đã cập nhật hồ sơ bệnh nhân.');
    }

    public function storeLabResult(Request $request, MedicalRecord $medicalRecord): RedirectResponse
    {
        $this->authorizeRecord($request, $medicalRecord);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'performed_at' => ['nullable', 'date'],
            'result' => ['nullable', 'string', 'max:10000'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp'],
        ]);

        $filePath = $request->file('file')?->store('lab-results/'.$medicalRecord->id, 'public');

        $medicalRecord->labResults()->create([
            'name' => $data['name'],
            'performed_at' => $data['performed_at'] ?? null,
            'result' => $data['result'] ?? null,
            'file_path' => $filePath,
        ]);

        return back()->with('success', 'Đã thêm kết quả xét nghiệm.');
    }

    public function destroyLabResult(Request $request, MedicalRecord $medicalRecord, LabResult $labResult): RedirectResponse
    {
        $this->authorizeRecord($request, $medicalRecord);
        abort_unless($labResult->medical_record_id === $medicalRecord->id, 404);

        if ($labResult->file_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($labResult->file_path);
        }

        $labResult->delete();

        return back()->with('success', 'Đã xóa kết quả xét nghiệm.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'appointment_id' => ['required', 'integer', 'exists:appointments,id'],
            'examined_at' => ['required', 'date'],
            'symptoms' => ['nullable', 'string', 'max:10000'],
            'diagnosis' => ['nullable', 'string', 'max:10000'],
            'treatment' => ['nullable', 'string', 'max:10000'],
            'prescription' => ['nullable', 'string', 'max:10000'],
            'note' => ['nullable', 'string', 'max:10000'],
            'follow_up_date' => ['nullable', 'date', 'after_or_equal:examined_at'],
        ]);
    }

    private function updatePatientProfile(Request $request, ?\App\Models\User $patient): void
    {
        if (! $patient) {
            return;
        }

        $patient->update($request->validate([
            'blood_type' => ['nullable', 'string', 'max:10'],
            'allergies' => ['nullable', 'string', 'max:5000'],
            'underlying_conditions' => ['nullable', 'string', 'max:5000'],
            'current_medications' => ['nullable', 'string', 'max:5000'],
        ]));
    }

    private function doctor(Request $request)
    {
        $doctor = $request->user()->doctor;
        abort_unless($doctor, 403, 'Tài khoản chưa được liên kết với hồ sơ bác sĩ.');

        return $doctor;
    }

    private function authorizeRecord(Request $request, MedicalRecord $medicalRecord): void
    {
        abort_unless($medicalRecord->doctor_id === $this->doctor($request)->id, 403);
    }

    private function ownedAppointment(Request $request, int $appointmentId, ?MedicalRecord $record = null): Appointment
    {
        return $this->doctor($request)->appointments()
            ->whereKey($appointmentId)
            ->whereDoesntHave('medicalRecord', fn ($query) => $query->when(
                $record,
                fn ($query) => $query->whereKeyNot($record->id)
            ))
            ->firstOrFail();
    }

    private function availableAppointments(Request $request, ?MedicalRecord $record = null)
    {
        return $this->doctor($request)->appointments()
            ->with(['user', 'service'])
            ->where(function ($query) use ($record): void {
                $query->whereDoesntHave('medicalRecord')
                    ->when($record?->appointment_id, fn ($query, $appointmentId) => $query->orWhere('appointments.id', $appointmentId));
            })
            ->latest('appointment_date')
            ->get();
    }
}
