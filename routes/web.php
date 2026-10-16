<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Portal;
use App\Http\Middleware\SetLocale;
use Illuminate\Routing\RedirectController;
use Illuminate\Support\Facades\Route;

// Public website (Bangla, or English with ?lang=en)
Route::controller(HomeController::class)->middleware(SetLocale::class)->group(function () {
    Route::get('sitemap.xml', 'sitemap')->name('sitemap');
    Route::get('robots.txt', 'robots')->name('robots');
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
        Route::post('login', [Portal\AuthController::class, 'login'])->name('login.store')->middleware(['throttle:10,1', SetLocale::class]);
    });
    Route::middleware('auth:student')->group(function () {
        Route::get('/', [Portal\DashboardController::class, 'index'])->name('dashboard');
        Route::get('profile', [Portal\DashboardController::class, 'profile'])->name('profile');
        Route::get('id-card', [Portal\DashboardController::class, 'idCard'])->name('id-card');
        Route::get('receipts/{payment}', [Portal\DashboardController::class, 'receipt'])->name('receipt');
        Route::get('certificate', [Portal\DashboardController::class, 'certificate'])->name('certificate');
        Route::get('pay', [Portal\PayController::class, 'index'])->name('pay');
        Route::post('pay', [Portal\PayController::class, 'store'])->name('pay.store')->middleware('throttle:10,1');
        Route::put('profile', [Portal\DashboardController::class, 'updateProfile'])->name('profile.update');
        Route::get('calendar', [Portal\DashboardController::class, 'calendar'])->name('calendar');
        Route::get('study', [Portal\StudyController::class, 'index'])->name('study');
        Route::get('assignments/{assignment}', [Portal\StudyController::class, 'assignment'])->name('assignments.show');
        Route::post('assignments/{assignment}', [Portal\StudyController::class, 'submit'])->name('assignments.submit')->middleware('throttle:20,1');
        Route::get('assignments/{assignment}/file', [Portal\StudyController::class, 'assignmentFile'])->name('assignments.file');
        Route::get('submissions/{submission}/file', [Portal\StudyController::class, 'submissionFile'])->name('submissions.file');
        Route::get('quizzes/{quiz}', [Portal\StudyController::class, 'quiz'])->name('quizzes.show');
        Route::post('quizzes/{quiz}', [Portal\StudyController::class, 'submitQuiz'])->name('quizzes.submit');
        Route::get('videos', [Portal\VideoController::class, 'index'])->name('videos');
        Route::get('videos/{video}/file', [Portal\VideoController::class, 'file'])->name('videos.file');
        Route::post('videos/{video}/watched', [Portal\VideoController::class, 'watched'])->name('videos.watched');
        Route::get('course-files/{file}', [Portal\VideoController::class, 'download'])->name('course-files.download');
        Route::post('password', [Portal\AuthController::class, 'password'])->name('password');
        Route::post('logout', [Portal\AuthController::class, 'logout'])->name('logout');
    });
});

// "Log in with Google" for staff and students
Route::middleware(SetLocale::class)->controller(\App\Http\Controllers\GoogleLoginController::class)->group(function () {
    Route::get('auth/google/callback', 'callback')->name('google.callback');
    Route::get('auth/google/{who}', 'redirect')->whereIn('who', ['staff', 'student'])->name('google.redirect')->middleware('throttle:20,1');
});

// Staff "forgot password" (email link)
Route::middleware(['guest:web', SetLocale::class])->prefix('staff')->name('staff.password.')->group(function () {
    Route::get('forgot-password', [Admin\PasswordResetController::class, 'request'])->name('request');
    Route::post('forgot-password', [Admin\PasswordResetController::class, 'send'])->name('email')->middleware('throttle:5,1');
    Route::get('reset-password/{token}', [Admin\PasswordResetController::class, 'form'])->name('reset');
    Route::post('reset-password', [Admin\PasswordResetController::class, 'reset'])->name('update')->middleware('throttle:10,1');
});

// A Drive file shared with "anyone with the link" (Admin › ড্রাইভ)
Route::get('d/{token}', [Admin\DriveController::class, 'shared'])->name('drive.shared')->where('token', '[A-Za-z0-9]{32}')->middleware('throttle:60,1');

// Admin panel
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:web')->group(function () {
        Route::get('login', [Admin\AuthController::class, 'show'])->name('login');
        Route::post('login', [Admin\AuthController::class, 'login'])->name('login.store')->middleware(['throttle:10,1', SetLocale::class]);
    });

    // Always the "web" (staff) guard — a logged-in student must never pass this check.
    // Each group below is one "area" of the panel; User::AREAS decides which roles may open it.
    Route::middleware('auth:web')->group(function () {
        Route::post('logout', [Admin\AuthController::class, 'logout'])->name('logout');
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        // Courses, batches and teachers (registered before the "view" routes so /create and /edit win)
        Route::middleware("can:area,'academics'")->group(function () {
            Route::resource('departments', Admin\DepartmentController::class)->except('show');
            Route::get('departments/{department}/batches/create', [Admin\BatchController::class, 'create'])->name('batches.create');
            Route::post('departments/{department}/batches', [Admin\BatchController::class, 'store'])->name('batches.store');
            Route::get('batches/{batch}/edit', [Admin\BatchController::class, 'edit'])->name('batches.edit');
            Route::put('batches/{batch}', [Admin\BatchController::class, 'update'])->name('batches.update');
            Route::delete('batches/{batch}', [Admin\BatchController::class, 'destroy'])->name('batches.destroy');
            Route::resource('teachers', Admin\TeacherController::class)->except(['show', 'create', 'edit']);
        });

        // Admitting and editing students, website applications
        Route::middleware("can:area,'admissions'")->group(function () {
            Route::get('students/create', [Admin\StudentController::class, 'create'])->name('students.create');
            Route::post('students', [Admin\StudentController::class, 'store'])->name('students.store');
            Route::get('students/{student}/edit', [Admin\StudentController::class, 'edit'])->name('students.edit');
            Route::put('students/{student}', [Admin\StudentController::class, 'update'])->name('students.update');
            Route::delete('students/{student}', [Admin\StudentController::class, 'destroy'])->name('students.destroy');
            Route::post('students/{student}/password', [Admin\StudentController::class, 'resetPassword'])->name('students.password');
            Route::post('students/{student}/documents', [Admin\StudentController::class, 'addDocument'])->name('students.documents');
            Route::get('students/{student}/form', [Admin\PrintController::class, 'admissionForm'])->name('students.form');
            Route::get('students/{student}/id-card', [Admin\PrintController::class, 'idCard'])->name('students.id-card');
            Route::get('batches/{batch}/id-cards', [Admin\PrintController::class, 'batchIdCards'])->name('batches.id-cards');
            Route::post('students/{student}/certificate', [Admin\StudentController::class, 'issueCertificate'])->name('students.certificate');
            Route::get('students/{student}/certificate', [Admin\PrintController::class, 'certificate'])->name('students.certificate.print');
            Route::get('enquiries', [Admin\EnquiryController::class, 'index'])->name('enquiries.index');
            Route::patch('enquiries/{enquiry}', [Admin\EnquiryController::class, 'update'])->name('enquiries.update');
            Route::delete('enquiries/{enquiry}', [Admin\EnquiryController::class, 'destroy'])->name('enquiries.destroy');
        });

        // Looking at students and batches
        Route::middleware("can:area,'students'")->group(function () {
            Route::get('students', [Admin\StudentController::class, 'index'])->name('students.index');
            Route::get('students/{student}', [Admin\StudentController::class, 'show'])->name('students.show');
            Route::post('students/{student}/notes', [Admin\StudentController::class, 'addNote'])->name('students.notes');
            Route::get('documents/{document}', [Admin\StudentController::class, 'document'])->name('documents.show');
            Route::get('batches', [Admin\BatchController::class, 'all'])->name('batches.all');
            Route::get('departments/{department}', [Admin\BatchController::class, 'index'])->name('departments.show');
            Route::get('batches/{batch}', [Admin\BatchController::class, 'show'])->name('batches.show');
            Route::get('batches/{batch}/export', [Admin\BatchController::class, 'export'])->name('batches.export');
        });

        // Fees: payments, receipts, dues, expenses, online payments
        Route::middleware("can:area,'fees'")->group(function () {
            Route::get('payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
            Route::post('students/{student}/payments', [Admin\PaymentController::class, 'store'])->name('payments.store');
            Route::get('payments/{payment}/receipt', [Admin\PrintController::class, 'receipt'])->name('payments.receipt');
            Route::put('payments/{payment}', [Admin\PaymentController::class, 'update'])->name('payments.update');
            Route::delete('payments/{payment}', [Admin\PaymentController::class, 'destroy'])->name('payments.destroy');

            Route::get('dues', [Admin\DueController::class, 'index'])->name('dues.index');
            Route::post('dues/remind', [Admin\DueController::class, 'remindAll'])->name('dues.remind-all');
            Route::post('dues/{student}/remind', [Admin\DueController::class, 'remind'])->name('dues.remind');

            Route::get('expenses', [Admin\ExpenseController::class, 'index'])->name('expenses.index');
            Route::post('expenses', [Admin\ExpenseController::class, 'store'])->name('expenses.store');
            Route::put('expenses/{expense}', [Admin\ExpenseController::class, 'update'])->name('expenses.update');
            Route::delete('expenses/{expense}', [Admin\ExpenseController::class, 'destroy'])->name('expenses.destroy');

            Route::get('online-payments', [Admin\OnlinePaymentController::class, 'index'])->name('online-payments.index');
            Route::post('online-payments/{online}/approve', [Admin\OnlinePaymentController::class, 'approve'])->name('online-payments.approve');
            Route::post('online-payments/{online}/reject', [Admin\OnlinePaymentController::class, 'reject'])->name('online-payments.reject');
        });

        // Teaching: attendance, results, class videos
        Route::middleware("can:area,'classes'")->group(function () {
            Route::get('attendance', [Admin\AttendanceController::class, 'index'])->name('attendance.index');
            Route::get('batches/{batch}/attendance', fn (\App\Models\Batch $batch) => redirect()->route('admin.attendance.index', ['batch' => $batch->id]))->name('attendance.create');
            Route::post('batches/{batch}/attendance', [Admin\AttendanceController::class, 'store'])->name('attendance.store');
            Route::get('batches/{batch}/attendance/history', [Admin\AttendanceController::class, 'history'])->name('attendance.history');

            Route::get('results', [Admin\ResultController::class, 'index'])->name('results.index');
            Route::post('batches/{batch}/results', [Admin\ResultController::class, 'store'])->name('results.store');
            Route::get('batches/{batch}/results/export', [Admin\ResultController::class, 'export'])->name('results.export');
            Route::post('students/{student}/assessments', [Admin\StudentController::class, 'addAssessment'])->name('students.assessments');
            Route::delete('assessments/{assessment}', [Admin\StudentController::class, 'deleteAssessment'])->name('assessments.destroy');

            Route::get('course-videos', [Admin\CourseVideoController::class, 'index'])->name('course-videos.index');
            Route::post('course-videos', [Admin\CourseVideoController::class, 'store'])->name('course-videos.store');
            Route::put('course-videos/{video}', [Admin\CourseVideoController::class, 'update'])->name('course-videos.update');
            Route::get('course-videos/{video}/file', [Admin\CourseVideoController::class, 'file'])->name('course-videos.file');
            Route::get('course-files/{file}', [Admin\CourseVideoController::class, 'download'])->name('course-files.download');
            Route::delete('course-files/{file}', [Admin\CourseVideoController::class, 'destroyFile'])->name('course-files.destroy');
            Route::delete('course-videos/{video}', [Admin\CourseVideoController::class, 'destroy'])->name('course-videos.destroy');

            Route::get('assignments', [Admin\AssignmentController::class, 'index'])->name('assignments.index');
            Route::post('assignments', [Admin\AssignmentController::class, 'store'])->name('assignments.store');
            Route::get('assignments/{assignment}', [Admin\AssignmentController::class, 'show'])->name('assignments.show');
            Route::put('assignments/{assignment}', [Admin\AssignmentController::class, 'update'])->name('assignments.update');
            Route::delete('assignments/{assignment}', [Admin\AssignmentController::class, 'destroy'])->name('assignments.destroy');
            Route::get('assignments/{assignment}/file', [Admin\AssignmentController::class, 'file'])->name('assignments.file');
            Route::post('assignments/{assignment}/results', [Admin\AssignmentController::class, 'toResults'])->name('assignments.results');
            Route::put('submissions/{submission}', [Admin\AssignmentController::class, 'grade'])->name('submissions.grade');
            Route::get('submissions/{submission}/file', [Admin\AssignmentController::class, 'submissionFile'])->name('submissions.file');

            Route::post('quizzes', [Admin\QuizController::class, 'store'])->name('quizzes.store');
            Route::get('quizzes/{quiz}', [Admin\QuizController::class, 'show'])->name('quizzes.show');
            Route::put('quizzes/{quiz}', [Admin\QuizController::class, 'update'])->name('quizzes.update');
            Route::delete('quizzes/{quiz}', [Admin\QuizController::class, 'destroy'])->name('quizzes.destroy');
            Route::post('quizzes/{quiz}/questions', [Admin\QuizController::class, 'addQuestion'])->name('quizzes.questions');
            Route::post('quizzes/{quiz}/results', [Admin\QuizController::class, 'toResults'])->name('quizzes.results');
            Route::post('quizzes/{quiz}/reset/{student}', [Admin\QuizController::class, 'resetAttempt'])->name('quizzes.reset');
            Route::delete('quiz-questions/{question}', [Admin\QuizController::class, 'destroyQuestion'])->name('quiz-questions.destroy');

            Route::get('calendar', [Admin\EventController::class, 'index'])->name('events.index');
            Route::post('calendar', [Admin\EventController::class, 'store'])->name('events.store');
            Route::put('calendar/{event}', [Admin\EventController::class, 'update'])->name('events.update');
            Route::delete('calendar/{event}', [Admin\EventController::class, 'destroy'])->name('events.destroy');
        });

        Route::middleware("can:area,'notices'")->group(function () {
            Route::get('notices', [Admin\NoticeController::class, 'index'])->name('notices.index');
            Route::post('notices', [Admin\NoticeController::class, 'store'])->name('notices.store');
            Route::put('notices/{notice}', [Admin\NoticeController::class, 'update'])->name('notices.update');
            Route::delete('notices/{notice}', [Admin\NoticeController::class, 'destroy'])->name('notices.destroy');
        });

        Route::middleware("can:area,'reports'")->group(function () {
            Route::get('reports', [Admin\ReportController::class, 'index'])->name('reports.index');
            Route::get('reports/{report}', [Admin\ReportController::class, 'show'])->name('reports.show');
        });

        Route::middleware('can:admin')->group(function () {
            Route::get('settings', [Admin\SettingController::class, 'index'])->name('settings.index');
            Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
            Route::post('settings/users', [Admin\SettingController::class, 'storeUser'])->name('settings.users.store');
            Route::put('settings/users/{user}', [Admin\SettingController::class, 'updateUser'])->name('settings.users.update');
            Route::delete('settings/users/{user}', [Admin\SettingController::class, 'destroyUser'])->name('settings.users.destroy');
            Route::post('settings/faqs', [Admin\SettingController::class, 'storeFaq'])->name('settings.faqs.store');
            Route::delete('settings/faqs/{faq}', [Admin\SettingController::class, 'destroyFaq'])->name('settings.faqs.destroy');

            Route::get('gallery', [Admin\GalleryController::class, 'index'])->name('gallery.index');
            Route::post('gallery', [Admin\GalleryController::class, 'store'])->name('gallery.store');
            Route::delete('gallery/{item}', [Admin\GalleryController::class, 'destroy'])->name('gallery.destroy');

            Route::get('drive', [Admin\DriveController::class, 'index'])->name('drive.index');
            Route::post('drive/folders', [Admin\DriveController::class, 'storeFolder'])->name('drive.folders.store');
            Route::post('drive/upload', [Admin\DriveController::class, 'upload'])->name('drive.upload');
            Route::post('drive/move', [Admin\DriveController::class, 'move'])->name('drive.move');
            Route::post('drive/trash', [Admin\DriveController::class, 'trash'])->name('drive.trash');
            Route::post('drive/restore', [Admin\DriveController::class, 'restore'])->name('drive.restore');
            Route::post('drive/zip', [Admin\DriveController::class, 'zipSelected'])->name('drive.zip');
            Route::delete('drive/forever', [Admin\DriveController::class, 'destroy'])->name('drive.destroy');
            Route::delete('drive/trash', [Admin\DriveController::class, 'emptyTrash'])->name('drive.empty');
            Route::get('drive/{item}/download', [Admin\DriveController::class, 'download'])->name('drive.download');
            Route::get('drive/{item}/open', [Admin\DriveController::class, 'open'])->name('drive.open');
            Route::put('drive/{item}', [Admin\DriveController::class, 'update'])->name('drive.update');
            Route::post('drive/{item}/star', [Admin\DriveController::class, 'star'])->name('drive.star');
            Route::post('drive/{item}/share', [Admin\DriveController::class, 'share'])->name('drive.share');

            Route::get('activity', [Admin\ActivityController::class, 'index'])->name('activity.index');
            Route::get('messages', [Admin\MessageController::class, 'index'])->name('messages.index');
            Route::post('messages/test', [Admin\MessageController::class, 'test'])->name('messages.test');
            Route::get('messages/preview/{event}', [Admin\MessageController::class, 'preview'])->name('messages.preview');
        });
    });
});
