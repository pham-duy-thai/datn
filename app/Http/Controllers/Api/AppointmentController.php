<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Service;
use App\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class AppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $appointments = Appointment::query()
            ->with(['user', 'doctor.department', 'doctorSchedule', 'service', 'medicalRecord', 'payment'])
            ->tap(fn ($query) => $this->applyRoleScope($query, $request))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('doctor_id'), fn ($query) => $query->where('doctor_id', $request->integer('doctor_id')))
            ->when($request->filled('service_id'), fn ($query) => $query->where('service_id', $request->integer('service_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('appointment_date', $request->date('date')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('appointment_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('appointment_date', '<=', $request->date('to')))
            ->latest('appointment_date')
            ->latest('appointment_time')
            ->paginate($request->integer('per_page', 15));

        return response()->json($appointments);
    }

    public function store(Request $request, PaymentGatewayService $paymentGateway): JsonResponse
    {
        $data = $this->validateData($request);
        $paymentMethod = $data['payment_method'];
        unset($data['payment_method']);

        if ($request->user()?->role === 'patient') {
            $data['user_id'] = $request->user()->id;
            $data['patient_name'] = $data['patient_name'] ?? $request->user()->name;
            $data['patient_email'] = $data['patient_email'] ?? $request->user()->email;
            $data['patient_phone'] = $data['patient_phone'] ?? $request->user()->phone;
        }

        if (! $request->user() || $request->user()->role === 'patient') {
            $data['status'] = 'pending';
        } else {
            $data['status'] = $data['status'] ?? 'pending';
        }

        $appointment = Appointment::create($data);
        $service = Service::findOrFail($data['service_id']);
        $totalAmount = (float) $service->price;
        $depositAmount = Payment::depositAmountFor($totalAmount);
        $payment = Payment::create([
            'appointment_id' => $appointment->id,
            'user_id' => $appointment->user_id,
            'method' => $paymentMethod,
            'status' => $paymentMethod === 'cash' ? 'unpaid' : 'pending',
            'amount' => $depositAmount,
            'total_amount' => $totalAmount,
            'deposit_amount' => $depositAmount,
            'currency' => 'VND',
        ]);

        $checkoutUrl = null;

        if ($paymentMethod !== 'cash') {
            try {
                $checkoutUrl = $paymentGateway->checkoutUrl($payment, $request);
            } catch (RuntimeException $exception) {
                $appointment->delete();

                return response()->json(['message' => $exception->getMessage()], 422);
            }
        }

        return response()->json([
            'data' => $appointment->load(['doctor.department', 'doctorSchedule', 'service', 'payment']),
            'checkout_url' => $checkoutUrl,
        ], 201);
    }

    public function show(Appointment $appointment): JsonResponse
    {
        $this->authorizeAppointmentAccess(request(), $appointment);

        return response()->json(['data' => $appointment->load(['user', 'doctor.department', 'doctorSchedule', 'service', 'medicalRecord', 'payment'])]);
    }

    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorizeAppointmentManagement($request, $appointment);

        $data = $this->validateData($request, true);
        unset($data['payment_method']);
        $appointment->update($data);

        return response()->json(['data' => $appointment->fresh(['user', 'doctor.department', 'doctorSchedule', 'service', 'medicalRecord', 'payment'])]);
    }

    public function updateStatus(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorizeAppointmentManagement($request, $appointment, allowDoctor: true);

        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirmed', 'cancelled', 'completed'])],
            'note' => ['nullable', 'string'],
        ]);

        $appointment->update($data);

        return response()->json(['data' => $appointment->fresh(['doctor', 'service'])]);
    }

    public function cancel(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorizeAppointmentAccess($request, $appointment);

        if ($appointment->status === 'completed') {
            return response()->json(['message' => 'Không thể hủy lịch hẹn đã hoàn tất.'], 422);
        }

        $data = $request->validate([
            'note' => ['nullable', 'string'],
        ]);

        $appointment->update([
            'status' => 'cancelled',
            'note' => $data['note'] ?? $appointment->note,
        ]);

        return response()->json(['data' => $appointment->fresh(['doctor', 'service'])]);
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $this->authorizeAppointmentManagement(request(), $appointment);

        $appointment->delete();

        return response()->json(['message' => 'Đã xóa lịch hẹn.']);
    }

    public function statistics(): JsonResponse
    {
        $statusCounts = Appointment::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $byDoctor = Appointment::query()
            ->join('doctors', 'appointments.doctor_id', '=', 'doctors.id')
            ->select('doctors.id', 'doctors.name', DB::raw('COUNT(*) as total'))
            ->groupBy('doctors.id', 'doctors.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $byDepartment = Appointment::query()
            ->join('doctors', 'appointments.doctor_id', '=', 'doctors.id')
            ->join('departments', 'doctors.department_id', '=', 'departments.id')
            ->select('departments.id', 'departments.name', DB::raw('COUNT(*) as total'))
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $byService = Appointment::query()
            ->leftJoin('services', 'appointments.service_id', '=', 'services.id')
            ->select('services.id', 'services.name', DB::raw('COUNT(*) as total'))
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return response()->json([
            'total' => Appointment::count(),
            'today' => Appointment::whereDate('appointment_date', now()->toDateString())->count(),
            'this_month' => Appointment::whereYear('appointment_date', now()->year)->whereMonth('appointment_date', now()->month)->count(),
            'completed' => Appointment::where('status', 'completed')->count(),
            'cancelled' => Appointment::where('status', 'cancelled')->count(),
            'pending' => Appointment::where('status', 'pending')->count(),
            'confirmed' => Appointment::where('status', 'confirmed')->count(),
            'by_status' => $statusCounts,
            'by_doctor' => $byDoctor,
            'by_department' => $byDepartment,
            'by_service' => $byService,
        ]);
    }

    private function validateData(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'doctor_id' => [$partial ? 'sometimes' : 'required', 'exists:doctors,id'],
            'doctor_schedule_id' => ['nullable', 'exists:doctor_schedules,id'],
            'service_id' => [$partial ? 'sometimes' : 'required', 'exists:services,id'],
            'payment_method' => [$partial ? 'sometimes' : 'required', Rule::in(Payment::METHODS)],
            'patient_name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'patient_email' => ['nullable', 'email', 'max:255'],
            'patient_phone' => [$partial ? 'sometimes' : 'required', 'string', 'max:20'],
            'appointment_date' => [$partial ? 'sometimes' : 'required', 'date'],
            'appointment_time' => [$partial ? 'sometimes' : 'required', 'date_format:H:i'],
            'reason' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['pending', 'confirmed', 'cancelled', 'completed'])],
            'note' => ['nullable', 'string'],
        ]);
    }

    private function applyRoleScope($query, Request $request): void
    {
        $user = $request->user();

        if (! $user) {
            return;
        }

        if ($user->role === 'patient') {
            $query->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhere('patient_email', $user->email);
            });
        }

        if ($user->role === 'doctor') {
            $doctorId = $user->doctor?->id;
            $query->where('doctor_id', $doctorId ?: 0);
        }
    }

    private function authorizeAppointmentAccess(Request $request, Appointment $appointment): void
    {
        $user = $request->user();

        if (! $user || in_array($user->role, ['admin', 'receptionist'], true)) {
            return;
        }

        if ($user->role === 'patient') {
            abort_unless($appointment->user_id === $user->id || $appointment->patient_email === $user->email, 403);

            return;
        }

        if ($user->role === 'doctor') {
            abort_unless($user->doctor && $appointment->doctor_id === $user->doctor->id, 403);

            return;
        }

        abort(403);
    }

    private function authorizeAppointmentManagement(Request $request, Appointment $appointment, bool $allowDoctor = false): void
    {
        $user = $request->user();

        if (! $user || in_array($user->role, ['admin', 'receptionist'], true)) {
            return;
        }

        if ($allowDoctor && $user->role === 'doctor' && $user->doctor && $appointment->doctor_id === $user->doctor->id) {
            return;
        }

        abort(403);
    }
}
