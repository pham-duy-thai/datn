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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminCrudController extends Controller
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

    private array $weekdays = [
        1 => 'Thứ hai',
        2 => 'Thứ ba',
        3 => 'Thứ tư',
        4 => 'Thứ năm',
        5 => 'Thứ sáu',
        6 => 'Thứ bảy',
        7 => 'Chủ nhật',
    ];

    private array $resources = [
        'users' => ['model' => User::class, 'route' => 'users', 'label' => 'người dùng', 'title' => 'người dùng'],
        'patients' => ['model' => User::class, 'route' => 'patients', 'label' => 'bệnh nhân', 'title' => 'bệnh nhân'],
        'departments' => ['model' => Department::class, 'route' => 'departments', 'label' => 'chuyên khoa', 'title' => 'chuyên khoa'],
        'doctors' => ['model' => Doctor::class, 'route' => 'doctors', 'label' => 'bác sĩ', 'title' => 'bác sĩ'],
        'schedules' => ['model' => DoctorSchedule::class, 'route' => 'schedules', 'label' => 'lịch làm việc', 'title' => 'lịch làm việc'],
        'appointments' => ['model' => Appointment::class, 'route' => 'appointments', 'label' => 'lịch hẹn', 'title' => 'lịch hẹn'],
        'medical-records' => ['model' => MedicalRecord::class, 'route' => 'medical-records', 'label' => 'hồ sơ khám', 'title' => 'hồ sơ khám'],
        'services' => ['model' => Service::class, 'route' => 'services', 'label' => 'dịch vụ', 'title' => 'dịch vụ'],
        'news' => ['model' => News::class, 'route' => 'news', 'label' => 'tin tức', 'title' => 'tin tức'],
        'contacts' => ['model' => Contact::class, 'route' => 'contacts', 'label' => 'liên hệ', 'title' => 'liên hệ'],
    ];

    public function create(Request $request): View
    {
        $resource = $this->resource($request);

        return $this->formPage($resource);
    }

    public function store(Request $request): RedirectResponse
    {
        $resource = $this->resource($request);
        $data = $this->validated($request, $resource);
        $paymentMethod = $data['payment_method'] ?? null;
        unset($data['payment_method']);

        $model = $this->modelClass($resource)::create($data);
        $this->syncAppointmentPayment($resource, $model, $paymentMethod);

        return redirect()
            ->route($this->routeName($resource, 'index'))
            ->with('success', 'Đã thêm '.$this->label($resource)." #{$model->getKey()}.");
    }

    public function edit(Request $request, string $id): View
    {
        $resource = $this->resource($request);
        $record = $this->findRecord($resource, $id);

        return $this->formPage($resource, $record);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $resource = $this->resource($request);
        $record = $this->findRecord($resource, $id);
        $data = $this->validated($request, $resource, $record);
        $paymentMethod = $data['payment_method'] ?? null;
        unset($data['payment_method']);

        $record->update($data);
        $this->syncAppointmentPayment($resource, $record, $paymentMethod);

        return redirect()
            ->route($this->routeName($resource, 'index'))
            ->with('success', 'Đã cập nhật '.$this->label($resource)." #{$record->getKey()}.");
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $resource = $this->resource($request);
        $record = $this->findRecord($resource, $id);

        if ($record instanceof User && $record->is($request->user())) {
            return back()->withErrors(['delete' => 'Không thể xóa tài khoản đang đăng nhập.']);
        }

        $record->delete();

        return redirect()
            ->route($this->routeName($resource, 'index'))
            ->with('success', 'Đã xóa '.$this->label($resource)." #{$record->getKey()}.");
    }

    private function formPage(string $resource, ?Model $record = null): View
    {
        $isEdit = $record !== null;

        return view('admin.management.form', [
            'title' => ($isEdit ? 'Sửa ' : 'Thêm ').$this->title($resource),
            'eyebrow' => $isEdit ? 'Cập nhật' : 'Thêm mới',
            'action' => $isEdit
                ? route($this->routeName($resource, 'update'), $record->getKey())
                : route($this->routeName($resource, 'store')),
            'method' => $isEdit ? 'PUT' : 'POST',
            'backUrl' => route($this->routeName($resource, 'index')),
            'fields' => $this->fields($resource, $record),
            'submitLabel' => $isEdit ? 'Lưu thay đổi' : 'Thêm mới',
        ]);
    }

    private function fields(string $resource, ?Model $record): array
    {
        return match ($resource) {
            'users' => $this->userFields($record),
            'patients' => $this->patientFields($record),
            'departments' => [
                $this->textField('name', 'Tên chuyên khoa', $record?->name, required: true),
                $this->textField('slug', 'Đường dẫn', $record?->slug, 'Tự sinh từ tên nếu để trống'),
                $this->textareaField('description', 'Mô tả', $record?->description),
                $this->textField('image', 'Ảnh', $record?->image, 'Ví dụ: images/about-bg.jpg'),
                $this->selectField('is_active', 'Trạng thái', $record?->is_active ?? true, ['1' => 'Đang hoạt động', '0' => 'Tạm ẩn'], required: true),
            ],
            'doctors' => [
                $this->selectField('user_id', 'Tài khoản bác sĩ', $record?->user_id, $this->doctorUserOptions($record), 'Không gán tài khoản'),
                $this->selectField('department_id', 'Chuyên khoa', $record?->department_id, $this->departmentOptions(), 'Không chọn'),
                $this->textField('name', 'Tên bác sĩ', $record?->name, required: true),
                $this->emailField('email', 'Thư điện tử', $record?->email),
                $this->textField('phone', 'Số điện thoại', $record?->phone),
                $this->textField('avatar', 'Ảnh đại diện', $record?->avatar, 'Ví dụ: images/team-image1.jpg'),
                $this->textField('specialization', 'Chuyên môn', $record?->specialization),
                $this->textField('degree', 'Học vị', $record?->degree),
                $this->numberField('experience_years', 'Số năm kinh nghiệm', $record?->experience_years ?? 0, min: 0, max: 255),
                $this->numberField('consultation_fee', 'Phí khám', $record?->consultation_fee ?? 0, min: 0, step: 1000),
                $this->textareaField('bio', 'Hồ sơ bác sĩ', $record?->bio),
                $this->selectField('is_active', 'Trạng thái', $record?->is_active ?? true, ['1' => 'Đang hoạt động', '0' => 'Tạm ẩn'], required: true),
            ],
            'schedules' => [
                $this->selectField('doctor_id', 'Bác sĩ', $record?->doctor_id, $this->doctorOptions(), required: true),
                $this->selectField('weekday', 'Thứ', $record?->weekday, $this->weekdays, required: true),
                $this->timeField('start_time', 'Giờ bắt đầu', $record?->start_time, required: true),
                $this->timeField('end_time', 'Giờ kết thúc', $record?->end_time, required: true),
                $this->textField('room', 'Phòng khám', $record?->room),
                $this->numberField('max_patients', 'Số bệnh nhân tối đa', $record?->max_patients ?? 1, min: 1, max: 1000),
                $this->selectField('is_available', 'Trạng thái', $record?->is_available ?? true, ['1' => 'Đang nhận lịch', '0' => 'Tạm ngưng'], required: true),
            ],
            'appointments' => [
                $this->selectField('user_id', 'Tài khoản bệnh nhân', $record?->user_id, $this->patientOptions(), 'Không gán tài khoản'),
                $this->selectField('doctor_id', 'Bác sĩ', $record?->doctor_id, $this->doctorOptions(), required: true),
                $this->selectField('doctor_schedule_id', 'Lịch làm việc', $record?->doctor_schedule_id, $this->scheduleOptions(), 'Không chọn'),
                $this->selectField('service_id', 'Dịch vụ', $record?->service_id, $this->serviceOptions(), 'Không chọn'),
                $this->textField('patient_name', 'Tên người khám', $record?->patient_name, required: true),
                $this->emailField('patient_email', 'Thư điện tử người khám', $record?->patient_email),
                $this->textField('patient_phone', 'Số điện thoại người khám', $record?->patient_phone, required: true),
                $this->dateField('appointment_date', 'Ngày khám', $record?->appointment_date?->format('Y-m-d'), required: true),
                $this->timeField('appointment_time', 'Giờ khám', $record?->appointment_time, required: true),
                $this->selectField('status', 'Trạng thái', $record?->status ?? 'pending', $this->appointmentStatusOptions(), required: true),
                $this->selectField('payment_method', 'Phương thức thanh toán', $record?->payment?->method ?? 'cash', $this->paymentMethodOptions(), required: true),
                $this->textareaField('reason', 'Lý do khám', $record?->reason),
                $this->textareaField('note', 'Ghi chú', $record?->note),
            ],
            'medical-records' => [
                $this->selectField('appointment_id', 'Lịch hẹn', $record?->appointment_id, $this->appointmentOptions($record), 'Không gắn lịch hẹn'),
                $this->selectField('user_id', 'Bệnh nhân', $record?->user_id, $this->patientOptions(), 'Không chọn'),
                $this->selectField('doctor_id', 'Bác sĩ', $record?->doctor_id, $this->doctorOptions(), 'Không chọn'),
                $this->dateField('examined_at', 'Ngày khám', $record?->examined_at?->format('Y-m-d')),
                $this->textareaField('symptoms', 'Triệu chứng', $record?->symptoms),
                $this->textareaField('diagnosis', 'Chẩn đoán', $record?->diagnosis),
                $this->textareaField('treatment', 'Điều trị', $record?->treatment),
                $this->textareaField('prescription', 'Đơn thuốc', $record?->prescription),
                $this->textareaField('note', 'Ghi chú', $record?->note),
                $this->dateField('follow_up_date', 'Ngày tái khám', $record?->follow_up_date?->format('Y-m-d')),
            ],
            'services' => [
                $this->selectField('department_id', 'Chuyên khoa', $record?->department_id, $this->departmentOptions(), 'Không chọn'),
                $this->textField('name', 'Tên dịch vụ', $record?->name, required: true),
                $this->textField('slug', 'Đường dẫn', $record?->slug, 'Tự sinh từ tên nếu để trống'),
                $this->textareaField('description', 'Mô tả', $record?->description),
                $this->numberField('price', 'Giá dịch vụ', $record?->price ?? 0, min: 0, step: 1000),
                $this->numberField('duration_minutes', 'Thời lượng phút', $record?->duration_minutes, min: 1),
                $this->textField('image', 'Ảnh', $record?->image, 'Ví dụ: images/appointment-image.jpg'),
                $this->selectField('is_active', 'Trạng thái', $record?->is_active ?? true, ['1' => 'Đang hoạt động', '0' => 'Tạm ẩn'], required: true),
            ],
            'news' => [
                $this->selectField('user_id', 'Tác giả', $record?->user_id, $this->authorOptions($record), 'Không chọn'),
                $this->textField('title', 'Tiêu đề', $record?->title, required: true),
                $this->textField('slug', 'Đường dẫn', $record?->slug, 'Tự sinh từ tiêu đề nếu để trống'),
                $this->textareaField('excerpt', 'Tóm tắt', $record?->excerpt),
                $this->textareaField('content', 'Nội dung', $record?->content, required: true, rows: 10),
                $this->textField('thumbnail', 'Ảnh đại diện', $record?->thumbnail, 'Ví dụ: images/news-image1.jpg'),
                $this->selectField('status', 'Trạng thái', $record?->status ?? 'draft', ['draft' => 'Bản nháp', 'published' => 'Đã xuất bản'], required: true),
                $this->datetimeField('published_at', 'Ngày xuất bản', $record?->published_at?->format('Y-m-d\TH:i')),
            ],
            'contacts' => [
                $this->textField('name', 'Tên người gửi', $record?->name, required: true),
                $this->emailField('email', 'Thư điện tử', $record?->email, required: true),
                $this->textField('phone', 'Số điện thoại', $record?->phone),
                $this->textField('subject', 'Chủ đề', $record?->subject),
                $this->textareaField('message', 'Nội dung', $record?->message, required: true),
                $this->selectField('status', 'Trạng thái', $record?->status ?? 'new', ['new' => 'Mới', 'read' => 'Đã đọc', 'replied' => 'Đã phản hồi'], required: true),
            ],
            default => abort(404),
        };
    }

    private function validated(Request $request, string $resource, ?Model $record = null): array
    {
        $this->mergeGeneratedSlug($request, $resource);

        $data = match ($resource) {
            'users' => $request->validate($this->userRules($record)),
            'patients' => $request->validate($this->patientRules($record)),
            'departments' => $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', Rule::unique('departments', 'slug')->ignore($record?->getKey())],
                'description' => ['nullable', 'string'],
                'image' => ['nullable', 'string', 'max:255'],
                'is_active' => ['required', 'boolean'],
            ]),
            'doctors' => $request->validate([
                'user_id' => ['nullable', 'exists:users,id', Rule::unique('doctors', 'user_id')->ignore($record?->getKey())],
                'department_id' => ['nullable', 'exists:departments,id'],
                'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255', Rule::unique('doctors', 'email')->ignore($record?->getKey())],
                'phone' => ['nullable', 'string', 'max:20'],
                'avatar' => ['nullable', 'string', 'max:255'],
                'specialization' => ['nullable', 'string', 'max:255'],
                'degree' => ['nullable', 'string', 'max:255'],
                'experience_years' => ['required', 'integer', 'min:0', 'max:255'],
                'bio' => ['nullable', 'string'],
                'consultation_fee' => ['required', 'numeric', 'min:0'],
                'is_active' => ['required', 'boolean'],
            ]),
            'schedules' => $this->validateSchedule($request, $record),
            'appointments' => $request->validate([
                'user_id' => ['nullable', 'exists:users,id'],
                'doctor_id' => ['required', 'exists:doctors,id'],
                'doctor_schedule_id' => ['nullable', 'exists:doctor_schedules,id'],
                'service_id' => ['required', 'exists:services,id'],
                'patient_name' => ['required', 'string', 'max:255'],
                'patient_email' => ['nullable', 'email', 'max:255'],
                'patient_phone' => ['required', 'string', 'max:20'],
                'appointment_date' => ['required', 'date'],
                'appointment_time' => ['required', 'date_format:H:i'],
                'reason' => ['nullable', 'string'],
                'status' => ['required', Rule::in(array_keys($this->appointmentStatusOptions()))],
                'payment_method' => ['required', Rule::in(Payment::METHODS)],
                'note' => ['nullable', 'string'],
            ]),
            'medical-records' => $request->validate([
                'appointment_id' => ['nullable', 'exists:appointments,id', Rule::unique('medical_records', 'appointment_id')->ignore($record?->getKey())],
                'user_id' => ['nullable', 'exists:users,id'],
                'doctor_id' => ['nullable', 'exists:doctors,id'],
                'examined_at' => ['nullable', 'date'],
                'symptoms' => ['nullable', 'string'],
                'diagnosis' => ['nullable', 'string'],
                'treatment' => ['nullable', 'string'],
                'prescription' => ['nullable', 'string'],
                'note' => ['nullable', 'string'],
                'follow_up_date' => ['nullable', 'date'],
            ]),
            'services' => $request->validate([
                'department_id' => ['nullable', 'exists:departments,id'],
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', Rule::unique('services', 'slug')->ignore($record?->getKey())],
                'description' => ['nullable', 'string'],
                'price' => ['required', 'numeric', 'min:0'],
                'duration_minutes' => ['nullable', 'integer', 'min:1'],
                'image' => ['nullable', 'string', 'max:255'],
                'is_active' => ['required', 'boolean'],
            ]),
            'news' => $request->validate([
                'user_id' => ['nullable', 'exists:users,id'],
                'title' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', Rule::unique('news', 'slug')->ignore($record?->getKey())],
                'excerpt' => ['nullable', 'string'],
                'content' => ['required', 'string'],
                'thumbnail' => ['nullable', 'string', 'max:255'],
                'status' => ['required', Rule::in(['draft', 'published'])],
                'published_at' => ['nullable', 'date'],
            ]),
            'contacts' => $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:20'],
                'subject' => ['nullable', 'string', 'max:255'],
                'message' => ['required', 'string'],
                'status' => ['required', Rule::in(['new', 'read', 'replied'])],
            ]),
            default => abort(404),
        };

        if ($resource === 'patients') {
            $data['role'] = 'patient';
        }

        if (array_key_exists('password', $data)) {
            if (filled($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }
        }

        if ($resource === 'news') {
            $data['published_at'] = $data['published_at'] ? str_replace('T', ' ', $data['published_at']) : null;

            if (! $data['user_id']) {
                $data['user_id'] = $request->user()?->id;
            }

            if ($data['status'] === 'published' && ! $data['published_at']) {
                $data['published_at'] = now();
            }
        }

        return $data;
    }

    private function validateSchedule(Request $request, ?Model $record): array
    {
        $data = $request->validate([
            'doctor_id' => ['required', 'exists:doctors,id'],
            'weekday' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room' => ['nullable', 'string', 'max:255'],
            'max_patients' => ['required', 'integer', 'min:1', 'max:1000'],
            'is_available' => ['required', 'boolean'],
        ]);

        $exists = DoctorSchedule::query()
            ->where('doctor_id', $data['doctor_id'])
            ->where('weekday', $data['weekday'])
            ->where('start_time', $data['start_time'])
            ->where('end_time', $data['end_time'])
            ->when($record, fn ($query) => $query->where('id', '<>', $record->getKey()))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'start_time' => 'Lịch làm việc này đã tồn tại cho bác sĩ đã chọn.',
            ]);
        }

        return $data;
    }

    private function userRules(?Model $record): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($record?->getKey())],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(array_keys($this->roleLabels))],
            'gender' => ['nullable', Rule::in(array_keys($this->genderLabels))],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'password' => [$record ? 'nullable' : 'required', 'string', 'min:6'],
        ];
    }

    private function patientRules(?Model $record): array
    {
        $rules = $this->userRules($record);
        unset($rules['role']);

        return $rules;
    }

    private function mergeGeneratedSlug(Request $request, string $resource): void
    {
        if ($request->filled('slug')) {
            return;
        }

        $source = match ($resource) {
            'departments', 'services' => $request->input('name'),
            'news' => $request->input('title'),
            default => null,
        };

        if ($source) {
            $request->merge(['slug' => Str::slug($source)]);
        }
    }

    private function findRecord(string $resource, string $id): Model
    {
        $record = $this->modelClass($resource)::query()->findOrFail($id);

        if ($resource === 'patients') {
            abort_unless($record instanceof User && $record->role === 'patient', 404);
        }

        return $record;
    }

    private function resource(Request $request): string
    {
        $resource = (string) $request->route('resource');

        abort_unless(array_key_exists($resource, $this->resources), 404);

        return $resource;
    }

    private function modelClass(string $resource): string
    {
        return $this->resources[$resource]['model'];
    }

    private function routeName(string $resource, string $action): string
    {
        return 'admin.'.$this->resources[$resource]['route'].'.'.$action;
    }

    private function label(string $resource): string
    {
        return $this->resources[$resource]['label'];
    }

    private function title(string $resource): string
    {
        return $this->resources[$resource]['title'];
    }

    private function userFields(?Model $record): array
    {
        return [
            $this->textField('name', 'Họ tên', $record?->name, required: true),
            $this->emailField('email', 'Thư điện tử', $record?->email, required: true),
            $this->textField('phone', 'Số điện thoại', $record?->phone),
            $this->selectField('role', 'Vai trò', $record?->role ?? 'patient', $this->roleLabels, required: true),
            $this->selectField('gender', 'Giới tính', $record?->gender, $this->genderLabels, 'Không chọn'),
            $this->dateField('date_of_birth', 'Ngày sinh', $record?->date_of_birth?->format('Y-m-d')),
            $this->textField('address', 'Địa chỉ', $record?->address),
            $this->textField('avatar', 'Ảnh đại diện', $record?->avatar, 'Ví dụ: images/team-image1.jpg'),
            $this->passwordField('password', 'Mật khẩu', $record ? 'Để trống nếu không đổi mật khẩu' : null, required: ! $record),
        ];
    }

    private function patientFields(?Model $record): array
    {
        return [
            $this->textField('name', 'Họ tên bệnh nhân', $record?->name, required: true),
            $this->emailField('email', 'Thư điện tử', $record?->email, required: true),
            $this->textField('phone', 'Số điện thoại', $record?->phone),
            $this->selectField('gender', 'Giới tính', $record?->gender, $this->genderLabels, 'Không chọn'),
            $this->dateField('date_of_birth', 'Ngày sinh', $record?->date_of_birth?->format('Y-m-d')),
            $this->textField('address', 'Địa chỉ', $record?->address),
            $this->textField('avatar', 'Ảnh đại diện', $record?->avatar, 'Ví dụ: images/team-image2.jpg'),
            $this->passwordField('password', 'Mật khẩu', $record ? 'Để trống nếu không đổi mật khẩu' : null, required: ! $record),
        ];
    }

    private function textField(string $name, string $label, mixed $value = null, ?string $help = null, bool $required = false): array
    {
        return compact('name', 'label', 'value', 'help', 'required') + ['type' => 'text'];
    }

    private function emailField(string $name, string $label, mixed $value = null, ?string $help = null, bool $required = false): array
    {
        return compact('name', 'label', 'value', 'help', 'required') + ['type' => 'email'];
    }

    private function passwordField(string $name, string $label, ?string $help = null, bool $required = false): array
    {
        return compact('name', 'label', 'help', 'required') + ['type' => 'password', 'value' => ''];
    }

    private function dateField(string $name, string $label, mixed $value = null, ?string $help = null, bool $required = false): array
    {
        return compact('name', 'label', 'value', 'help', 'required') + ['type' => 'date'];
    }

    private function timeField(string $name, string $label, mixed $value = null, ?string $help = null, bool $required = false): array
    {
        $value = $value ? substr((string) $value, 0, 5) : null;

        return compact('name', 'label', 'value', 'help', 'required') + ['type' => 'time'];
    }

    private function datetimeField(string $name, string $label, mixed $value = null, ?string $help = null, bool $required = false): array
    {
        return compact('name', 'label', 'value', 'help', 'required') + ['type' => 'datetime-local'];
    }

    private function numberField(string $name, string $label, mixed $value = null, int|float|null $min = null, int|float|null $max = null, int|float|string|null $step = null, ?string $help = null, bool $required = false): array
    {
        return compact('name', 'label', 'value', 'min', 'max', 'step', 'help', 'required') + ['type' => 'number'];
    }

    private function textareaField(string $name, string $label, mixed $value = null, ?string $help = null, bool $required = false, int $rows = 4): array
    {
        return compact('name', 'label', 'value', 'help', 'required', 'rows') + ['type' => 'textarea'];
    }

    private function selectField(string $name, string $label, mixed $value, array $options, ?string $empty = null, bool $required = false): array
    {
        return compact('name', 'label', 'value', 'options', 'empty', 'required') + ['type' => 'select'];
    }

    private function departmentOptions(): array
    {
        return Department::orderBy('name')->pluck('name', 'id')->all();
    }

    private function doctorOptions(): array
    {
        return Doctor::query()
            ->with('department')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Doctor $doctor): array => [
                $doctor->id => $doctor->name.' - '.($doctor->department?->name ?? 'Chưa có chuyên khoa'),
            ])
            ->all();
    }

    private function doctorUserOptions(?Model $record): array
    {
        return User::query()
            ->where('role', 'doctor')
            ->where(function ($query) use ($record): void {
                $query->whereDoesntHave('doctor');

                if ($record?->user_id) {
                    $query->orWhere('id', $record->user_id);
                }
            })
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (User $user): array => [$user->id => $user->name.' - '.$user->email])
            ->all();
    }

    private function patientOptions(): array
    {
        return User::query()
            ->where('role', 'patient')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (User $user): array => [$user->id => $user->name.' - '.$user->email])
            ->all();
    }

    private function authorOptions(?Model $record): array
    {
        return User::query()
            ->whereIn('role', ['admin', 'receptionist'])
            ->when($record?->user_id, fn ($query) => $query->orWhere('id', $record->user_id))
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (User $user): array => [$user->id => $user->name.' - '.$this->roleLabels[$user->role]])
            ->all();
    }

    private function serviceOptions(): array
    {
        return Service::query()
            ->with('department')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Service $service): array => [
                $service->id => $service->name.' - '.($service->department?->name ?? 'Chưa có chuyên khoa'),
            ])
            ->all();
    }

    private function scheduleOptions(): array
    {
        return DoctorSchedule::query()
            ->with('doctor')
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get()
            ->mapWithKeys(fn (DoctorSchedule $schedule): array => [
                $schedule->id => ($schedule->doctor?->name ?? 'Chưa có bác sĩ').' - '.($this->weekdays[$schedule->weekday] ?? 'Không rõ').' '.substr($schedule->start_time, 0, 5),
            ])
            ->all();
    }

    private function appointmentOptions(?Model $record): array
    {
        return Appointment::query()
            ->where(function ($query) use ($record): void {
                $query->whereDoesntHave('medicalRecord');

                if ($record?->appointment_id) {
                    $query->orWhere('id', $record->appointment_id);
                }
            })
            ->latest('appointment_date')
            ->limit(80)
            ->get()
            ->mapWithKeys(fn (Appointment $appointment): array => [
                $appointment->id => '#'.$appointment->id.' - '.$appointment->patient_name.' - '.$appointment->appointment_date?->format('d/m/Y'),
            ])
            ->all();
    }

    private function appointmentStatusOptions(): array
    {
        return [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'completed' => 'Hoàn tất',
            'cancelled' => 'Đã hủy',
        ];
    }

    private function paymentMethodOptions(): array
    {
        return [
            'cash' => 'Tiền mặt tại bệnh viện',
            'vnpay' => 'VNPay sandbox',
            'momo' => 'MoMo sandbox',
        ];
    }

    private function syncAppointmentPayment(string $resource, Model $record, ?string $method): void
    {
        if ($resource !== 'appointments' || ! $record instanceof Appointment || ! $method || ! $record->service_id) {
            return;
        }

        $service = Service::find($record->service_id);

        if (! $service) {
            return;
        }

        $payment = Payment::firstOrNew(['appointment_id' => $record->id]);
        $isPaid = $payment->exists && $payment->status === 'paid';
        $totalAmount = (float) $service->price;
        $depositAmount = Payment::depositAmountFor($totalAmount);

        $payment->fill([
            'user_id' => $record->user_id,
            'method' => $method,
            'status' => $isPaid ? 'paid' : ($method === 'cash' ? 'unpaid' : 'pending'),
            'amount' => $depositAmount,
            'total_amount' => $totalAmount,
            'deposit_amount' => $depositAmount,
            'currency' => 'VND',
            'paid_at' => $isPaid ? $payment->paid_at : null,
            'deposit_paid_at' => $isPaid ? ($payment->deposit_paid_at ?? $payment->paid_at) : null,
        ]);
        $payment->save();
    }
}
