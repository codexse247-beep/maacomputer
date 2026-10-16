<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Department;
use App\Models\Enquiry;
use App\Models\Event;
use App\Models\Faq;
use App\Models\GalleryPhoto;
use App\Models\Notice;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HomeController extends Controller
{
    public const TIMES = ['সকাল', 'দুপুর', 'বিকাল', 'সন্ধ্যা'];

    public const EDUCATION = ['এসএসসি / দাখিল', 'এইচএসসি / ডিপ্লোমা', 'স্নাতক বা তার বেশি', 'অন্যান্য'];

    private function publicNotices()
    {
        return Notice::where('is_public', true)->whereNull('batch_id')->whereNull('department_id')->latest('published_on')->latest('id');
    }

    public function index()
    {
        return view('home', [
            'departments' => Department::orderBy('sort')->orderBy('id')->get(),
            'notices' => $this->publicNotices()->take(3)->get(),
        ]);
    }

    public function departments()
    {
        return view('site.departments', ['departments' => Department::orderBy('sort')->orderBy('id')->get()]);
    }

    public function admission(Request $request)
    {
        return view('site.admission', [
            'departments' => Department::orderBy('sort')->orderBy('id')->get(),
            'selected' => $request->integer('department') ?: null,
            // Stored in Bangla, shown in the visitor's language
            'times' => collect(self::TIMES)->mapWithKeys(fn ($t) => [$t => __($t)])->all(),
            'education' => collect(self::EDUCATION)->mapWithKeys(fn ($e) => [$e => __($e)])->all(),
        ]);
    }

    public function storeAdmission(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'father_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^\+?[0-9\- ]{6,20}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'dob' => ['nullable', 'date', 'before:today'],
            'education' => ['nullable', Rule::in(self::EDUCATION)],
            'department_id' => ['required', 'exists:departments,id'],
            'preferred_time' => ['nullable', Rule::in(self::TIMES)],
            'message' => ['nullable', 'string', 'max:2000'],
        ], ['regex' => __(':attribute সঠিক মোবাইল নম্বর নয়।')], [
            'name' => __('নাম'), 'father_name' => __('পিতার নাম'), 'phone' => __('মোবাইল নম্বর'), 'department_id' => __('বিভাগ'), 'dob' => __('জন্ম তারিখ'), 'email' => __('ইমেইল'),
        ]);

        Enquiry::create($data + ['type' => 'admission']);

        return redirect()->route('admission')->with('ok', __('আপনার আবেদন পাওয়া গেছে। শীঘ্রই আমরা আপনাকে ফোন করব।'));
    }

    public function about()
    {
        return view('site.about', [
            'departmentCount' => Department::count(),
            'batchCount' => Batch::whereIn('status', ['running', 'enrolling'])->count(),
            'studentCount' => Student::where('status', 'active')->count(),
            'certifiedCount' => Student::whereNotNull('certificate_no')->count(),
        ]);
    }

    public function instructors()
    {
        return view('site.instructors', ['teachers' => Teacher::with('department')->where('active', true)->where('show_on_site', true)->orderBy('sort')->orderBy('name')->get()]);
    }

    public function gallery(Request $request)
    {
        // ?c= is a category, or "video" for YouTube videos only.
        $c = (string) $request->query('c');
        $category = array_key_exists($c, GalleryPhoto::CATEGORIES) || $c === 'video' ? $c : null;

        return view('site.gallery', [
            'items' => GalleryPhoto::query()
                ->when($category === 'video', fn ($q) => $q->where('type', 'video'))
                ->when($category && $category !== 'video', fn ($q) => $q->where('category', $category))
                ->latest('id')->get(),
            'category' => $category,
        ]);
    }

    public function faq()
    {
        return view('site.faq', ['faqs' => Faq::orderBy('sort')->orderBy('id')->get()]);
    }

    public function verify(Request $request)
    {
        $result = null;
        if ($request->filled('certificate_no')) {
            $data = $request->validate([
                'certificate_no' => ['required', 'string', 'max:40'],
                'dob' => ['required', 'date'],
            ], [], ['certificate_no' => __('সনদ নম্বর'), 'dob' => __('জন্ম তারিখ')]);

            $result = Student::with('batch.department')
                ->where('certificate_no', strtoupper(trim($data['certificate_no'])))
                ->whereDate('dob', $data['dob'])
                ->first() ?? false;
        }

        return view('site.verify', ['result' => $result]);
    }

    public function privacy()
    {
        return view('site.privacy');
    }

    public function contact()
    {
        return view('site.contact');
    }

    public function storeContact(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^\+?[0-9\- ]{6,20}$/'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:3000'],
        ], ['regex' => __(':attribute সঠিক মোবাইল নম্বর নয়।')], ['name' => __('নাম'), 'phone' => __('মোবাইল নম্বর'), 'message' => __('বার্তা')]);

        Enquiry::create($data + ['type' => 'contact']);

        return redirect()->route('contact')->with('ok', __('ধন্যবাদ! আপনার মেসেজ পাওয়া গেছে। আমরা শীঘ্রই যোগাযোগ করব।'));
    }

    public function notices(Request $request)
    {
        $tag = array_key_exists((string) $request->query('tag'), Notice::TAGS) ? $request->query('tag') : null;

        return view('notices', [
            'notices' => $this->publicNotices()->when($tag, fn ($q) => $q->where('tag', $tag))->paginate(15)->withQueryString(),
            'tag' => $tag,
            'events' => Event::where('is_public', true)->where(fn ($q) => $q->where('starts_on', '>=', now()->toDateString())->orWhere('ends_on', '>=', now()->toDateString()))
                ->orderBy('starts_on')->take(6)->get(),
        ]);
    }

    public function login()
    {
        if (auth('student')->check()) {
            return redirect()->route('portal.dashboard');
        }

        return view('site.login');
    }

    public function forgot()
    {
        return view('site.forgot');
    }

    public function storeForgot(Request $request)
    {
        $data = $request->validate([
            'student_code' => ['required', 'string', 'max:30'],
            'phone' => ['required', 'regex:/^\+?[0-9\- ]{6,20}$/'],
        ], ['regex' => __(':attribute সঠিক মোবাইল নম্বর নয়।')], ['student_code' => __('স্টুডেন্ট আইডি'), 'phone' => __('মোবাইল নম্বর')]);

        $code = strtoupper(trim($data['student_code']));
        $student = Student::where('student_code', $code)->first();

        // Same answer whether or not the ID exists, so the form can't be used to discover student IDs.
        if ($student) {
            $matches = preg_replace('/\D/', '', $data['phone']) === preg_replace('/\D/', '', $student->phone);
            Enquiry::create([
                'type' => 'password', 'name' => $student->name_bn, 'phone' => $data['phone'], 'student_code' => $code,
                'message' => $matches ? 'মোবাইল নম্বর রেকর্ডের সাথে মিলেছে' : 'মোবাইল নম্বর রেকর্ডের সাথে মেলেনি — ফোন করে যাচাই করুন',
            ]);
        }

        return redirect()->route('forgot')->with('ok', __('অনুরোধ পাওয়া গেছে। যাচাই করে অফিস থেকে আপনাকে ফোনে নতুন পাসওয়ার্ড জানানো হবে।'));
    }

    /** For Google: every public page, in both languages. */
    public function sitemap()
    {
        $pages = ['home' => '1.0', 'departments' => '0.9', 'admission' => '0.9', 'about' => '0.7', 'instructors' => '0.6', 'gallery' => '0.6',
            'notices' => '0.8', 'faq' => '0.6', 'contact' => '0.7', 'verify' => '0.5', 'privacy' => '0.3'];
        $updated = max(Notice::max('updated_at') ?? now(), Department::max('updated_at') ?? now());

        return response()->view('sitemap', ['pages' => $pages, 'updated' => \Illuminate\Support\Carbon::parse($updated)], 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots()
    {
        $lines = ['User-agent: *', 'Disallow: /admin', 'Disallow: /portal', 'Disallow: /staff', '', 'Sitemap: '.route('sitemap')];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
