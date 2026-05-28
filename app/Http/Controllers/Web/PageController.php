<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Banner;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\News;
use App\Models\Payment;
use App\Models\Service;
use App\Services\PaymentGatewayService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home', [
            'banners' => $this->safe(fn () => Banner::where('is_active', true)->orderBy('sort_order')->limit(5)->get()),
            'departments' => $this->safe(fn () => Department::where('is_active', true)->withCount(['doctors', 'services'])->limit(8)->get()),
            'doctors' => $this->safe(fn () => Doctor::where('is_active', true)->with(['department', 'schedules'])->latest()->limit(6)->get()),
            'services' => $this->safe(fn () => Service::where('is_active', true)->with('department')->limit(6)->get()),
            'news' => $this->safe(fn () => News::where('status', 'published')->latest('published_at')->limit(3)->get()),
        ]);
    }

    public function account(Request $request): View
    {
        $user = $request->user()->load([
            'appointments' => fn ($query) => $query->with(['doctor.department', 'service', 'payment'])->latest('appointment_date')->limit(10),
            'medicalRecords' => fn ($query) => $query->with('doctor.department')->latest('examined_at')->limit(10),
        ]);

        return view('pages.account.show', ['user' => $user]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Mật khẩu hiện tại không đúng.',
            ]);
        }

        $request->user()->forceFill([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ])->save();

        return redirect()
            ->route('account.show')
            ->with('success', 'Đã cập nhật mật khẩu mới.');
    }

    public function skipPasswordChange(Request $request): RedirectResponse
    {
        $request->user()->forceFill([
            'must_change_password' => false,
        ])->save();

        return redirect()
            ->route('account.show')
            ->with('success', 'Bạn đã bỏ qua bước đổi mật khẩu.');
    }

    public function departments(Request $request): View
    {
        $departments = $this->safe(function () use ($request) {
            return Department::query()
                ->withCount(['doctors', 'services'])
                ->when($request->filled('search'), function ($query) use ($request): void {
                    $search = $request->string('search');
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                })
                ->where('is_active', true)
                ->latest()
                ->paginate(9)
                ->withQueryString();
        });

        return view('pages.departments.index', ['departments' => $departments]);
    }

    public function departmentShow(Department $department): View
    {
        $department->load([
            'doctors' => fn ($query) => $query->where('is_active', true)->with('schedules'),
            'services' => fn ($query) => $query->where('is_active', true),
        ]);

        return view('pages.departments.show', ['department' => $department]);
    }

    public function doctors(Request $request): View
    {
        return view('pages.doctors.index', [
            'departments' => $this->safe(fn () => Department::where('is_active', true)->orderBy('name')->get()),
            'doctors' => $this->safe(function () use ($request) {
                return Doctor::query()
                    ->with(['department', 'schedules'])
                    ->where('is_active', true)
                    ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
                    ->when($request->filled('search'), function ($query) use ($request): void {
                        $search = $request->string('search');
                        $query->where(function ($query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('specialization', 'like', "%{$search}%")
                                ->orWhere('degree', 'like', "%{$search}%");
                        });
                    })
                    ->latest()
                    ->paginate(9)
                    ->withQueryString();
            }),
        ]);
    }

    public function doctorShow(Doctor $doctor): View
    {
        $doctor->load(['department', 'schedules' => fn ($query) => $query->where('is_available', true)->orderBy('weekday')->orderBy('start_time')]);

        return view('pages.doctors.show', [
            'doctor' => $doctor,
            'services' => $this->safe(fn () => Service::where('is_active', true)
                ->when($doctor->department_id, fn ($query) => $query->where('department_id', $doctor->department_id))
                ->orderBy('name')
                ->get()),
        ]);
    }

    public function services(Request $request): View
    {
        return view('pages.services.index', [
            'departments' => $this->safe(fn () => Department::where('is_active', true)->orderBy('name')->get()),
            'services' => $this->safe(function () use ($request) {
                return Service::query()
                    ->with('department')
                    ->where('is_active', true)
                    ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
                    ->when($request->filled('search'), function ($query) use ($request): void {
                        $search = $request->string('search');
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    })
                    ->latest()
                    ->paginate(9)
                    ->withQueryString();
            }),
        ]);
    }

    public function serviceShow(Service $service): View
    {
        $service->load(['department.doctors' => fn ($query) => $query->where('is_active', true)->limit(6)]);

        return view('pages.services.show', ['service' => $service]);
    }

    public function news(Request $request): View
    {
        return view('pages.news.index', [
            'news' => $this->safe(function () use ($request) {
                return News::query()
                    ->with('user')
                    ->where('status', 'published')
                    ->when($request->filled('search'), function ($query) use ($request): void {
                        $search = $request->string('search');
                        $query->where('title', 'like', "%{$search}%")
                            ->orWhere('excerpt', 'like', "%{$search}%")
                            ->orWhere('content', 'like', "%{$search}%");
                    })
                    ->latest('published_at')
                    ->paginate(9)
                    ->withQueryString();
            }),
        ]);
    }

    public function newsShow(News $news): View
    {
        $news->load('user');

        return view('pages.news.show', [
            'article' => $news,
            'relatedNews' => $this->safe(fn () => News::where('status', 'published')
                ->whereKeyNot($news->id)
                ->latest('published_at')
                ->limit(3)
                ->get()),
        ]);
    }

    public function appointmentCreate(Request $request): View
    {
        return view('pages.appointments.create', [
            'departments' => $this->safe(fn () => Department::where('is_active', true)->orderBy('name')->get()),
            'doctors' => $this->safe(fn () => Doctor::where('is_active', true)->with(['department', 'schedules' => fn ($query) => $query->where('is_available', true)->orderBy('weekday')->orderBy('start_time')])->orderBy('name')->get()),
            'services' => $this->safe(fn () => Service::where('is_active', true)->with('department')->orderBy('name')->get()),
            'selectedDoctorId' => $request->integer('doctor_id') ?: null,
            'selectedServiceId' => $request->integer('service_id') ?: null,
        ]);
    }

    public function appointmentStore(Request $request, PaymentGatewayService $paymentGateway): RedirectResponse
    {
        $data = $request->validate([
            'doctor_id' => ['required', 'exists:doctors,id'],
            'doctor_schedule_id' => ['nullable', 'exists:doctor_schedules,id'],
            'service_id' => ['required', 'exists:services,id'],
            'payment_method' => ['required', Rule::in(Payment::METHODS)],
            'patient_name' => ['required', 'string', 'max:255'],
            'patient_email' => ['required', 'email', 'max:255'],
            'patient_phone' => ['required', 'string', 'max:20'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'reason' => ['nullable', 'string'],
        ]);

        $paymentMethod = $data['payment_method'];
        unset($data['payment_method']);

        $data['user_id'] = $request->user()?->id;
        $data['status'] = 'pending';

        $appointment = Appointment::create($data);
        $service = Service::findOrFail($data['service_id']);
        $totalAmount = (float) $service->price;
        $depositAmount = Payment::depositAmountFor($totalAmount);
        $payment = Payment::create([
            'appointment_id' => $appointment->id,
            'user_id' => $request->user()?->id,
            'method' => $paymentMethod,
            'status' => $paymentMethod === 'cash' ? 'unpaid' : 'pending',
            'amount' => $depositAmount,
            'total_amount' => $totalAmount,
            'deposit_amount' => $depositAmount,
            'currency' => 'VND',
        ]);

        if ($paymentMethod !== 'cash') {
            try {
                return redirect()->away($paymentGateway->checkoutUrl($payment, $request));
            } catch (\RuntimeException $exception) {
                $appointment->delete();

                return back()
                    ->withInput()
                    ->withErrors(['payment_method' => $exception->getMessage()]);
            }
        }

        return redirect()
            ->route('payments.show', $payment)
            ->with('success', "Đã gửi lịch hẹn #{$appointment->id}. Vui lòng thanh toán tại bệnh viện.");
    }

    public function contactCreate(): View
    {
        return view('pages.contact.create');
    }

    public function contactStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'status' => ['sometimes', Rule::in(['new', 'read', 'replied'])],
        ]);

        $data['status'] = 'new';

        Contact::create($data);

        return redirect()
            ->route('contact.create')
            ->with('success', 'Đã gửi thông tin liên hệ. Bệnh viện sẽ phản hồi sớm.');
    }

    /**
     * Keep the public homepage renderable before migrations are run.
     */
    private function safe(callable $callback, mixed $fallback = null): mixed
    {
        try {
            return $callback();
        } catch (Throwable) {
            return $fallback ?? collect();
        }
    }
}
