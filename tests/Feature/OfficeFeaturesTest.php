<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Enquiry;
use App\Models\Event;
use App\Models\Expense;
use App\Models\MessageLog;
use App\Models\OnlinePayment;
use App\Models\Payment;
use App\Models\Quiz;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OfficeFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([DatabaseSeeder::class, DemoSeeder::class]);
        $this->admin = User::create(['name' => 'অ্যাডমিন', 'email' => 'a@example.com', 'password' => 'secret-pass']);
        $this->student = Student::where('name_en', 'Tanvir Ahmed')->firstOrFail();
        $this->student->update(['email' => 'tanvir@example.com']);
    }

    private function enableEmail(): void
    {
        Setting::put(['mail_enabled' => '1', 'mail_host' => 'smtp.gmail.com', 'mail_username' => 'centre@example.com', 'mail_password' => 'app-pass']);
    }

    public function test_every_new_admin_page_loads(): void
    {
        $this->actingAs($this->admin, 'web');
        foreach (['/admin/dues', '/admin/expenses', '/admin/expenses?month=2026-01', '/admin/online-payments', '/admin/online-payments?status=approved',
            '/admin/assignments', '/admin/assignments?tab=quiz', '/admin/calendar', '/admin/calendar?month=2026-12', '/admin/activity',
            '/admin/messages', '/admin/reports/finance?period=this_year', "/admin/students/create?enquiry=999"] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin/reports/finance?format=csv')->assertOk()->assertDownload();
    }

    public function test_roles_limit_what_staff_can_open(): void
    {
        $teacher = User::create(['name' => 'শিক্ষক', 'email' => 't@example.com', 'role' => 'teacher', 'password' => 'secret-pass']);
        $accounts = User::create(['name' => 'হিসাব', 'email' => 'acc@example.com', 'role' => 'accounts', 'password' => 'secret-pass']);
        $s = $this->student;

        $this->actingAs($teacher, 'web');
        $this->get('/admin')->assertOk()->assertDontSee('মোট বকেয়া ফি')->assertDontSee('/admin/payments');
        foreach (['/admin/attendance', '/admin/results', '/admin/course-videos', '/admin/assignments', '/admin/calendar', '/admin/notices', "/admin/students/{$s->id}"] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get("/admin/students/{$s->id}")->assertDontSee('পেমেন্টের ইতিহাস')->assertDontSee('তথ্য সম্পাদনা');
        foreach (['/admin/payments', '/admin/expenses', '/admin/dues', '/admin/students/create', '/admin/enquiries', '/admin/reports', '/admin/departments', '/admin/settings', '/admin/activity'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post("/admin/students/{$s->id}/payments", ['amount' => 100, 'paid_on' => now()->toDateString(), 'item' => 'x', 'method' => Payment::METHODS[0]])->assertForbidden();

        $this->actingAs($accounts, 'web');
        foreach (['/admin/payments', '/admin/expenses', '/admin/dues', '/admin/online-payments', '/admin/reports', "/admin/students/{$s->id}"] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (['/admin/attendance', '/admin/students/create', '/admin/assignments', '/admin/settings'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_payment_edit_is_logged_and_settings_changes_too(): void
    {
        $this->actingAs($this->admin, 'web');
        $this->post('/admin/login', []); // no-op for a logged-in user
        $payment = $this->student->payments()->first();

        $this->get("/admin/students/{$this->student->id}?edit_payment={$payment->id}")->assertOk()->assertSee('সংশোধন');
        $this->put("/admin/payments/{$payment->id}", ['amount' => '১,৫০০', 'paid_on' => $payment->paid_on->toDateString(), 'item' => 'সংশোধিত কিস্তি', 'method' => Payment::METHODS[1]])
            ->assertSessionHasNoErrors();
        $this->assertSame(1500, $payment->fresh()->amount);

        $log = ActivityLog::where('subject_type', 'Payment')->where('action', 'updated')->latest('id')->first();
        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertSame('1500', $log->diff['amount'][1]);

        $this->put('/admin/settings', ['name' => 'মা কম্পিউটার', 'mail_password' => 'secret-app-pass'])->assertSessionHasNoErrors();
        $log = ActivityLog::where('subject_type', 'Setting')->latest('id')->first();
        $this->assertSame(['••••', '••••'], $log->diff['mail_password']);
        $this->put('/admin/settings', ['name' => 'মা কম্পিউটার', 'mail_password' => ''])->assertSessionHasNoErrors();
        $this->assertSame('secret-app-pass', Setting::values()['mail_password']); // blank keeps it

        $this->get('/admin/activity')->assertOk()->assertSee('সংশোধিত কিস্তি')->assertSee('পরিবর্তন করেছে');
    }

    public function test_online_application_becomes_an_admission_and_emails_the_password(): void
    {
        Mail::fake();
        $this->enableEmail();
        $gd = \App\Models\Department::where('code', 'GD')->first();
        $this->post('/admission', ['name' => 'রিমা আক্তার', 'father_name' => 'করিম', 'phone' => '01711000000', 'email' => 'rima@example.com', 'department_id' => $gd->id, 'preferred_time' => 'সকাল']);
        $enquiry = Enquiry::where('type', 'admission')->firstOrFail();

        $this->actingAs($this->admin, 'web');
        $this->get('/admin/enquiries')->assertSee("enquiry={$enquiry->id}", false);
        $this->get("/admin/students/create?enquiry={$enquiry->id}")->assertOk()->assertSee('রিমা আক্তার')->assertSee('অনলাইন আবেদন থেকে ভর্তি');

        $batch = Batch::where('department_id', $gd->id)->first();
        $this->post('/admin/students', [
            'enquiry_id' => $enquiry->id, 'name_bn' => 'রিমা আক্তার', 'name_en' => 'Rima Akter', 'father_name' => 'করিম', 'mother_name' => 'সালমা',
            'gender' => 'মহিলা', 'guardian_phone' => '01711000001', 'phone' => '01711000000', 'email' => 'rima@example.com',
            'present_village' => 'বারইয়ারহাট', 'present_upazila' => 'মীরসরাই', 'present_district' => 'চট্টগ্রাম',
            'batch_id' => $batch->id, 'admission_date' => now()->toDateString(), 'admission_type' => 'নতুন ভর্তি', 'installments' => 3,
        ])->assertSessionHasNoErrors()->assertSessionHas('ok', fn ($m) => str_contains($m, 'ইমেইল করা হয়েছে'));

        $rima = Student::where('name_en', 'Rima Akter')->firstOrFail();
        $this->assertSame('done', $enquiry->fresh()->status);
        $this->assertStringContainsString('পছন্দের সময়: সকাল', $rima->notes()->first()->body);

        $log = MessageLog::where('event', 'admission')->where('student_id', $rima->id)->firstOrFail();
        $this->assertSame('sent', $log->status);
        $this->assertStringContainsString($rima->student_code, $log->message);
        $this->assertStringContainsString('******', $log->message); // the password is never stored
    }

    public function test_certificate_print_for_admin_and_student(): void
    {
        $s = $this->student;
        $this->actingAs($this->admin, 'web');
        $this->get("/admin/students/{$s->id}/certificate")->assertNotFound();
        $this->post("/admin/students/{$s->id}/certificate")->assertSessionHas('ok');
        $s->refresh();
        $this->get("/admin/students/{$s->id}/certificate")->assertOk()->assertSee('Certificate of Completion')->assertSee($s->name_en)->assertSee($s->certificate_no);
        auth('web')->logout();

        $this->actingAs($s, 'student')->get('/portal/certificate')->assertOk()->assertSee($s->certificate_no);
        $this->get('/portal')->assertSee('সনদ দেখুন ও প্রিন্ট করুন');
    }

    public function test_dues_reminders_and_daily_automatic_emails(): void
    {
        Mail::fake();
        $s = $this->student;
        $s->update(['next_installment_date' => now()->subDays(3), 'next_installment_amount' => 2000]);

        $this->actingAs($this->admin, 'web');
        $this->get('/admin/dues')->assertOk()->assertSee($s->name_bn)->assertSee('ইমেইল এখনো চালু হয়নি');

        $this->enableEmail();
        $this->post("/admin/dues/{$s->id}/remind")->assertSessionHas('ok');
        $this->post('/admin/dues/remind')->assertSessionHas('ok');
        $this->assertSame(2, MessageLog::where('event', 'due')->where('student_id', $s->id)->where('status', 'sent')->count());

        // Automatic: two days before the date, once a day, only when switched on
        MessageLog::query()->delete();
        $s->update(['next_installment_date' => now()->addDays(2)]);
        $this->artisan('fees:remind')->assertSuccessful();
        $this->assertSame(0, MessageLog::count());
        Setting::put(['due_auto' => '1', 'due_days_before' => '2']);
        $this->artisan('fees:remind')->assertSuccessful();
        $this->artisan('fees:remind')->assertSuccessful();
        $this->assertSame(1, MessageLog::where('event', 'due')->where('student_id', $s->id)->count());
    }

    public function test_expenses_and_profit(): void
    {
        $this->actingAs($this->admin, 'web');
        $this->post('/admin/expenses', ['spent_on' => now()->toDateString(), 'category' => 'বাড়ি ভাড়া', 'amount' => '৫,০০০', 'note' => 'অক্টোবর'])->assertSessionHasNoErrors();
        $this->post('/admin/expenses', ['spent_on' => now()->toDateString(), 'category' => 'ভুল খাত', 'amount' => 10])->assertSessionHasErrors('category');
        $e = Expense::firstOrFail();
        $this->assertSame(5000, $e->amount);
        $this->put("/admin/expenses/{$e->id}", ['spent_on' => now()->toDateString(), 'category' => 'বাড়ি ভাড়া', 'amount' => 6000])->assertSessionHasNoErrors();

        $income = (int) Payment::whereBetween('paid_on', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('amount');
        $this->get('/admin/expenses')->assertOk()->assertSee(taka(6000))->assertSee(taka(abs($income - 6000)));
        $this->get('/admin/reports/finance?period=this_month')->assertOk()->assertSee('৬,০০০');
        $this->get('/admin')->assertOk()->assertSee('আয় ও খরচ');
        $this->delete("/admin/expenses/{$e->id}")->assertRedirect();
        $this->assertSame(0, Expense::count());
    }

    public function test_bkash_payment_from_portal_is_approved_or_rejected_by_the_office(): void
    {
        Mail::fake();
        $s = $this->student;
        $due = $s->loadSum('payments', 'amount')->totalDue();
        $this->assertGreaterThan(0, $due);

        // Off until a number is set
        $this->actingAs($s, 'student')->get('/portal/pay')->assertOk()->assertSee('অনলাইন পেমেন্ট এখনো চালু হয়নি');
        Setting::put(['bkash_number' => '01711223344']);
        $this->enableEmail();
        $this->get('/portal/pay')->assertOk()->assertSee('০১৭১১২২৩৩৪৪');
        $this->get('/portal')->assertSee('বিকাশ / নগদে ফি দিন');

        $this->post('/portal/pay', ['method' => 'nagad', 'amount' => 1000, 'sender' => '01811000000', 'trx_id' => 'ABC12345'])->assertSessionHasErrors('method');
        $this->post('/portal/pay', ['method' => 'bkash', 'amount' => $due + 1, 'sender' => '01811000000', 'trx_id' => 'ABC12345'])->assertSessionHasErrors('amount');
        $this->post('/portal/pay', ['method' => 'bkash', 'amount' => 1000, 'sender' => '01811000000', 'trx_id' => 'abc12345'])->assertSessionHasNoErrors();
        $this->post('/portal/pay', ['method' => 'bkash', 'amount' => 500, 'sender' => '01811000000', 'trx_id' => 'ABC12345'])->assertSessionHasErrors('trx_id');
        $this->post('/portal/pay', ['method' => 'bkash', 'amount' => 500, 'sender' => '01811000000', 'trx_id' => 'WRONG999'])->assertSessionHasNoErrors();
        auth('student')->logout();

        [$good, $bad] = [OnlinePayment::where('trx_id', 'ABC12345')->firstOrFail(), OnlinePayment::where('trx_id', 'WRONG999')->firstOrFail()];
        $this->actingAs($this->admin, 'web');
        $this->get('/admin')->assertSee('অনলাইন পেমেন্ট যাচাই বাকি');
        $this->get('/admin/online-payments')->assertOk()->assertSee('ABC12345');
        $before = $s->payments()->count();
        $this->post("/admin/online-payments/{$good->id}/approve")->assertSessionHas('ok');
        $this->post("/admin/online-payments/{$good->id}/approve")->assertNotFound(); // never twice
        $this->post("/admin/online-payments/{$bad->id}/reject", [])->assertSessionHasErrors('note');
        $this->post("/admin/online-payments/{$bad->id}/reject", ['note' => 'টাকা আসেনি'])->assertSessionHas('ok');

        $this->assertSame($before + 1, $s->payments()->count());
        $this->assertSame('বিকাশ', $good->fresh()->payment->method);
        $this->assertSame('rejected', $bad->fresh()->status);
        $this->assertSame(1, MessageLog::where('event', 'online_approved')->count());
        $this->assertSame(1, MessageLog::where('event', 'online_rejected')->count());
        auth('web')->logout();
        $this->actingAs($s, 'student')->get('/portal/pay')->assertSee('গৃহীত')->assertSee('টাকা আসেনি');
    }

    public function test_email_settings_test_send_and_notice_emails(): void
    {
        Mail::fake();
        $this->actingAs($this->admin, 'web');
        $this->post('/admin/messages/test', ['to' => 'me@example.com'])->assertSessionHasErrors('to'); // email off
        $this->put('/admin/settings', ['name' => 'মা কম্পিউটার', 'mail_enabled' => '1', 'mail_host' => 'smtp.gmail.com', 'mail_port' => '587',
            'mail_username' => 'centre@example.com', 'mail_password' => 'app-pass', 'notify_notice' => '1'])->assertSessionHasNoErrors();
        $this->post('/admin/messages/test', ['to' => 'me@example.com'])->assertSessionHas('ok');

        $this->post('/admin/notices', ['title' => 'আগামীকাল ক্লাস বন্ধ', 'tag' => 'exam', 'published_on' => now()->toDateString(), 'audience' => 'all', 'body' => 'ঝড়ের কারণে', 'send_email' => '1'])
            ->assertSessionHas('ok', fn ($m) => str_contains($m, 'ইমেইল করা হয়েছে'));
        $log = MessageLog::where('event', 'notice')->where('student_id', $this->student->id)->firstOrFail();
        $this->assertStringContainsString('ঝড়ের কারণে', $log->message);
        $this->get('/admin/messages')->assertOk()->assertSee('tanvir@example.com');

        // Turning one email type off stops it
        $this->put('/admin/settings', ['name' => 'মা কম্পিউটার', 'notify_payment' => '0'])->assertSessionHasNoErrors();
        $this->post("/admin/students/{$this->student->id}/payments", ['amount' => 100, 'paid_on' => now()->toDateString(), 'item' => 'x', 'method' => Payment::METHODS[0]]);
        $this->assertSame(0, MessageLog::where('event', 'payment')->count());
    }

    public function test_assignments_from_handing_in_to_results(): void
    {
        Storage::fake('local');
        $s = $this->student;
        $dep = $s->batch->department_id;

        $this->actingAs($this->admin, 'web');
        $this->post('/admin/assignments', ['department_id' => $dep, 'title' => 'লোগো ডিজাইন', 'instructions' => 'একটি লোগো বানান', 'due_on' => now()->addWeek()->toDateString(),
            'max_marks' => '১০', 'file' => UploadedFile::fake()->create('brief.pdf', 50, 'application/pdf')])->assertSessionHasNoErrors();
        $a = Assignment::firstOrFail();
        $this->assertSame(10, $a->max_marks);
        auth('web')->logout();

        $this->actingAs($s, 'student');
        $this->get('/portal')->assertSee('লোগো ডিজাইন');
        $this->get('/portal/study')->assertOk()->assertSee('জমা বাকি');
        $this->get("/portal/assignments/{$a->id}")->assertOk()->assertSee('একটি লোগো বানান');
        $this->get("/portal/assignments/{$a->id}/file")->assertOk()->assertDownload('brief.pdf');
        $this->post("/portal/assignments/{$a->id}", [])->assertSessionHasErrors('body');
        $this->post("/portal/assignments/{$a->id}", ['body' => 'আমার লোগো', 'file' => UploadedFile::fake()->create('logo.png', 100, 'image/png')])->assertSessionHasNoErrors();
        $sub = $a->submissions()->firstOrFail();
        $this->get("/portal/submissions/{$sub->id}/file")->assertOk();
        auth('student')->logout();

        $this->actingAs($this->admin, 'web');
        $this->get("/admin/assignments/{$a->id}")->assertOk()->assertSee('আমার লোগো');
        $this->get("/admin/submissions/{$sub->id}/file")->assertOk();
        $this->put("/admin/submissions/{$sub->id}", ['marks' => 11])->assertSessionHasErrors('marks');
        $this->put("/admin/submissions/{$sub->id}", ['marks' => '৮', 'feedback' => 'চমৎকার'])->assertSessionHasNoErrors();
        $this->post("/admin/assignments/{$a->id}/results")->assertSessionHas('ok');
        $this->assertSame(8, \App\Models\Assessment::where('student_id', $s->id)->where('title', 'অ্যাসাইনমেন্ট: লোগো ডিজাইন')->value('score'));
        auth('web')->logout();

        $this->actingAs($s, 'student');
        $this->get('/portal/study')->assertSee('নম্বর ৮/১০');
        $this->post("/portal/assignments/{$a->id}", ['body' => 'বদলাতে চাই'])->assertSessionHasErrors('body'); // marked: locked

        // Another student's file is off limits
        $other = Student::where('id', '!=', $s->id)->first();
        $this->actingAs($other, 'student')->get("/portal/submissions/{$sub->id}/file")->assertNotFound();
    }

    public function test_quiz_with_time_limit_and_one_attempt(): void
    {
        $s = $this->student;
        $this->actingAs($this->admin, 'web');
        $this->post('/admin/quizzes', ['department_id' => $s->batch->department_id, 'title' => 'ফটোশপ কুইজ', 'time_limit' => 10, 'show_answers' => '1'])->assertSessionHasNoErrors();
        $quiz = Quiz::firstOrFail();
        $this->put("/admin/quizzes/{$quiz->id}", ['title' => 'ফটোশপ কুইজ', 'published' => '1'])->assertSessionHasErrors('published'); // no questions yet
        $this->post("/admin/quizzes/{$quiz->id}/questions", ['question' => 'লেয়ার কী?', 'options' => ['স্তর', 'রং', '', ''], 'correct' => 0])->assertSessionHasNoErrors();
        $this->post("/admin/quizzes/{$quiz->id}/questions", ['question' => 'Ctrl+Z?', 'options' => ['সেভ', 'আনডু', 'কপি', ''], 'correct' => 1])->assertSessionHasNoErrors();
        $this->post("/admin/quizzes/{$quiz->id}/questions", ['question' => 'খালি', 'options' => ['এক', '', '', ''], 'correct' => 0])->assertSessionHasErrors('options');
        $this->put("/admin/quizzes/{$quiz->id}", ['title' => 'ফটোশপ কুইজ', 'time_limit' => 10, 'show_answers' => '1', 'published' => '1'])->assertSessionHasNoErrors();
        auth('web')->logout();

        [$q1, $q2] = $quiz->questions()->get()->all();
        $this->actingAs($s, 'student');
        $this->get('/portal/study')->assertSee('ফটোশপ কুইজ')->assertSee('শুরু করুন');
        $this->get("/portal/quizzes/{$quiz->id}")->assertOk()->assertSee('লেয়ার কী?')->assertSee('বাকি সময়');
        $this->post("/portal/quizzes/{$quiz->id}", ['answers' => [$q1->id => 0, $q2->id => 2]])->assertRedirect();
        $this->post("/portal/quizzes/{$quiz->id}", ['answers' => [$q1->id => 0, $q2->id => 1]]); // second try ignored
        $attempt = $quiz->attempts()->firstOrFail();
        $this->assertSame([1, 2], [$attempt->score, $attempt->total]);
        $this->get("/portal/quizzes/{$quiz->id}")->assertSee('নম্বর ১/২');
        auth('student')->logout();

        $this->actingAs($this->admin, 'web');
        $this->post("/admin/quizzes/{$quiz->id}/questions", ['question' => 'নতুন', 'options' => ['ক', 'খ', '', ''], 'correct' => 0])->assertSessionHasErrors('question');
        $this->post("/admin/quizzes/{$quiz->id}/results")->assertSessionHas('ok');
        $this->assertSame(1, \App\Models\Assessment::where('student_id', $s->id)->where('title', 'কুইজ: ফটোশপ কুইজ')->value('score'));
        $this->post("/admin/quizzes/{$quiz->id}/reset/{$s->id}")->assertSessionHas('ok');
        $this->assertSame(0, $quiz->attempts()->count());

        // Unpublished or other-course quizzes are hidden
        $quiz->refresh()->update(['published' => false]);
        $this->actingAs($s, 'student')->get("/portal/quizzes/{$quiz->id}")->assertNotFound();
    }

    public function test_calendar_profile_and_dashboard_in_the_portal(): void
    {
        Storage::fake('public');
        $s = $this->student;
        $this->actingAs($this->admin, 'web');
        $this->post('/admin/calendar', ['title' => 'বিজয় দিবসের ছুটি', 'type' => 'holiday', 'starts_on' => now()->addDays(3)->toDateString(), 'audience' => 'all'])->assertSessionHasNoErrors();
        $this->post('/admin/calendar', ['title' => 'অন্য ব্যাচের পরীক্ষা', 'type' => 'exam', 'starts_on' => now()->addDays(4)->toDateString(), 'audience' => 'batch',
            'batch_id' => Batch::where('id', '!=', $s->batch_id)->value('id') ?? Batch::create(['department_id' => $s->batch->department_id, 'code' => 'Z-1', 'name' => 'জেড'])->id])->assertSessionHasNoErrors();
        $this->post('/admin/calendar', ['title' => 'ভুল', 'type' => 'holiday', 'starts_on' => now()->toDateString(), 'ends_on' => now()->subDay()->toDateString(), 'audience' => 'all'])->assertSessionHasErrors('ends_on');
        $this->get('/admin/calendar?month='.now()->addDays(3)->format('Y-m'))->assertSee('বিজয় দিবসের ছুটি');
        $this->assertSame(2, Event::count());
        auth('web')->logout();

        $this->actingAs($s, 'student');
        $this->get('/portal')->assertOk()->assertSee('বিজয় দিবসের ছুটি')->assertDontSee('অন্য ব্যাচের পরীক্ষা');
        $this->get('/portal/calendar?month='.now()->addDays(3)->format('Y-m'))->assertOk()->assertSee('বিজয় দিবসের ছুটি')->assertDontSee('অন্য ব্যাচের পরীক্ষা');

        $this->put('/portal/profile', ['phone' => '01999-888777', 'email' => 'new@example.com', 'present_village' => 'নতুন বাড়ি', 'present_upazila' => 'মীরসরাই', 'present_district' => 'চট্টগ্রাম',
            'photo' => UploadedFile::fake()->image('me.jpg')])->assertSessionHasNoErrors();
        $s->refresh();
        $this->assertSame(['01999-888777', 'new@example.com', 'নতুন বাড়ি'], [$s->phone, $s->email, $s->present_village]);
        Storage::disk('public')->assertExists($s->photo);
        $this->assertSame($s->id, ActivityLog::where('subject_type', 'Student')->where('action', 'updated')->latest('id')->value('student_id'));
        $this->put('/portal/profile', ['phone' => 'abc', 'present_village' => 'x', 'present_upazila' => 'y', 'present_district' => 'z'])->assertSessionHasErrors('phone');
    }

    public function test_english_website_sitemap_and_sharing_tags(): void
    {
        $gd = \App\Models\Department::where('code', 'GD')->first();
        $this->actingAs($this->admin, 'web');
        $this->put("/admin/departments/{$gd->id}", ['code' => 'GD', 'name' => $gd->name, 'course_fee' => $gd->course_fee, 'admission_fee' => $gd->admission_fee,
            'en' => ['name' => 'Graphic Design', 'blurb' => 'Photoshop and Illustrator from scratch', 'topics' => 'Photoshop, Illustrator']])->assertSessionHasNoErrors();
        $this->put('/admin/settings', ['name' => 'মা কম্পিউটার এন্ড ট্রেনিং সেন্টার', 'about_intro_en' => 'We teach practical computer skills.'])->assertSessionHasNoErrors();
        $this->post('/admin/settings/faqs', ['question' => 'ফি কত?', 'answer' => 'বিভাগ অনুযায়ী।', 'en' => ['question' => 'How much are the fees?', 'answer' => 'It depends on the department.']]);
        auth('web')->logout();

        // Bangla by default, with sharing tags and a link to English
        $this->get('/')->assertOk()->assertSee('<html lang="bn">', false)->assertSee('og:title', false)->assertSee('application/ld+json', false)
            ->assertSee('English')->assertSee('কম্পিউটার দক্ষতা')->assertSee('গ্রাফিক ডিজাইন');

        // ?lang=en switches and is remembered
        $this->get('/?lang=en')->assertOk()->assertSee('<html lang="en">', false)->assertSee('from day one.')->assertSee('Graphic Design')
            ->assertSee('Photoshop and Illustrator from scratch')->assertSee('বাংলা')->assertDontSee('কম্পিউটার দক্ষতা');
        $this->get('/departments')->assertSee('Enrol in this department')->assertSee('৳12,000', false);
        $this->get('/about')->assertSee('We teach practical computer skills.');
        $this->get('/faq')->assertSee('How much are the fees?');
        foreach (['/admission', '/contact', '/notices', '/gallery', '/instructors', '/verify', '/privacy', '/login', '/forgot-password', '/staff/forgot-password'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->post('/contact', [])->assertSessionHasErrors('name');
        $this->post('/contact', ['name' => 'Rima', 'phone' => '01711000000', 'message' => 'Hello'])->assertSessionHas('ok', fn ($m) => str_starts_with($m, 'Thank you'));

        // Admin and portal always stay in Bangla
        $this->actingAs($this->admin, 'web')->get('/admin')->assertSee('ড্যাশবোর্ড');
        auth('web')->logout();
        $this->get('/?lang=bn')->assertSee('<html lang="bn">', false);

        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->assertSee('<loc>'.route('departments').'</loc>', false)->assertSee('hreflang="en"', false);
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap: '.route('sitemap'));
    }

    public function test_staff_forgot_password_by_email(): void
    {
        Notification::fake();
        $this->get('/staff/forgot-password')->assertOk()->assertSee('ইমেইল এখনো চালু হয়নি');
        $this->post('/staff/forgot-password', ['email' => 'a@example.com'])->assertSessionHasErrors('email');

        $this->enableEmail();
        $this->post('/staff/forgot-password', ['email' => 'nobody@example.com'])->assertSessionHas('ok'); // same answer
        $this->post('/staff/forgot-password', ['email' => 'a@example.com'])->assertSessionHas('ok');

        $token = null;
        Notification::assertSentTo($this->admin, ResetPassword::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });
        $this->get("/staff/reset-password/{$token}?email=a@example.com")->assertOk();
        $this->post('/staff/reset-password', ['token' => 'wrong', 'email' => 'a@example.com', 'password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123'])->assertSessionHasErrors('email');
        $this->post('/staff/reset-password', ['token' => $token, 'email' => 'a@example.com', 'password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123'])->assertRedirect('/login');
        $this->post('/admin/login', ['email' => 'a@example.com', 'password' => 'new-pass-123'])->assertRedirect('/admin');
        $this->assertSame('login', ActivityLog::latest('id')->value('action'));
    }
}
