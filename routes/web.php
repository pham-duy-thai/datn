<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\Web\AdminCrudController;
use App\Http\Controllers\Web\ChatbotController;
use App\Http\Controllers\Web\MedicalAiController;
use App\Http\Controllers\Web\MedicalImageController;
use App\Http\Controllers\Web\DoctorMedicalRecordController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return in_array(auth()->user()->role, ['admin', 'receptionist'], true)
        ? redirect()->route('admin.dashboard')
        : redirect()->route('home');
})->name('entry');

Route::middleware('guest')->group(function (): void {
    Route::get('/dang-nhap', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/dang-nhap', [AuthController::class, 'login'])->name('login.store');

    Route::get('/dang-ky', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/dang-ky', [AuthController::class, 'register'])->name('register.store');

    Route::get('/quen-mat-khau', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/quen-mat-khau', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/xac-nhan-ma', [AuthController::class, 'showVerifyCode'])->name('password.code');
    Route::post('/xac-nhan-ma', [AuthController::class, 'verifyResetCode'])->name('password.code.verify');
    Route::get('/dat-lai-mat-khau', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/dat-lai-mat-khau', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/dang-xuat', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::post('/tro-ly-ai', ChatbotController::class)->name('chatbot.reply');

Route::get('/thanh-toan/vnpay/return', [PaymentController::class, 'vnpayReturn'])->name('payments.vnpay.return');
Route::get('/thanh-toan/momo/return', [PaymentController::class, 'momoReturn'])->name('payments.momo.return');

Route::middleware('auth')->group(function (): void {
    Route::get('/trang-chu', [PageController::class, 'home'])->name('home');
    Route::get('/tai-khoan', [PageController::class, 'account'])->name('account.show');
    Route::patch('/tai-khoan/mat-khau', [PageController::class, 'updatePassword'])->name('account.password.update');
    Route::post('/tai-khoan/bo-qua-doi-mat-khau', [PageController::class, 'skipPasswordChange'])->name('account.password.skip');
    Route::get('/thanh-toan/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    Route::get('/thanh-toan/{payment}/thanh-toan-lai', [PaymentController::class, 'retry'])->name('payments.retry');

    Route::get('/khoa', [PageController::class, 'departments'])->name('departments.index');
    Route::get('/khoa/{department:slug}', [PageController::class, 'departmentShow'])->name('departments.show');

    Route::get('/bac-si', [PageController::class, 'doctors'])->name('doctors.index');
    Route::get('/bac-si/{doctor}', [PageController::class, 'doctorShow'])
        ->whereNumber('doctor')
        ->name('doctors.show');

    Route::get('/dich-vu', [PageController::class, 'services'])->name('services.index');
    Route::get('/dich-vu/{service:slug}', [PageController::class, 'serviceShow'])->name('services.show');

    Route::get('/tin-tuc', [PageController::class, 'news'])->name('news.index');
    Route::get('/tin-tuc/{news:slug}', [PageController::class, 'newsShow'])->name('news.show');

    Route::get('/dat-lich-kham', [PageController::class, 'appointmentCreate'])->name('appointments.create');
    Route::post('/dat-lich-kham', [PageController::class, 'appointmentStore'])->name('appointments.store');

    Route::get('/lien-he', [PageController::class, 'contactCreate'])->name('contact.create');
    Route::post('/lien-he', [PageController::class, 'contactStore'])->name('contact.store');
    Route::post('/anh-y-te', [MedicalImageController::class, 'store'])->name('medical-images.store');
});

Route::post('/ai-ho-tro-bac-si', MedicalAiController::class)
    ->middleware(['auth', 'role:doctor'])
    ->name('doctor.ai.assist');

Route::middleware(['auth', 'role:doctor'])
    ->prefix('bac-si/ho-so-benh-nhan')
    ->name('doctor.records.')
    ->group(function (): void {
        Route::get('/', [DoctorMedicalRecordController::class, 'index'])->name('index');
        Route::get('/them', [DoctorMedicalRecordController::class, 'create'])->name('create');
        Route::post('/', [DoctorMedicalRecordController::class, 'store'])->name('store');
        Route::get('/{medicalRecord}/sua', [DoctorMedicalRecordController::class, 'edit'])->name('edit');
        Route::put('/{medicalRecord}', [DoctorMedicalRecordController::class, 'update'])->name('update');
        Route::post('/{medicalRecord}/xet-nghiem', [DoctorMedicalRecordController::class, 'storeLabResult'])->name('lab-results.store');
        Route::delete('/{medicalRecord}/xet-nghiem/{labResult}', [DoctorMedicalRecordController::class, 'destroyLabResult'])->name('lab-results.destroy');
    });

Route::middleware(['auth', 'role:admin,receptionist'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

        Route::get('/nguoi-dung/them', [AdminCrudController::class, 'create'])->defaults('resource', 'users')->name('users.create');
        Route::post('/nguoi-dung', [AdminCrudController::class, 'store'])->defaults('resource', 'users')->name('users.store');
        Route::get('/nguoi-dung/{id}/sua', [AdminCrudController::class, 'edit'])->defaults('resource', 'users')->name('users.edit');
        Route::put('/nguoi-dung/{id}', [AdminCrudController::class, 'update'])->defaults('resource', 'users')->name('users.update');
        Route::patch('/nguoi-dung/{user}/reset-mat-khau', [AdminController::class, 'resetUserPassword'])->name('users.reset-password');
        Route::delete('/nguoi-dung/{id}', [AdminCrudController::class, 'destroy'])->defaults('resource', 'users')->name('users.destroy');
        Route::get('/nguoi-dung', [AdminController::class, 'users'])->name('users.index');

        Route::get('/benh-nhan/them', [AdminCrudController::class, 'create'])->defaults('resource', 'patients')->name('patients.create');
        Route::post('/benh-nhan', [AdminCrudController::class, 'store'])->defaults('resource', 'patients')->name('patients.store');
        Route::get('/benh-nhan/{id}/sua', [AdminCrudController::class, 'edit'])->defaults('resource', 'patients')->name('patients.edit');
        Route::put('/benh-nhan/{id}', [AdminCrudController::class, 'update'])->defaults('resource', 'patients')->name('patients.update');
        Route::delete('/benh-nhan/{id}', [AdminCrudController::class, 'destroy'])->defaults('resource', 'patients')->name('patients.destroy');
        Route::get('/benh-nhan', [AdminController::class, 'patients'])->name('patients.index');

        Route::get('/chuyen-khoa/them', [AdminCrudController::class, 'create'])->defaults('resource', 'departments')->name('departments.create');
        Route::post('/chuyen-khoa', [AdminCrudController::class, 'store'])->defaults('resource', 'departments')->name('departments.store');
        Route::get('/chuyen-khoa/{id}/sua', [AdminCrudController::class, 'edit'])->defaults('resource', 'departments')->name('departments.edit');
        Route::put('/chuyen-khoa/{id}', [AdminCrudController::class, 'update'])->defaults('resource', 'departments')->name('departments.update');
        Route::delete('/chuyen-khoa/{id}', [AdminCrudController::class, 'destroy'])->defaults('resource', 'departments')->name('departments.destroy');
        Route::get('/chuyen-khoa', [AdminController::class, 'departments'])->name('departments.index');

        Route::get('/bac-si/them', [AdminCrudController::class, 'create'])->defaults('resource', 'doctors')->name('doctors.create');
        Route::post('/bac-si', [AdminCrudController::class, 'store'])->defaults('resource', 'doctors')->name('doctors.store');
        Route::get('/bac-si/{id}/sua', [AdminCrudController::class, 'edit'])->defaults('resource', 'doctors')->name('doctors.edit');
        Route::put('/bac-si/{id}', [AdminCrudController::class, 'update'])->defaults('resource', 'doctors')->name('doctors.update');
        Route::delete('/bac-si/{id}', [AdminCrudController::class, 'destroy'])->defaults('resource', 'doctors')->name('doctors.destroy');
        Route::get('/bac-si', [AdminController::class, 'doctors'])->name('doctors.index');

        Route::get('/lich-lam-viec/them', [AdminCrudController::class, 'create'])->defaults('resource', 'schedules')->name('schedules.create');
        Route::post('/lich-lam-viec', [AdminCrudController::class, 'store'])->defaults('resource', 'schedules')->name('schedules.store');
        Route::get('/lich-lam-viec/{id}/sua', [AdminCrudController::class, 'edit'])->defaults('resource', 'schedules')->name('schedules.edit');
        Route::put('/lich-lam-viec/{id}', [AdminCrudController::class, 'update'])->defaults('resource', 'schedules')->name('schedules.update');
        Route::delete('/lich-lam-viec/{id}', [AdminCrudController::class, 'destroy'])->defaults('resource', 'schedules')->name('schedules.destroy');
        Route::get('/lich-lam-viec', [AdminController::class, 'schedules'])->name('schedules.index');

        Route::get('/lich-hen/them', [AdminCrudController::class, 'create'])->defaults('resource', 'appointments')->name('appointments.create');
        Route::post('/lich-hen', [AdminCrudController::class, 'store'])->defaults('resource', 'appointments')->name('appointments.store');
        Route::get('/lich-hen/{id}/sua', [AdminCrudController::class, 'edit'])->defaults('resource', 'appointments')->name('appointments.edit');
        Route::put('/lich-hen/{id}', [AdminCrudController::class, 'update'])->defaults('resource', 'appointments')->name('appointments.update');
        Route::delete('/lich-hen/{id}', [AdminCrudController::class, 'destroy'])->defaults('resource', 'appointments')->name('appointments.destroy');
        Route::get('/lich-hen', [AdminController::class, 'appointments'])->name('appointments.index');
        Route::patch('/lich-hen/{appointment}/trang-thai', [AdminController::class, 'updateAppointmentStatus'])->name('appointments.status');

        Route::get('/ho-so-kham/them', [AdminCrudController::class, 'create'])->defaults('resource', 'medical-records')->name('medical-records.create');
        Route::post('/ho-so-kham', [AdminCrudController::class, 'store'])->defaults('resource', 'medical-records')->name('medical-records.store');
        Route::get('/ho-so-kham/{id}/sua', [AdminCrudController::class, 'edit'])->defaults('resource', 'medical-records')->name('medical-records.edit');
        Route::put('/ho-so-kham/{id}', [AdminCrudController::class, 'update'])->defaults('resource', 'medical-records')->name('medical-records.update');
        Route::delete('/ho-so-kham/{id}', [AdminCrudController::class, 'destroy'])->defaults('resource', 'medical-records')->name('medical-records.destroy');
        Route::get('/ho-so-kham', [AdminController::class, 'medicalRecords'])->name('medical-records.index');

        Route::get('/dich-vu/them', [AdminCrudController::class, 'create'])->defaults('resource', 'services')->name('services.create');
        Route::post('/dich-vu', [AdminCrudController::class, 'store'])->defaults('resource', 'services')->name('services.store');
        Route::get('/dich-vu/{id}/sua', [AdminCrudController::class, 'edit'])->defaults('resource', 'services')->name('services.edit');
        Route::put('/dich-vu/{id}', [AdminCrudController::class, 'update'])->defaults('resource', 'services')->name('services.update');
        Route::delete('/dich-vu/{id}', [AdminCrudController::class, 'destroy'])->defaults('resource', 'services')->name('services.destroy');
        Route::get('/dich-vu', [AdminController::class, 'services'])->name('services.index');

        Route::get('/tin-tuc/them', [AdminCrudController::class, 'create'])->defaults('resource', 'news')->name('news.create');
        Route::post('/tin-tuc', [AdminCrudController::class, 'store'])->defaults('resource', 'news')->name('news.store');
        Route::get('/tin-tuc/{id}/sua', [AdminCrudController::class, 'edit'])->defaults('resource', 'news')->name('news.edit');
        Route::put('/tin-tuc/{id}', [AdminCrudController::class, 'update'])->defaults('resource', 'news')->name('news.update');
        Route::delete('/tin-tuc/{id}', [AdminCrudController::class, 'destroy'])->defaults('resource', 'news')->name('news.destroy');
        Route::get('/tin-tuc', [AdminController::class, 'news'])->name('news.index');

        Route::get('/lien-he/them', [AdminCrudController::class, 'create'])->defaults('resource', 'contacts')->name('contacts.create');
        Route::post('/lien-he', [AdminCrudController::class, 'store'])->defaults('resource', 'contacts')->name('contacts.store');
        Route::get('/lien-he/{id}/sua', [AdminCrudController::class, 'edit'])->defaults('resource', 'contacts')->name('contacts.edit');
        Route::put('/lien-he/{id}', [AdminCrudController::class, 'update'])->defaults('resource', 'contacts')->name('contacts.update');
        Route::delete('/lien-he/{id}', [AdminCrudController::class, 'destroy'])->defaults('resource', 'contacts')->name('contacts.destroy');
        Route::get('/lien-he', [AdminController::class, 'contacts'])->name('contacts.index');
        Route::patch('/lien-he/{contact}/trang-thai', [AdminController::class, 'updateContactStatus'])->name('contacts.status');

        Route::get('/thanh-toan', [AdminController::class, 'payments'])->name('payments.index');
        Route::patch('/thanh-toan/{payment}/thu-tien-mat', [PaymentController::class, 'markCashPaid'])->name('payments.cash-paid');
        Route::patch('/thanh-toan/{payment}/huy', [PaymentController::class, 'cancel'])->name('payments.cancel');
    });
