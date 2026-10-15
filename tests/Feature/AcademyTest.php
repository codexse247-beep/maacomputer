<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AcademyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([DatabaseSeeder::class, DemoSeeder::class]);
        $this->admin = User::create(['name' => 'অ্যাডমিন', 'email' => 'a@example.com', 'password' => 'secret-pass']);
    }

    public function test_public_pages_load(): void
    {
        $this->get('/')->assertOk()->assertSee('গ্রাফিক ডিজাইন')->assertSee('ভর্তি চলছে');
        foreach (['/notices', '/notices?tag=admission', '/departments', '/admission', '/about', '/instructors', '/gallery', '/gallery?c=lab', '/gallery?c=video',
            '/faq', '/verify', '/privacy', '/contact', '/login', '/forgot-password'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/departments')->assertSee('কী শিখবেন')->assertSee('৳১২,০০০');
        $this->get('/instructors')->assertSee('মো. রফিক হোসেন');
        $this->get('/no-such-page')->assertNotFound()->assertSee('পেজটি খুঁজে পাওয়া যায়নি');
        $this->get('/portal/login')->assertRedirect('/login');
        $this->get('/admin/login')->assertRedirect('/login');
    }

    public function test_website_forms_reach_the_admin(): void
    {
        $gd = \App\Models\Department::where('code', 'GD')->first();
        $this->post('/admission', ['name' => 'রিমা', 'father_name' => 'করিম', 'phone' => '০১৭১১-০০০০০০', 'department_id' => $gd->id, 'preferred_time' => 'সকাল'])
            ->assertRedirect('/admission')->assertSessionHas('ok');
        $this->post('/admission', [])->assertSessionHasErrors(['name', 'phone', 'department_id']);
        $this->post('/contact', ['name' => 'রিমা', 'phone' => '01711000000', 'message' => 'ফি কত?'])->assertSessionHas('ok');
        $this->post('/forgot-password', ['student_code' => 'mct-2607-004', 'phone' => '01700-000104'])->assertSessionHas('ok');
        $this->post('/forgot-password', ['student_code' => 'NOPE-1', 'phone' => '01700000000'])->assertSessionHas('ok');

        $this->assertSame(3, \App\Models\Enquiry::count());
        $this->assertSame('01711-000000', \App\Models\Enquiry::where('type', 'admission')->value('phone'));
        $this->actingAs($this->admin)->get('/admin/enquiries')->assertOk()->assertSee('রিমা')->assertSee('মোবাইল নম্বর রেকর্ডের সাথে মিলেছে');
        $this->actingAs($this->admin)->get('/admin')->assertSee('নতুন অনলাইন আবেদন ও মেসেজ');
        $e = \App\Models\Enquiry::first();
        $this->actingAs($this->admin)->patch("/admin/enquiries/{$e->id}", ['status' => 'done'])->assertRedirect();
        $this->assertSame('done', $e->fresh()->status);
    }

    public function test_certificate_issue_and_public_verification(): void
    {
        $student = Student::where('name_en', 'Tanvir Ahmed')->first();
        $this->actingAs($this->admin)->post("/admin/students/{$student->id}/certificate")->assertSessionHas('ok');
        $student->refresh();
        $this->assertMatchesRegularExpression('/^MCT-C-\d{4}-0001$/', $student->certificate_no);
        $this->assertSame('completed', $student->status);

        $this->get('/verify?certificate_no='.strtolower($student->certificate_no).'&dob='.$student->dob->format('Y-m-d'))->assertSee('সনদটি সঠিক')->assertSee('তানভীর আহমেদ');
        $this->get('/verify?certificate_no='.$student->certificate_no.'&dob=2000-01-01')->assertSee('কোনো সনদ পাওয়া যায়নি');
    }

    public function test_guests_are_redirected_to_the_right_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/portal')->assertRedirect('/login');
    }

    public function test_staff_cannot_open_settings(): void
    {
        $staff = User::create(['name' => 'স্টাফ', 'email' => 's@example.com', 'role' => 'staff', 'password' => 'secret-pass']);
        $this->actingAs($staff)->get('/admin/settings')->assertForbidden();
        $this->actingAs($staff)->get('/admin')->assertOk()->assertDontSee('/admin/settings');
        $this->actingAs($staff)->get('/admin/gallery')->assertForbidden();
    }

    public function test_gallery_images_and_youtube_videos(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin);

        $this->post('/admin/gallery', ['type' => 'image', 'category' => 'lab', 'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')]])
            ->assertSessionHasNoErrors();
        $this->post('/admin/gallery', ['type' => 'video', 'category' => 'event', 'caption' => 'সনদ বিতরণ', 'video_url' => 'https://youtube.com/shorts/dQw4w9WgXcQ?si=x'])
            ->assertSessionHasNoErrors();
        $this->post('/admin/gallery', ['type' => 'video', 'category' => 'event', 'video_url' => 'https://example.com/video'])->assertSessionHasErrors('video_url');
        $this->post('/admin/gallery', ['type' => 'image', 'category' => 'lab'])->assertSessionHasErrors('photos');

        $this->assertSame(3, \App\Models\GalleryPhoto::count());
        $this->get('/admin/gallery')->assertOk()->assertSee('সনদ বিতরণ');
        // The video opens in the Lity lightbox via its standard watch link.
        $this->get('/gallery?c=video')->assertOk()->assertSee('https://www.youtube.com/watch?v=dQw4w9WgXcQ', false)->assertSee('data-lity', false)->assertDontSee('/storage/gallery/');
        $this->get('/gallery')->assertOk()->assertSee('/storage/gallery/', false);

        $photo = \App\Models\GalleryPhoto::where('type', 'image')->first();
        $this->delete("/admin/gallery/{$photo->id}")->assertRedirect();
        Storage::disk('public')->assertMissing($photo->path);
    }

    public function test_settings_attendance_results_reports(): void
    {
        $this->actingAs($this->admin);
        $batch = Batch::where('code', 'GD-2607')->first();
        $ids = $batch->students()->pluck('id')->all();
        $gd = \App\Models\Department::where('code', 'GD')->first();

        // Settings: centre info and fee table (Bangla digits)
        $this->put('/admin/settings', ['name' => 'মা কম্পিউটার এন্ড ট্রেনিং সেন্টার', 'phone' => '01811-223344', 'fees' => [$gd->id => ['course_fee' => '১৩,০০০', 'admission_fee' => '৫০০']]])
            ->assertSessionHasNoErrors();
        $this->assertSame(13000, $gd->fresh()->course_fee);
        $this->get('/contact')->assertSee('01811-223344');
        $this->post('/admin/settings/faqs', ['question' => 'নতুন প্রশ্ন?', 'answer' => 'উত্তর।'])->assertSessionHasNoErrors();
        $this->get('/faq')->assertSee('নতুন প্রশ্ন?');
        $this->post('/admin/settings/users', ['name' => 'হিসাবরক্ষক', 'email' => 'acc@example.com', 'role' => 'accounts'])->assertSessionHas('ok');

        // Attendance with present / absent / leave
        $this->post("/admin/batches/{$batch->id}/attendance", ['date' => now()->toDateString(), 'status' => [$ids[0] => 'present', $ids[1] => 'leave', $ids[2] => 'absent']])->assertRedirect();
        $this->assertSame('leave', \App\Models\Attendance::where('student_id', $ids[1])->whereDate('date', now())->value('status'));
        $this->get("/admin/attendance?batch={$batch->id}")->assertOk()->assertSee('নেওয়া হয়েছে')
            ->assertViewHas('avg', fn ($avg) => $avg > 50); // demo data is ~88% present
        $this->get("/admin/batches/{$batch->id}")->assertViewHas('avgAttendance', fn ($avg) => $avg > 50);
        $this->get("/admin/batches/{$batch->id}/attendance/history")->assertOk();

        // Bulk results: draft is hidden from the portal, publish shows it
        $this->post("/admin/batches/{$batch->id}/results", ['title' => 'চূড়ান্ত প্রজেক্ট', 'max_score' => 100, 'marks' => [$ids[0] => '৮৫', $ids[1] => '30'], 'action' => 'draft'])->assertRedirect();
        $this->assertFalse((bool) \App\Models\Assessment::where('title', 'চূড়ান্ত প্রজেক্ট')->value('published'));
        $this->post("/admin/batches/{$batch->id}/results", ['title' => 'চূড়ান্ত প্রজেক্ট', 'max_score' => 100, 'marks' => [$ids[0] => '150'], 'action' => 'publish'])->assertSessionHasErrors('marks');
        $this->post("/admin/batches/{$batch->id}/results", ['title' => 'চূড়ান্ত প্রজেক্ট', 'max_score' => 100, 'marks' => [$ids[0] => '৮৫', $ids[1] => '30'], 'action' => 'publish'])->assertRedirect();
        $this->assertSame('failed', \App\Models\Assessment::where('student_id', $ids[1])->where('title', 'চূড়ান্ত প্রজেক্ট')->value('status'));
        $this->get("/admin/results?batch={$batch->id}&exam=".urlencode('চূড়ান্ত প্রজেক্ট'))->assertOk()->assertSee('A+');

        // Teachers, notices for one department
        $this->post('/admin/teachers', ['name' => 'নতুন শিক্ষক', 'department_id' => $gd->id, 'active' => '1', 'show_on_site' => '1'])->assertSessionHasNoErrors();
        $this->post('/admin/notices', ['title' => 'শুধু GD বিভাগের নোটিশ', 'tag' => 'exam', 'published_on' => now()->toDateString(), 'audience' => 'department', 'department_id' => $gd->id, 'is_public' => '1'])->assertSessionHasNoErrors();
        $this->assertFalse((bool) \App\Models\Notice::where('title', 'শুধু GD বিভাগের নোটিশ')->value('is_public'));

        // Every report in both formats
        foreach (array_keys(\App\Http\Controllers\Admin\ReportController::REPORTS) as $report) {
            $this->get("/admin/reports/$report?period=this_year")->assertOk();
            $this->get("/admin/reports/$report?format=csv")->assertOk()->assertDownload();
        }
        $this->get("/admin/reports/results?batch={$batch->id}&exam=".urlencode('চূড়ান্ত প্রজেক্ট'))->assertOk()->assertSee('৮৫');
    }

    public function test_admin_login_and_every_admin_page(): void
    {
        $this->post('/admin/login', ['email' => 'a@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/admin/login', ['email' => 'a@example.com', 'password' => 'secret-pass'])->assertRedirect('/admin');

        $batch = Batch::where('code', 'GD-2607')->first();
        $student = $batch->students()->first();
        $payment = $student->payments()->first();

        foreach ([
            '/admin', '/admin/departments', '/admin/departments/create', "/admin/departments/{$batch->department_id}",
            "/admin/departments/{$batch->department_id}/edit", "/admin/departments/{$batch->department_id}/batches/create",
            "/admin/batches/{$batch->id}", "/admin/batches/{$batch->id}?fee=partial&status=active&q=তানভীর", "/admin/batches/{$batch->id}/edit",
            "/admin/batches/{$batch->id}/id-cards", '/admin/attendance', "/admin/attendance?batch={$batch->id}&date=".now()->subDays(3)->toDateString(),
            '/admin/students', '/admin/students?q=MCT', '/admin/students/create', "/admin/students/create?batch={$batch->id}",
            "/admin/students/{$student->id}", "/admin/students/{$student->id}/edit", "/admin/students/{$student->id}/id-card",
            "/admin/students/{$student->id}/form", '/admin/payments', "/admin/payments/{$payment->id}/receipt", '/admin/notices',
            '/admin/payments?fee=due&department='.$batch->department_id, '/admin/batches', '/admin/batches?status=running', '/admin/results',
            '/admin/teachers', '/admin/teachers?edit=1', '/admin/enquiries', '/admin/enquiries?status=all&type=contact', '/admin/reports',
            '/admin/reports?period=last_month&department='.$batch->department_id, '/admin/settings', '/admin/notices?edit=1', '/admin/gallery', '/admin/course-videos',
        ] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }

        $this->actingAs($this->admin)->get("/admin/batches/{$batch->id}/export")->assertOk()->assertDownload();
    }

    public function test_full_admission_flow_with_bangla_digits_and_uploads(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $batch = Batch::where('code', 'GD-2701')->first();

        $response = $this->actingAs($this->admin)->post('/admin/students', [
            'name_bn' => 'সুমাইয়া আক্তার', 'name_en' => 'Sumaiya Akter', 'father_name' => 'মো. সেলিম', 'mother_name' => 'রহিমা বেগম',
            'gender' => 'মহিলা', 'guardian_phone' => '০১৮১১-১২৩৪৫৬', 'phone' => '01711-123456',
            'present_village' => 'বাসা ১২', 'present_upazila' => 'মীরসরাই', 'present_district' => 'চট্টগ্রাম', 'same_address' => '1',
            'edu' => [['inst' => 'মীরসরাই স্কুল', 'group' => 'বিজ্ঞান', 'board' => 'চট্টগ্রাম', 'year' => '2022', 'result' => '5.00'], [], []],
            'batch_id' => $batch->id, 'admission_date' => now()->toDateString(), 'admission_type' => 'নতুন ভর্তি',
            'discount' => '৫০০', 'installments' => 3, 'first_payment' => '৩,০০০', 'method' => 'বিকাশ',
            'photo' => UploadedFile::fake()->image('me.jpg'), 'doc_form' => UploadedFile::fake()->create('form.pdf', 100, 'application/pdf'),
        ]);

        $student = Student::where('name_en', 'Sumaiya Akter')->firstOrFail();
        $response->assertRedirect("/admin/students/{$student->id}")->assertSessionHas('ok');

        $this->assertSame('MCT-2701-001', $student->student_code);
        $this->assertSame('01811-123456', $student->guardian_phone);
        $this->assertSame(500, $student->discount);
        $this->assertSame('বাসা ১২', $student->perm_village);
        $this->assertSame(12000, $student->totalPayable());
        $this->assertSame(3000, $student->totalPaid());
        $this->assertSame('partial', $student->feeState());
        $this->assertCount(2, $student->documents);
        Storage::disk('public')->assertExists($student->photo);

        // The generated portal password works (checked on a fresh, admin-free app below)
        preg_match('/পাসওয়ার্ড <b>(\d+)<\/b>/u', session('ok'), $m);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($m[1], $student->password));
    }

    public function test_course_videos_only_for_enrolled_students(): void
    {
        Storage::fake('local');
        $student = Student::where('name_en', 'Tanvir Ahmed')->firstOrFail();
        $own = $student->batch->department_id;
        $otherDept = \App\Models\Department::where('id', '!=', $own)->value('id');

        $this->actingAs($this->admin);
        $this->post('/admin/course-videos', ['department_id' => $own, 'class_no' => '১', 'title' => 'ফটোশপ পরিচিতি', 'type' => 'upload',
            'video' => UploadedFile::fake()->create('class1.mp4', 2048, 'video/mp4')])->assertSessionHasNoErrors();
        $this->post('/admin/course-videos', ['department_id' => $own, 'class_no' => 2, 'title' => 'লেয়ার', 'type' => 'url', 'url' => 'https://youtu.be/dQw4w9WgXcQ'])->assertSessionHasNoErrors();
        $this->post('/admin/course-videos', ['department_id' => $otherDept, 'title' => 'অন্য কোর্সের ভিডিও', 'type' => 'url', 'url' => 'https://drive.google.com/file/d/abc123/view?usp=sharing'])->assertSessionHasNoErrors();
        $this->post('/admin/course-videos', ['department_id' => $own, 'title' => 'ভুল ফাইল', 'type' => 'upload', 'video' => UploadedFile::fake()->create('a.exe', 10, 'application/x-msdownload')])->assertSessionHasErrors('video');
        $this->post('/admin/course-videos', ['department_id' => $own, 'title' => 'লিংক নেই', 'type' => 'url'])->assertSessionHasErrors('url');

        $upload = \App\Models\CourseVideo::where('type', 'upload')->first();
        $this->assertSame(1, $upload->class_no);
        Storage::disk('local')->assertExists($upload->path);
        $this->get("/admin/course-videos?course=$own")->assertOk()->assertSee('ফটোশপ পরিচিতি');
        $this->get("/admin/course-videos/{$upload->id}/file")->assertOk();
        $this->get("/admin/course-videos?course=$otherDept")->assertSee('https://drive.google.com/file/d/abc123/preview', false);
        auth()->guard('web')->logout();

        // Guests and other courses' students get nothing; the file never sits in the public folder.
        $this->get("/portal/videos/{$upload->id}/file")->assertRedirect('/login');
        $otherBatch = Batch::create(['department_id' => $otherDept, 'code' => 'X-1', 'name' => 'অন্য ব্যাচ']);
        $outsider = $student->replicate()->fill(['batch_id' => $otherBatch->id, 'student_code' => 'X-1-001', 'status' => 'active']);
        $outsider->save();
        $this->actingAs($outsider, 'student')->get('/portal/videos')->assertOk()->assertDontSee('ফটোশপ পরিচিতি')->assertSee('অন্য কোর্সের ভিডিও');
        $this->get("/portal/videos/{$upload->id}/file")->assertNotFound();

        $this->actingAs($student, 'student')->get('/portal/videos')->assertOk()->assertSee('ফটোশপ পরিচিতি')->assertSee('ক্লাস ১')
            ->assertSee('https://www.youtube.com/watch?v=dQw4w9WgXcQ', false)->assertDontSee('অন্য কোর্সের ভিডিও');
        $this->get("/portal/videos/{$upload->id}/file")->assertOk();

        // On the live server Caddy sends the file; PHP only answers with the header.
        config(['app.video_sendfile' => true]);
        $this->get("/portal/videos/{$upload->id}/file")->assertOk()->assertHeader('X-Accel-Redirect', '/'.$upload->path);

        $student->update(['status' => 'dropped']);
        $this->get('/portal/videos')->assertOk()->assertDontSee('ফটোশপ পরিচিতি');
        $this->get("/portal/videos/{$upload->id}/file")->assertNotFound();

        $this->actingAs($this->admin, 'web')->delete("/admin/course-videos/{$upload->id}")->assertRedirect();
        $this->assertNull(\App\Models\CourseVideo::find($upload->id));
        Storage::disk('local')->assertMissing($upload->path);
    }

    public function test_course_notes_locks_progress_and_editing(): void
    {
        Storage::fake('local');
        $student = Student::where('name_en', 'Tanvir Ahmed')->firstOrFail();
        $dep = $student->batch->department_id;
        $otherBatch = Batch::create(['department_id' => $dep, 'code' => 'Y-1', 'name' => 'আরেক ব্যাচ']);
        $this->actingAs($this->admin, 'web');

        // Class with a note file; one for another batch; one released later; one for fully-paid students only.
        $this->post('/admin/course-videos', ['department_id' => $dep, 'class_no' => 1, 'title' => 'ক্লাস এক', 'description' => 'বাড়ির কাজ: লোগো', 'type' => 'url',
            'url' => 'https://youtu.be/dQw4w9WgXcQ', 'files' => [UploadedFile::fake()->create('note.pdf', 100, 'application/pdf')]])->assertSessionHasNoErrors();
        $this->post('/admin/course-videos', ['department_id' => $dep, 'batch_id' => $otherBatch->id, 'title' => 'অন্য ব্যাচের ক্লাস', 'type' => 'url', 'url' => 'https://youtu.be/aaaaaaaaaaa'])->assertSessionHasNoErrors();
        $this->post('/admin/course-videos', ['department_id' => $dep, 'class_no' => 3, 'title' => 'পরের সপ্তাহের ক্লাস', 'type' => 'url', 'url' => 'https://youtu.be/bbbbbbbbbbb',
            'release_on' => now()->addWeek()->toDateString(), 'files' => [UploadedFile::fake()->create('later.pdf', 10, 'application/pdf')]])->assertSessionHasNoErrors();
        $this->post('/admin/course-videos', ['department_id' => $dep, 'class_no' => 4, 'title' => 'ফি পরিশোধিতদের ক্লাস', 'type' => 'url', 'url' => 'https://youtu.be/ccccccccccc', 'paid_only' => '1'])->assertSessionHasNoErrors();
        $this->post('/admin/course-videos', ['department_id' => $dep, 'title' => 'খারাপ ফাইল', 'type' => 'url', 'url' => 'https://youtu.be/ddddddddddd',
            'files' => [UploadedFile::fake()->create('virus.exe', 10)]])->assertSessionHasErrors('files.0');
        $this->post('/admin/course-videos', ['department_id' => $dep, 'batch_id' => Batch::where('department_id', '!=', $dep)->value('id') ?? 999999, 'title' => 'ভুল ব্যাচ', 'type' => 'url', 'url' => 'https://youtu.be/eeeeeeeeeee'])
            ->assertSessionHasErrors('batch_id');

        $first = \App\Models\CourseVideo::where('title', 'ক্লাস এক')->first();
        $later = \App\Models\CourseVideo::where('title', 'পরের সপ্তাহের ক্লাস')->first();
        $paidOnly = \App\Models\CourseVideo::where('title', 'ফি পরিশোধিতদের ক্লাস')->first();
        $note = $first->files()->first();
        $this->assertSame('note.pdf', $note->name);
        $this->get("/admin/course-files/{$note->id}")->assertOk()->assertDownload('note.pdf');
        $this->get("/admin/course-videos?edit={$first->id}")->assertOk()->assertSee('ক্লাস সম্পাদনা')->assertSee('note.pdf');

        // Edit: rename and move to class 2, switch to an uploaded video, keep the note.
        $this->put("/admin/course-videos/{$first->id}", ['department_id' => $dep, 'class_no' => '২', 'title' => 'ক্লাস দুই', 'type' => 'upload',
            'video' => UploadedFile::fake()->create('c.mp4', 500, 'video/mp4')])->assertSessionHasNoErrors();
        $first->refresh();
        $this->assertSame([2, 'ক্লাস দুই', 'upload', null], [$first->class_no, $first->title, $first->type, $first->url]);
        Storage::disk('local')->assertExists($first->path);
        $this->put("/admin/course-videos/{$first->id}", ['department_id' => $dep, 'class_no' => 2, 'title' => 'ক্লাস দুই', 'type' => 'upload'])->assertSessionHasNoErrors(); // keeps the old file
        $this->assertNotNull($first->fresh()->path);
        auth()->guard('web')->logout();

        // Student (has fees due in the demo data): sees own batch's classes, locked ones show why.
        $this->assertGreaterThan(0, $student->totalDue());
        $this->actingAs($student, 'student')->get('/portal/videos')->assertOk()
            ->assertSee('ক্লাস দুই')->assertSee('বাড়ির কাজ: লোগো')->assertSee('note.pdf')
            ->assertDontSee('অন্য ব্যাচের ক্লাস')
            ->assertSee('পরের সপ্তাহের ক্লাস')->assertSee('থেকে দেখা যাবে')->assertDontSee('later.pdf')
            ->assertSee('বকেয়া ফি পরিশোধ করলে দেখা যাবে')
            ->assertSee('id="doneCount">০</span>/১ ক্লাস দেখা হয়েছে', false);
        $this->get("/portal/course-files/{$note->id}")->assertOk()->assertDownload('note.pdf');
        $this->get('/portal/course-files/'.$later->files()->first()->id)->assertNotFound();
        $this->post("/portal/videos/{$later->id}/watched", ['watched' => 1])->assertNotFound();
        $this->post("/portal/videos/{$paidOnly->id}/watched", ['watched' => 1])->assertNotFound();

        // Watched progress
        $this->postJson("/portal/videos/{$first->id}/watched", ['watched' => 1])->assertOk();
        $this->get('/portal/videos')->assertSee('id="doneCount">১</span>/১ ক্লাস দেখা হয়েছে', false);
        $this->actingAs($this->admin, 'web')->get("/admin/course-videos?course=$dep")->assertSee('দেখেছে ১/')->assertSee('✓ '.$student->name_bn);
        $this->actingAs($student, 'student')->postJson("/portal/videos/{$first->id}/watched", ['watched' => 0])->assertOk();
        $this->assertSame(0, $student->watchedVideos()->count());

        // Paying the fees and the release date passing open the locked classes.
        $student->payments()->create(['amount' => $student->totalDue(), 'paid_on' => now(), 'item' => 'কোর্স ফি', 'method' => 'cash', 'receipt_no' => 'T-1']);
        $this->travel(8)->days();
        $this->get('/portal/videos')->assertDontSee('বকেয়া ফি পরিশোধ করলে')->assertDontSee('থেকে দেখা যাবে')->assertSee('later.pdf');

        // Deleting a class removes its stored files too.
        $this->actingAs($this->admin, 'web')->delete("/admin/course-videos/{$first->id}")->assertRedirect();
        Storage::disk('local')->assertMissing([$first->path, $note->path]);
        $this->assertSame(0, \App\Models\CourseFile::where('course_video_id', $first->id)->count());
    }

    public function test_student_portal(): void
    {
        $student = Student::where('name_en', 'Tanvir Ahmed')->firstOrFail();

        $this->post('/portal/login', ['student_code' => strtolower($student->student_code), 'password' => 'wrong'])->assertSessionHasErrors('student_code');
        $this->post('/portal/login', ['student_code' => strtolower($student->student_code), 'password' => '123456'])->assertRedirect('/portal');
        $this->get('/portal')->assertOk()->assertSee('তানভীর আহমেদ')->assertSee('৳৪,০০০')->assertSee('ইলাস্ট্রেটর মডিউল টেস্টের তারিখ ঘোষণা');
        $this->get('/portal/id-card')->assertOk();
        $this->get('/portal/receipts/'.$student->payments()->first()->id)->assertOk();

        // A student cannot open someone else's receipt, or the admin panel
        $other = Payment::where('student_id', '!=', $student->id)->first();
        $this->get('/portal/receipts/'.$other->id)->assertNotFound();
        $this->get('/admin')->assertRedirect('/login');

        $this->post('/portal/password', ['current_password' => '123456', 'password' => 'newpass1', 'password_confirmation' => 'newpass1'])->assertSessionHas('ok');
        $this->post('/portal/logout')->assertRedirect('/login');
        $this->get('/portal')->assertRedirect('/login');
    }

    public function test_payments_attendance_results_notes_and_notices(): void
    {
        $batch = Batch::where('code', 'GD-2607')->first();
        $student = $batch->students()->where('name_en', 'Md. Fahim Uddin')->first();
        $this->actingAs($this->admin);

        $this->post("/admin/students/{$student->id}/payments", ['amount' => '২০০০', 'paid_on' => now()->toDateString(), 'item' => '১ম কিস্তি', 'method' => 'নগদ (ক্যাশ)'])
            ->assertRedirect()->assertSessionHas('ok');
        $this->assertSame(2000, $student->fresh()->totalPaid());

        $this->post("/admin/batches/{$batch->id}/attendance", ['date' => now()->toDateString(), 'status' => [$student->id => 'present']])->assertRedirect();
        $this->assertTrue((bool) $student->attendances()->whereDate('date', now())->value('present'));

        $this->post("/admin/students/{$student->id}/assessments", ['title' => 'টেস্ট', 'score' => '৯০', 'max_score' => 100, 'status' => 'passed'])->assertRedirect();
        $this->post("/admin/students/{$student->id}/notes", ['body' => 'নোট'])->assertRedirect();
        $this->post('/admin/notices', ['title' => 'নতুন নোটিশ', 'tag' => 'general', 'published_on' => now()->toDateString(), 'audience' => 'all', 'is_public' => '1'])->assertSessionHasNoErrors();
        $this->get('/')->assertSee('নতুন নোটিশ');

        $this->put("/admin/students/{$student->id}", array_merge($student->only([
            'name_bn', 'name_en', 'father_name', 'mother_name', 'gender', 'guardian_phone', 'phone', 'present_village', 'present_upazila', 'present_district',
            'admission_type', 'installments', 'course_fee', 'admission_fee',
        ]), ['batch_id' => Batch::where('code', 'GD-2610')->value('id'), 'admission_date' => '2026-06-28', 'status' => 'on_leave']))->assertSessionHasNoErrors();
        $this->assertSame('on_leave', $student->fresh()->status);

        $this->post('/admin/departments', ['code' => 'gd', 'name' => 'x', 'course_fee' => 1, 'admission_fee' => 1])->assertSessionHasErrors('code');
        $this->post('/admin/students', [])->assertSessionHasErrors(['name_bn', 'phone', 'batch_id']);
    }
}
