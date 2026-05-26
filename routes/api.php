<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\DoctorScheduleController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\MedicalRecordController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\StatisticsController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function (): void {
    Route::get('home', HomeController::class)->name('home');

    Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
    Route::middleware('auth.api')->group(function (): void {
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::patch('auth/profile', [AuthController::class, 'updateProfile'])->name('auth.profile');
        Route::patch('auth/password', [AuthController::class, 'changePassword'])->name('auth.password');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    });

    Route::apiResource('banners', BannerController::class)->only(['index', 'show']);
    Route::apiResource('departments', DepartmentController::class)->only(['index', 'show']);
    Route::apiResource('doctors', DoctorController::class)->only(['index', 'show']);
    Route::apiResource('doctor-schedules', DoctorScheduleController::class)
        ->only(['index', 'show'])
        ->parameters(['doctor-schedules' => 'doctorSchedule']);
    Route::apiResource('services', ServiceController::class)->only(['index', 'show']);
    Route::apiResource('news', NewsController::class)->only(['index', 'show']);
    Route::post('contacts', [ContactController::class, 'store'])->name('contacts.store');
    Route::post('appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('payments/vnpay/ipn', [PaymentController::class, 'vnpayIpn'])->name('payments.vnpay.ipn');
    Route::post('payments/momo/ipn', [PaymentController::class, 'momoIpn'])->name('payments.momo.ipn');

    Route::middleware(['auth.api', 'role:patient,admin'])->prefix('patient')->name('patient.')->group(function (): void {
        Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
        Route::post('appointments', [AppointmentController::class, 'store'])->name('appointments.store');
        Route::get('appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
        Route::patch('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
        Route::get('medical-records', [MedicalRecordController::class, 'index'])->name('medical-records.index');
        Route::get('medical-records/{medicalRecord}', [MedicalRecordController::class, 'show'])->name('medical-records.show');
    });

    Route::middleware(['auth.api', 'role:doctor,admin'])->prefix('doctor')->name('doctor.')->group(function (): void {
        Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
        Route::get('appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
        Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');
        Route::apiResource('medical-records', MedicalRecordController::class)
            ->only(['index', 'store', 'show', 'update'])
            ->parameters(['medical-records' => 'medicalRecord']);
    });

    Route::middleware(['auth.api', 'role:admin,receptionist'])->prefix('staff')->name('staff.')->group(function (): void {
        Route::get('statistics/overview', [StatisticsController::class, 'overview'])->name('statistics.overview');
        Route::get('statistics/appointments', [AppointmentController::class, 'statistics'])->name('statistics.appointments');

        Route::apiResource('patients', PatientController::class)->only(['index', 'show', 'update']);
        Route::get('patients/{patient}/appointments', [PatientController::class, 'appointments'])->name('patients.appointments');
        Route::get('patients/{patient}/medical-records', [PatientController::class, 'medicalRecords'])->name('patients.medical-records');

        Route::apiResource('appointments', AppointmentController::class)->only(['index', 'store', 'show', 'update']);
        Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');
        Route::patch('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        Route::patch('payments/{payment}/cash-paid', [PaymentController::class, 'markCashPaid'])->name('payments.cash-paid');
        Route::patch('payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');

        Route::apiResource('contacts', ContactController::class)->only(['index', 'show', 'update']);
        Route::patch('contacts/{contact}/status', [ContactController::class, 'updateStatus'])->name('contacts.status');

        Route::apiResource('doctor-schedules', DoctorScheduleController::class)
            ->only(['index', 'show'])
            ->parameters(['doctor-schedules' => 'doctorSchedule']);
        Route::apiResource('doctors', DoctorController::class)->only(['index', 'show']);
        Route::apiResource('departments', DepartmentController::class)->only(['index', 'show']);
        Route::apiResource('services', ServiceController::class)->only(['index', 'show']);
    });

    Route::middleware(['auth.api', 'role:admin'])->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('statistics/overview', [StatisticsController::class, 'overview'])->name('statistics.overview');
        Route::get('statistics/appointments', [AppointmentController::class, 'statistics'])->name('statistics.appointments');
        Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');
        Route::patch('contacts/{contact}/status', [ContactController::class, 'updateStatus'])->name('contacts.status');

        Route::apiResource('patients', PatientController::class)->only(['index', 'show', 'update']);
        Route::get('patients/{patient}/appointments', [PatientController::class, 'appointments'])->name('patients.appointments');
        Route::get('patients/{patient}/medical-records', [PatientController::class, 'medicalRecords'])->name('patients.medical-records');

        Route::apiResource('users', UserController::class);
        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('doctors', DoctorController::class);
        Route::apiResource('doctor-schedules', DoctorScheduleController::class)
            ->parameters(['doctor-schedules' => 'doctorSchedule']);
        Route::apiResource('services', ServiceController::class);
        Route::apiResource('appointments', AppointmentController::class);
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        Route::patch('payments/{payment}/cash-paid', [PaymentController::class, 'markCashPaid'])->name('payments.cash-paid');
        Route::patch('payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');
        Route::apiResource('medical-records', MedicalRecordController::class)
            ->parameters(['medical-records' => 'medicalRecord']);
        Route::apiResource('news', NewsController::class);
        Route::apiResource('contacts', ContactController::class);
        Route::apiResource('banners', BannerController::class);
    });
});
