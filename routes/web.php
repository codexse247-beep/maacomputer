<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Portal;
use Illuminate\Routing\RedirectController;
use Illuminate\Support\Facades\Route;

// Public website
Route::controller(HomeController::class)->group(function () {
    Route::get('/', 'index')->name('home');
    Route::get('departments', 'departments')->name('departments');
    Route::get('admission', 'admission')->name('admission');
    Route::post('admission', 'storeAdmission')->name('admission.store')->middleware('throttle:5,1');
    Route::get('about', 'about')->name('about');
    Route::get('instructors', 'instructors')->name('instructors');
    Route::get('gallery', 'gallery')->name('gallery');
    Route::get('faq', 'faq')->name('faq');
    Route::get('verify', 'verify')->name('verify')->middleware('throttle:20,1');
    Route::get('privacy', 'privacy')->name('privacy');
    Route::get('contact', 'contact')->name('contact');
    Route::post('contact', 'storeContact')->name('contact.store')->middleware('throttle:5,1');
    Route::get('notices', 'notices')->name('notices');
    Route::get('login', 'login')->name('login');
    Route::get('forgot-password', 'forgot')->name('forgot');
    Route::post('forgot-password', 'storeForgot')->name('forgot.store')->middleware('throttle:5,1');
});

// Student portal
Route::prefix('portal')->name('portal.')->group(function () {
    Route::middleware('guest:student')->group(function () {
        // GET only — Route::redirect() would also swallow the login POST below once routes are cached.
        Route::get('login', RedirectController::class)->defaults('destination', '/login')->defaults('status', 302)->name('login');
        Route::post('login', [Portal\AuthController::class, 'login'])->name('login.store')->middleware('throttle:10,1');
    });
    Route::middleware('auth:student')->group(function () {
        Route::get('/', [Portal\DashboardController::class, 'index'])->name('dashboard');
        Route::get('profile', [Portal\DashboardController::class, 'profile'])->name('profile');
        Route::get('id-card', [Portal\DashboardController::class, 'idCard'])->name('id-card');
        Route::get('receipts/{payment}', [Portal\DashboardController::class, 'receipt'])->name('receipt');
        Route::post('password', [Portal\AuthController::class, 'password'])->name('password');
        Route::post('logout', [Portal\AuthController::class, 'logout'])->name('logout');
    });
});

// Admin panel
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:web')->group(function () {
        Route::get('login', RedirectController::class)->defaults('destination', '/login')->defaults('status', 302)->name('login');
        Route::post('login', [Admin\AuthController::class, 'login'])->name('login.store')->middleware('throttle:10,1');
    });

    // Always the "web" (staff) guard — a logged-in student must never pass this check.
    Route::middleware('auth:web')->group(function () {
        Route::post('logout', [Admin\AuthController::class, 'logout'])->name('logout');
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::resource('departments', Admin\DepartmentController::class)->except('show');
        Route::get('batches', [Admin\BatchController::class, 'all'])->name('batches.all');
        Route::get('departments/{department}', [Admin\BatchController::class, 'index'])->name('departments.show');
        Route::get('departments/{department}/batches/create', [Admin\BatchController::class, 'create'])->name('batches.create');
        Route::post('departments/{department}/batches', [Admin\BatchController::class, 'store'])->name('batches.store');
        Route::get('batches/{batch}', [Admin\BatchController::class, 'show'])->name('batches.show');
        Route::get('batches/{batch}/edit', [Admin\BatchController::class, 'edit'])->name('batches.edit');
        Route::put('batches/{batch}', [Admin\BatchController::class, 'update'])->name('batches.update');
        Route::delete('batches/{batch}', [Admin\BatchController::class, 'destroy'])->name('batches.destroy');
        Route::get('batches/{batch}/id-cards', [Admin\PrintController::class, 'batchIdCards'])->name('batches.id-cards');
        Route::get('batches/{batch}/export', [Admin\BatchController::class, 'export'])->name('batches.export');

        Route::get('attendance', [Admin\AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('batches/{batch}/attendance', fn (\App\Models\Batch $batch) => redirect()->route('admin.attendance.index', ['batch' => $batch->id]))->name('attendance.create');
        Route::post('batches/{batch}/attendance', [Admin\AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('batches/{batch}/attendance/history', [Admin\AttendanceController::class, 'history'])->name('attendance.history');

        Route::get('results', [Admin\ResultController::class, 'index'])->name('results.index');
        Route::post('batches/{batch}/results', [Admin\ResultController::class, 'store'])->name('results.store');
        Route::get('batches/{batch}/results/export', [Admin\ResultController::class, 'export'])->name('results.export');

        Route::resource('teachers', Admin\TeacherController::class)->except(['show', 'create', 'edit']);
        Route::get('enquiries', [Admin\EnquiryController::class, 'index'])->name('enquiries.index');
        Route::patch('enquiries/{enquiry}', [Admin\EnquiryController::class, 'update'])->name('enquiries.update');
        Route::delete('enquiries/{enquiry}', [Admin\EnquiryController::class, 'destroy'])->name('enquiries.destroy');

        Route::get('reports', [Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{report}', [Admin\ReportController::class, 'show'])->name('reports.show');

        Route::middleware('can:admin')->group(function () {
            Route::get('settings', [Admin\SettingController::class, 'index'])->name('settings.index');
            Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
            Route::post('settings/users', [Admin\SettingController::class, 'storeUser'])->name('settings.users.store');
            Route::put('settings/users/{user}', [Admin\SettingController::class, 'updateUser'])->name('settings.users.update');
            Route::delete('settings/users/{user}', [Admin\SettingController::class, 'destroyUser'])->name('settings.users.destroy');
            Route::post('settings/faqs', [Admin\SettingController::class, 'storeFaq'])->name('settings.faqs.store');
            Route::delete('settings/faqs/{faq}', [Admin\SettingController::class, 'destroyFaq'])->name('settings.faqs.destroy');
            Route::post('settings/gallery', [Admin\SettingController::class, 'storePhoto'])->name('settings.gallery.store');
            Route::delete('settings/gallery/{photo}', [Admin\SettingController::class, 'destroyPhoto'])->name('settings.gallery.destroy');
        });

        Route::get('students', [Admin\StudentController::class, 'index'])->name('students.index');
        Route::get('students/create', [Admin\StudentController::class, 'create'])->name('students.create');
        Route::post('students', [Admin\StudentController::class, 'store'])->name('students.store');
        Route::get('students/{student}', [Admin\StudentController::class, 'show'])->name('students.show');
        Route::get('students/{student}/edit', [Admin\StudentController::class, 'edit'])->name('students.edit');
        Route::put('students/{student}', [Admin\StudentController::class, 'update'])->name('students.update');
        Route::delete('students/{student}', [Admin\StudentController::class, 'destroy'])->name('students.destroy');
        Route::post('students/{student}/password', [Admin\StudentController::class, 'resetPassword'])->name('students.password');
        Route::post('students/{student}/notes', [Admin\StudentController::class, 'addNote'])->name('students.notes');
        Route::post('students/{student}/documents', [Admin\StudentController::class, 'addDocument'])->name('students.documents');
        Route::get('documents/{document}', [Admin\StudentController::class, 'document'])->name('documents.show');
        Route::post('students/{student}/assessments', [Admin\StudentController::class, 'addAssessment'])->name('students.assessments');
        Route::delete('assessments/{assessment}', [Admin\StudentController::class, 'deleteAssessment'])->name('assessments.destroy');
        Route::get('students/{student}/id-card', [Admin\PrintController::class, 'idCard'])->name('students.id-card');
        Route::get('students/{student}/form', [Admin\PrintController::class, 'admissionForm'])->name('students.form');
        Route::post('students/{student}/certificate', [Admin\StudentController::class, 'issueCertificate'])->name('students.certificate');

        Route::get('payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
        Route::post('students/{student}/payments', [Admin\PaymentController::class, 'store'])->name('payments.store');
        Route::get('payments/{payment}/receipt', [Admin\PrintController::class, 'receipt'])->name('payments.receipt');
        Route::delete('payments/{payment}', [Admin\PaymentController::class, 'destroy'])->name('payments.destroy');

        Route::get('notices', [Admin\NoticeController::class, 'index'])->name('notices.index');
        Route::post('notices', [Admin\NoticeController::class, 'store'])->name('notices.store');
        Route::put('notices/{notice}', [Admin\NoticeController::class, 'update'])->name('notices.update');
        Route::delete('notices/{notice}', [Admin\NoticeController::class, 'destroy'])->name('notices.destroy');
    });
});
