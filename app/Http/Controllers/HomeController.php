<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Department;
use App\Models\Enquiry;
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
            'times' => self::TIMES,
            'education' => self::EDUCATION,
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
        ], ['regex' => ':attribute সঠিক মোবাইল নম্বর নয়।'], [
            'name' => 'নাম', 'father_name' => 'পিতার নাম', 'phone' => 'মোবাইল নম্বর', 'department_id' => 'বিভাগ', 'dob' => 'জন্ম তারিখ',
        ]);

        Enquiry::create($data + ['type' => 'admission']);

        return redirect()->route('admission')->with('ok', 'আপনার আবেদন পাওয়া গেছে। শীঘ্রই আমরা আপনাকে ফোন করব।');
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
        $category = array_key_exists((string) $request->query('c'), GalleryPhoto::CATEGORIES) ? $request->query('c') : null;

        return view('site.gallery', [
            'photos' => GalleryPhoto::when($category, fn ($q) => $q->where('category', $category))->latest()->get(),
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
            ], [], ['certificate_no' => 'সনদ নম্বর', 'dob' => 'জন্ম তারিখ']);

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
        ], ['regex' => ':attribute সঠিক মোবাইল নম্বর নয়।'], ['name' => 'নাম', 'phone' => 'মোবাইল নম্বর', 'message' => 'বার্তা']);

        Enquiry::create($data + ['type' => 'contact']);

        return redirect()->route('contact')->with('ok', 'ধন্যবাদ! আপনার মেসেজ পাওয়া গেছে। আমরা শীঘ্রই যোগাযোগ করব।');
    }

    public function notices(Request $request)
    {
        $tag = array_key_exists((string) $request->query('tag'), Notice::TAGS) ? $request->query('tag') : null;

        return view('notices', [
            'notices' => $this->publicNotices()->when($tag, fn ($q) => $q->where('tag', $tag))->paginate(15)->withQueryString(),
            'tag' => $tag,
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
        ], ['regex' => ':attribute সঠিক মোবাইল নম্বর নয়।'], ['student_code' => 'স্টুডেন্ট আইডি', 'phone' => 'মোবাইল নম্বর']);

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

        return redirect()->route('forgot')->with('ok', 'অনুরোধ পাওয়া গেছে। যাচাই করে অফিস থেকে আপনাকে ফোনে নতুন পাসওয়ার্ড জানানো হবে।');
    }
}
