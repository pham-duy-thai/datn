<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\MedicalRecord;
use App\Models\News;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    private array $roleLabels = [
        'admin' => 'Admin',
        'doctor' => 'Bác sĩ',
        'patient' => 'Bệnh nhân',
        'receptionist' => 'Nhân viên lễ tân',
    ];

    private array $genderLabels = [
        'male' => 'Nam',
        'female' => 'Nữ',
        'other' => 'Khác',
    ];

    private array $statusLabels = [
        '1' => 'Đang hoạt động',
        '0' => 'Tạm ẩn',
    ];

    private array $weekdays = [
        1 => 'Thứ hai',
        2 => 'Thứ ba',
        3 => 'Thứ tư',
        4 => 'Thứ năm',
        5 => 'Thứ sáu',
        6 => 'Thứ bảy',
        7 => 'Chủ nhật',
    ];

    private array $paymentMethodLabels = [
        'cash' => 'Tiền mặt',
        'vnpay' => 'VNPay sandbox',
        'momo' => 'MoMo sandbox',
    ];

    private array $paymentStatusLabels = [
        'unpaid' => 'Chưa thanh toán',
        'pending' => 'Chưa thanh toán',
        'paid' => 'Đã thanh toán',
        'failed' => 'Thanh toán thất bại',
        'cancelled' => 'Đã hủy',
        'refunded' => 'Đã hoàn tiền',
    ];

    public function dashboard(): View
    {
        $statusCounts = Appointment::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.dashboard', [
            'stats' => [
                'users' => User::count(),
                'departments' => Department::count(),
                'doctors' => Doctor::count(),
                'services' => Service::count(),
                'appointments' => Appointment::count(),
                'contacts' => Contact::count(),
                'news' => News::count(),
                'todayAppointments' => Appointment::whereDate('appointment_date', now()->toDateString())->count(),
            ],
            'statusCounts' => $statusCounts,
            'latestAppointments' => Appointment::with(['doctor.department', 'service', 'user'])
                ->latest('appointment_date')
                ->latest('appointment_time')
                ->limit(8)
                ->get(),
            'latestContacts' => Contact::latest()->limit(6)->get(),
            'latestUsers' => User::latest()->limit(6)->get(),
            'latestNews' => News::with('user')->latest('published_at')->limit(5)->get(),
        ]);
    }

    public function users(Request $request): View
    {
        $search = trim((string) $request->input('search'));

        $users = User::query()
            ->with('doctor.department')
            ->withCount(['appointments', 'medicalRecords'])
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->input('role')))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return $this->managementPage(
            title: 'Quản lý người dùng',
            eyebrow: 'Tài khoản',
            description: 'Theo dõi tài khoản admin, bác sĩ, bệnh nhân và nhân viên lễ tân.',
            routeName: 'admin.users.index',
            createRouteName: 'admin.users.create',
            columns: ['#', 'Người dùng', 'Liên hệ', 'Vai trò', 'Thông tin', 'Ngày tạo'],
            paginator: $users,
            rows: $users->getCollection()->map(fn (User $user): array => $this->actionRow([
                (string) $user->id,
                $this->lines($user->name, $user->email),
                $this->lines($user->phone ?: 'Chưa cập nhật', $user->address ?: 'Chưa cập nhật địa chỉ'),
                $this->roleLabels[$user->role] ?? $user->role,
                $this->lines(
                    'Giới tính: '.($this->genderLabels[$user->gender] ?? 'Chưa cập nhật'),
                    'Ngày sinh: '.($user->date_of_birth?->format('d/m/Y') ?? 'Chưa cập nhật'),
                    'Lịch khám: '.$user->appointments_count,
                    'Bệnh án: '.$user->medical_records_count
                ),
                $user->created_at->format('d/m/Y H:i'),
            ], 'admin.users.edit', 'admin.users.destroy', $user))->all(),
            filters: [
                [
                    'name' => 'role',
                    'label' => 'Vai trò',
                    'options' => $this->roleLabels,
                ],
            ],
            searchPlaceholder: 'Tên, thư điện tử, số điện thoại, địa chỉ'
        );
    }

    public function patients(Request $request): View
    {
        $search = trim((string) $request->input('search'));

        $patients = User::query()
            ->where('role', 'patient')
            ->withCount(['appointments', 'medicalRecords'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return $this->managementPage(
            title: 'Quản lý bệnh nhân',
            eyebrow: 'Bệnh nhân',
            description: 'Xem danh sách bệnh nhân, thông tin cá nhân và lịch sử dữ liệu khám.',
            routeName: 'admin.patients.index',
            createRouteName: 'admin.patients.create',
            columns: ['#', 'Bệnh nhân', 'Liên hệ', 'Hồ sơ', 'Thống kê', 'Ngày tạo'],
            paginator: $patients,
            rows: $patients->getCollection()->map(fn (User $patient): array => $this->actionRow([
                (string) $patient->id,
                $this->lines($patient->name, 'Giới tính: '.($this->genderLabels[$patient->gender] ?? 'Chưa cập nhật')),
                $this->lines($patient->email, $patient->phone ?: 'Chưa cập nhật', $patient->address ?: 'Chưa cập nhật địa chỉ'),
                $this->lines('Ngày sinh: '.($patient->date_of_birth?->format('d/m/Y') ?? 'Chưa cập nhật')),
                $this->lines('Lịch hẹn: '.$patient->appointments_count, 'Hồ sơ khám: '.$patient->medical_records_count),
                $patient->created_at->format('d/m/Y H:i'),
            ], 'admin.patients.edit', 'admin.patients.destroy', $patient))->all(),
            searchPlaceholder: 'Tên, thư điện tử, số điện thoại, địa chỉ'
        );
    }

    public function departments(Request $request): View
    {
        $search = trim((string) $request->input('search'));

        $departments = Department::query()
            ->withCount(['doctors', 'services'])
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return $this->managementPage(
            title: 'Quản lý chuyên khoa',
            eyebrow: 'Chuyên khoa',
            description: 'Danh sách chuyên khoa, trạng thái hiển thị, số bác sĩ và dịch vụ liên quan.',
            routeName: 'admin.departments.index',
            createRouteName: 'admin.departments.create',
            columns: ['#', 'Chuyên khoa', 'Mô tả', 'Số liệu', 'Trạng thái', 'Cập nhật'],
            paginator: $departments,
            rows: $departments->getCollection()->map(fn (Department $department): array => $this->actionRow([
                (string) $department->id,
                $this->lines($department->name, $department->slug),
                (string) str($department->description ?: 'Chưa có mô tả')->limit(110),
                $this->lines('Bác sĩ: '.$department->doctors_count, 'Dịch vụ: '.$department->services_count),
                $department->is_active ? 'Đang hoạt động' : 'Tạm ẩn',
                $department->updated_at->format('d/m/Y H:i'),
            ], 'admin.departments.edit', 'admin.departments.destroy', $department))->all(),
            filters: [
                [
                    'name' => 'is_active',
                    'label' => 'Trạng thái',
                    'options' => $this->statusLabels,
                ],
            ],
            searchPlaceholder: 'Tên chuyên khoa, đường dẫn, mô tả'
        );
    }

    public function doctors(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $departments = Department::orderBy('name')->pluck('name', 'id')->all();

        $doctors = Doctor::query()
            ->with(['department', 'user'])
            ->withCount(['schedules', 'appointments'])
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('specialization', 'like', "%{$search}%")
                        ->orWhere('degree', 'like', "%{$search}%")
                        ->orWhereHas('department', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return $this->managementPage(
            title: 'Quản lý bác sĩ',
            eyebrow: 'Bác sĩ',
            description: 'Theo dõi hồ sơ bác sĩ, chuyên khoa, lịch làm việc và lịch khám.',
            routeName: 'admin.doctors.index',
            createRouteName: 'admin.doctors.create',
            columns: ['#', 'Bác sĩ', 'Chuyên khoa', 'Chuyên môn', 'Thống kê', 'Trạng thái'],
            paginator: $doctors,
            rows: $doctors->getCollection()->map(fn (Doctor $doctor): array => $this->actionRow([
                (string) $doctor->id,
                $this->lines($doctor->name, $doctor->email ?: 'Chưa cập nhật thư điện tử', $doctor->phone ?: 'Chưa cập nhật điện thoại'),
                $doctor->department?->name ?? 'Chưa gán chuyên khoa',
                $this->lines(
                    $doctor->specialization ?: 'Chưa cập nhật chuyên môn',
                    $doctor->degree ?: 'Chưa cập nhật học vị',
                    'Kinh nghiệm: '.$doctor->experience_years.' năm',
                    'Phí khám: '.number_format((float) $doctor->consultation_fee, 0, ',', '.').' đ'
                ),
                $this->lines('Lịch làm việc: '.$doctor->schedules_count, 'Lịch hẹn: '.$doctor->appointments_count),
                $doctor->is_active ? 'Đang hoạt động' : 'Tạm ẩn',
            ], 'admin.doctors.edit', 'admin.doctors.destroy', $doctor))->all(),
            filters: [
                [
                    'name' => 'department_id',
                    'label' => 'Chuyên khoa',
                    'options' => $departments,
                ],
                [
                    'name' => 'is_active',
                    'label' => 'Trạng thái',
                    'options' => $this->statusLabels,
                ],
            ],
            searchPlaceholder: 'Tên, thư điện tử, chuyên môn, chuyên khoa'
        );
    }

    public function schedules(Request $request): View
    {
        $search = trim((string) $request->input('search'));

        $schedules = DoctorSchedule::query()
            ->with('doctor.department')
            ->when($request->filled('weekday'), fn ($query) => $query->where('weekday', $request->integer('weekday')))
            ->when($request->filled('is_available'), fn ($query) => $query->where('is_available', $request->boolean('is_available')))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('room', 'like', "%{$search}%")
                        ->orWhereHas('doctor', function ($query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhereHas('department', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                        });
                });
            })
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->paginate(12)
            ->withQueryString();

        return $this->managementPage(
            title: 'Quản lý lịch làm việc',
            eyebrow: 'Lịch bác sĩ',
            description: 'Xem lịch làm việc theo bác sĩ, thứ trong tuần, phòng khám và số bệnh nhân tối đa.',
            routeName: 'admin.schedules.index',
            createRouteName: 'admin.schedules.create',
            columns: ['#', 'Bác sĩ', 'Ngày làm việc', 'Giờ khám', 'Phòng', 'Trạng thái'],
            paginator: $schedules,
            rows: $schedules->getCollection()->map(fn (DoctorSchedule $schedule): array => $this->actionRow([
                (string) $schedule->id,
                $this->lines($schedule->doctor?->name ?? 'Chưa có bác sĩ', $schedule->doctor?->department?->name ?? 'Chưa có chuyên khoa'),
                $this->weekdays[$schedule->weekday] ?? 'Không rõ',
                substr($schedule->start_time, 0, 5).' - '.substr($schedule->end_time, 0, 5),
                $this->lines($schedule->room ?: 'Chưa cập nhật', 'Tối đa: '.$schedule->max_patients.' bệnh nhân'),
                $schedule->is_available ? 'Đang nhận lịch' : 'Tạm ngưng',
            ], 'admin.schedules.edit', 'admin.schedules.destroy', $schedule))->all(),
            filters: [
                [
                    'name' => 'weekday',
                    'label' => 'Thứ',
                    'options' => $this->weekdays,
                ],
                [
                    'name' => 'is_available',
                    'label' => 'Trạng thái',
                    'options' => [
                        '1' => 'Đang nhận lịch',
                        '0' => 'Tạm ngưng',
                    ],
                ],
            ],
            searchPlaceholder: 'Tên bác sĩ, chuyên khoa, phòng khám'
        );
    }

    public function appointments(Request $request): View
    {
        $appointments = Appointment::query()
            ->with(['doctor.department', 'service', 'user', 'payment'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search');

                $query->where(function ($query) use ($search): void {
                    $query->where('patient_name', 'like', "%{$search}%")
                        ->orWhere('patient_email', 'like', "%{$search}%")
                        ->orWhere('patient_phone', 'like', "%{$search}%")
                        ->orWhereHas('doctor', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('service', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('appointment_date')
            ->latest('appointment_time')
            ->paginate(12)
            ->withQueryString();

        return view('admin.appointments.index', ['appointments' => $appointments]);
    }

    public function updateAppointmentStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirmed', 'cancelled', 'completed'])],
            'note' => ['nullable', 'string'],
        ]);

        $appointment->update($data);

        return back()->with('success', "Đã cập nhật lịch hẹn #{$appointment->id}.");
    }

    public function payments(Request $request): View
    {
        $payments = Payment::query()
            ->with(['appointment.doctor.department', 'appointment.service', 'user'])
            ->when($request->filled('method'), fn ($query) => $query->where('method', $request->input('method')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search');

                $query->where(function ($query) use ($search): void {
                    $query->where('transaction_code', 'like', "%{$search}%")
                        ->orWhere('gateway_order_id', 'like', "%{$search}%")
                        ->orWhereHas('appointment', function ($query) use ($search): void {
                            $query->where('patient_name', 'like', "%{$search}%")
                                ->orWhere('patient_email', 'like', "%{$search}%")
                                ->orWhere('patient_phone', 'like', "%{$search}%");
                        })
                        ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.payments.index', [
            'payments' => $payments,
            'methodLabels' => $this->paymentMethodLabels,
            'statusLabels' => $this->paymentStatusLabels,
        ]);
    }

    public function medicalRecords(Request $request): View
    {
        $search = trim((string) $request->input('search'));

        $records = MedicalRecord::query()
            ->with(['user', 'doctor.department', 'appointment'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('symptoms', 'like', "%{$search}%")
                        ->orWhere('diagnosis', 'like', "%{$search}%")
                        ->orWhere('treatment', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        })
                        ->orWhereHas('doctor', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('appointment', fn ($query) => $query->where('patient_name', 'like', "%{$search}%"));
                });
            })
            ->latest('examined_at')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return $this->managementPage(
            title: 'Quản lý hồ sơ khám',
            eyebrow: 'Bệnh án',
            description: 'Theo dõi hồ sơ khám bệnh, triệu chứng, chẩn đoán, điều trị và lịch tái khám.',
            routeName: 'admin.medical-records.index',
            createRouteName: 'admin.medical-records.create',
            columns: ['#', 'Bệnh nhân', 'Bác sĩ', 'Ngày khám', 'Chẩn đoán', 'Điều trị'],
            paginator: $records,
            rows: $records->getCollection()->map(function (MedicalRecord $record): array {
                $patientName = $record->user?->name ?? $record->appointment?->patient_name ?? 'Chưa rõ bệnh nhân';
                $patientContact = $record->user?->email ?? $record->appointment?->patient_phone ?? 'Chưa có liên hệ';

                return $this->actionRow([
                    (string) $record->id,
                    $this->lines($patientName, $patientContact),
                    $this->lines($record->doctor?->name ?? 'Chưa có bác sĩ', $record->doctor?->department?->name ?? 'Chưa có chuyên khoa'),
                    $this->lines(
                        $record->examined_at?->format('d/m/Y') ?? 'Chưa cập nhật',
                        'Tái khám: '.($record->follow_up_date?->format('d/m/Y') ?? 'Chưa hẹn')
                    ),
                    $this->lines(
                        'Triệu chứng: '.(string) str($record->symptoms ?: 'Chưa cập nhật')->limit(60),
                        'Chẩn đoán: '.(string) str($record->diagnosis ?: 'Chưa cập nhật')->limit(70)
                    ),
                    (string) str($record->treatment ?: $record->note ?: 'Chưa cập nhật')->limit(90),
                ], 'admin.medical-records.edit', 'admin.medical-records.destroy', $record);
            })->all(),
            searchPlaceholder: 'Tên bệnh nhân, bác sĩ, triệu chứng, chẩn đoán'
        );
    }

    public function services(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $departments = Department::orderBy('name')->pluck('name', 'id')->all();

        $services = Service::query()
            ->with('department')
            ->withCount('appointments')
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('department', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return $this->managementPage(
            title: 'Quản lý dịch vụ',
            eyebrow: 'Dịch vụ y tế',
            description: 'Danh sách dịch vụ khám chữa bệnh, giá dịch vụ, thời lượng và chuyên khoa phụ trách.',
            routeName: 'admin.services.index',
            createRouteName: 'admin.services.create',
            columns: ['#', 'Dịch vụ', 'Chuyên khoa', 'Giá / thời lượng', 'Mô tả', 'Trạng thái'],
            paginator: $services,
            rows: $services->getCollection()->map(fn (Service $service): array => $this->actionRow([
                (string) $service->id,
                $this->lines($service->name, $service->slug, 'Lịch hẹn: '.$service->appointments_count),
                $service->department?->name ?? 'Chưa gán chuyên khoa',
                $this->lines(
                    number_format((float) $service->price, 0, ',', '.').' đ',
                    ($service->duration_minutes ?? 0).' phút'
                ),
                (string) str($service->description ?: 'Chưa có mô tả')->limit(100),
                $service->is_active ? 'Đang hoạt động' : 'Tạm ẩn',
            ], 'admin.services.edit', 'admin.services.destroy', $service))->all(),
            filters: [
                [
                    'name' => 'department_id',
                    'label' => 'Chuyên khoa',
                    'options' => $departments,
                ],
                [
                    'name' => 'is_active',
                    'label' => 'Trạng thái',
                    'options' => $this->statusLabels,
                ],
            ],
            searchPlaceholder: 'Tên dịch vụ, chuyên khoa, mô tả'
        );
    }

    public function news(Request $request): View
    {
        $search = trim((string) $request->input('search'));

        $articles = News::query()
            ->with('user')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('published_at')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return $this->managementPage(
            title: 'Quản lý tin tức',
            eyebrow: 'Nội dung',
            description: 'Theo dõi bài viết sức khỏe, trạng thái xuất bản, tác giả và ngày đăng.',
            routeName: 'admin.news.index',
            createRouteName: 'admin.news.create',
            columns: ['#', 'Bài viết', 'Tác giả', 'Trạng thái', 'Ngày đăng', 'Tóm tắt'],
            paginator: $articles,
            rows: $articles->getCollection()->map(fn (News $article): array => $this->actionRow([
                (string) $article->id,
                $this->lines($article->title, $article->slug),
                $article->user?->name ?? 'Chưa có tác giả',
                $article->status === 'published' ? 'Đã xuất bản' : 'Bản nháp',
                $article->published_at?->format('d/m/Y H:i') ?? 'Chưa xuất bản',
                (string) str($article->excerpt ?: $article->content)->limit(110),
            ], 'admin.news.edit', 'admin.news.destroy', $article))->all(),
            filters: [
                [
                    'name' => 'status',
                    'label' => 'Trạng thái',
                    'options' => [
                        'draft' => 'Bản nháp',
                        'published' => 'Đã xuất bản',
                    ],
                ],
            ],
            searchPlaceholder: 'Tiêu đề, tác giả, nội dung'
        );
    }

    public function contacts(Request $request): View
    {
        $contacts = Contact::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search');

                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.contacts.index', ['contacts' => $contacts]);
    }

    public function updateContactStatus(Request $request, Contact $contact): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'read', 'replied'])],
        ]);

        $contact->update($data);

        return back()->with('success', "Đã cập nhật liên hệ #{$contact->id}.");
    }

    private function managementPage(
        string $title,
        string $eyebrow,
        string $description,
        string $routeName,
        string $createRouteName,
        array $columns,
        $paginator,
        array $rows,
        array $filters = [],
        string $searchPlaceholder = 'Tìm kiếm'
    ): View {
        return view('admin.management.index', [
            'title' => $title,
            'eyebrow' => $eyebrow,
            'description' => $description,
            'routeName' => $routeName,
            'createUrl' => route($createRouteName),
            'columns' => $columns,
            'paginator' => $paginator,
            'rows' => $rows,
            'filters' => $filters,
            'searchPlaceholder' => $searchPlaceholder,
        ]);
    }

    private function actionRow(array $cells, string $editRouteName, string $deleteRouteName, mixed $record): array
    {
        return [
            'cells' => $cells,
            'editUrl' => route($editRouteName, $record->getKey()),
            'deleteUrl' => route($deleteRouteName, $record->getKey()),
        ];
    }

    private function lines(?string ...$lines): string
    {
        return collect($lines)
            ->filter(fn (?string $line): bool => filled($line))
            ->implode("\n");
    }
}
