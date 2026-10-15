<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Batch;
use App\Models\Department;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public const EXAMS = ['এসএসসি / দাখিল', 'এইচএসসি / ডিপ্লোমা', 'স্নাতক / অন্যান্য'];

    public function index(Request $request)
    {
        $query = Student::with('batch.department')->withSum('payments', 'amount')->latest('admission_date')->latest('id');

        if ($q = trim((string) $request->query('q'))) {
            $query->where(fn ($w) => $w->where('name_bn', 'like', "%$q%")->orWhere('name_en', 'like', "%$q%")
                ->orWhere('student_code', 'like', "%$q%")->orWhere('phone', 'like', "%$q%")->orWhere('guardian_phone', 'like', "%$q%"));
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return view('admin.students.index', ['students' => $query->paginate(25)->withQueryString(), 'q' => $q ?? '']);
    }

    public function create(Request $request)
    {
        $student = new Student([
            'admission_date' => now(),
            'nationality' => 'বাংলাদেশি',
            'admission_type' => 'নতুন ভর্তি',
            'installments' => 1,
            'batch_id' => $request->integer('batch') ?: null,
        ]);

        return view('admin.students.form', $this->formData($student));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $batch = Batch::with('department')->findOrFail($data['batch_id']);
        $password = (string) random_int(100000, 999999);
        $first = (int) ($request->input('first_payment') ?: 0);

        $student = DB::transaction(function () use ($data, $batch, $password, $first, $request) {
            [$code, $roll] = Student::nextCodeFor($batch);
            $student = Student::create($data + [
                'student_code' => $code,
                'roll' => $roll,
                'course_fee' => $batch->department->course_fee,
                'admission_fee' => $batch->department->admission_fee,
                'password' => $password,
                'admitted_by' => $request->user()->id,
                'status' => 'active',
            ]);

            if ($first > 0) {
                $student->payments()->create([
                    'receipt_no' => Payment::nextReceiptNo(),
                    'paid_on' => $data['admission_date'],
                    'item' => 'ভর্তি ফি + ১ম কিস্তি',
                    'amount' => $first,
                    'method' => $request->input('method', Payment::METHODS[0]),
                    'received_by' => $request->user()->id,
                ]);
            }

            return $student;
        });

        $this->storeUploads($request, $student);

        return redirect()->route('admin.students.show', $student)->with('ok',
            'ভর্তি সম্পন্ন হয়েছে। স্টুডেন্ট আইডি <b>'.e($student->student_code).'</b> · পোর্টাল পাসওয়ার্ড <b>'.$password.'</b> — পাসওয়ার্ডটি স্টুডেন্টকে দিন, পরে আর দেখা যাবে না।');
    }

    public function show(Student $student)
    {
        $student->load(['batch.department', 'payments' => fn ($q) => $q->latest('paid_on')->latest('id'), 'assessments' => fn ($q) => $q->orderBy('held_on'), 'documents', 'notes.author', 'admittedBy'])
            ->loadSum('payments', 'amount');

        return view('admin.students.show', ['student' => $student, 'methods' => Payment::METHODS]);
    }

    public function edit(Student $student)
    {
        return view('admin.students.form', $this->formData($student));
    }

    public function update(Request $request, Student $student)
    {
        $data = $this->validated($request, $student);
        $data += $request->validate([
            'status' => ['required', Rule::in(array_keys(Student::STATUSES))],
            'course_fee' => ['required', 'integer', 'min:0'],
            'admission_fee' => ['required', 'integer', 'min:0'],
        ]);

        $student->update($data);
        $this->storeUploads($request, $student);

        return redirect()->route('admin.students.show', $student)->with('ok', 'স্টুডেন্টের তথ্য হালনাগাদ হয়েছে।');
    }

    public function destroy(Student $student)
    {
        $batch = $student->batch;
        foreach ($student->documents as $doc) {
            Storage::disk('local')->delete($doc->path);
        }
        if ($student->photo) {
            Storage::disk('public')->delete($student->photo);
        }
        $student->delete();

        return redirect()->route('admin.batches.show', $batch)->with('ok', 'স্টুডেন্টের সব তথ্য মুছে ফেলা হয়েছে।');
    }

    public function resetPassword(Student $student)
    {
        $password = (string) random_int(100000, 999999);
        $student->update(['password' => $password]);

        return back()->with('ok', 'নতুন পোর্টাল পাসওয়ার্ড: <b>'.$password.'</b> — স্টুডেন্টকে জানিয়ে দিন।');
    }

    /** Gives a certificate number (MCT-C-2026-0001) and marks the course as completed. */
    public function issueCertificate(Request $request, Student $student)
    {
        if ($student->certificate_no) {
            return back()->withErrors(['certificate' => 'এই স্টুডেন্টকে আগেই সনদ দেওয়া হয়েছে।']);
        }
        if (! $student->dob) {
            return back()->withErrors(['certificate' => 'সনদ যাচাইয়ের জন্য জন্ম তারিখ লাগবে। আগে প্রোফাইলে জন্ম তারিখ যোগ করুন।']);
        }

        $year = now()->year;
        $last = Student::where('certificate_no', 'like', "MCT-C-$year-%")->orderByDesc('certificate_no')->value('certificate_no');
        $next = $last ? (int) substr($last, -4) + 1 : 1;
        $student->update([
            'certificate_no' => sprintf('MCT-C-%d-%04d', $year, $next),
            'certificate_issued_on' => now()->toDateString(),
            'status' => 'completed',
        ]);

        return back()->with('ok', 'সনদ প্রদান করা হয়েছে — সনদ নং <b>'.e($student->certificate_no).'</b>। এটি ওয়েবসাইটের "সনদ যাচাই" পেজে যাচাই করা যাবে।');
    }

    public function addNote(Request $request, Student $student)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']], [], ['body' => 'নোট']);
        $student->notes()->create(['body' => $data['body'], 'user_id' => $request->user()->id]);

        return redirect(route('admin.students.show', $student).'#docs')->with('ok', 'নোট সংরক্ষণ হয়েছে।');
    }

    public function addDocument(Request $request, Student $student)
    {
        $this->storeUploads($request, $student);

        return redirect(route('admin.students.show', $student).'#docs')->with('ok', 'ডকুমেন্ট আপলোড হয়েছে।');
    }

    public function document(StudentDocument $document)
    {
        $disk = $document->type === 'photo' ? 'public' : 'local';

        return Storage::disk($disk)->response($document->path, $document->original_name);
    }

    public function addAssessment(Request $request, Student $student)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'held_on' => ['nullable', 'date'],
            'score' => ['nullable', 'integer', 'min:0'],
            'max_score' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::in(array_keys(Assessment::STATUSES))],
        ], [], ['title' => 'মূল্যায়নের নাম', 'score' => 'প্রাপ্ত নম্বর', 'max_score' => 'পূর্ণ নম্বর']);
        $student->assessments()->create($data);

        return redirect(route('admin.students.show', $student).'#results')->with('ok', 'ফলাফল যোগ হয়েছে।');
    }

    public function deleteAssessment(Assessment $assessment)
    {
        $student = $assessment->student;
        $assessment->delete();

        return redirect(route('admin.students.show', $student).'#results')->with('ok', 'ফলাফল মুছে ফেলা হয়েছে।');
    }

    // -----------------------------------------------------------------------

    private function formData(Student $student): array
    {
        $departments = Department::orderBy('sort')->with(['batches' => fn ($q) => $q->where('status', '!=', 'completed')->withCount('activeStudents')->orderBy('code')])->get();

        // Keep the student's current batch selectable even if it has finished.
        if ($student->batch_id && ! $departments->pluck('batches')->flatten()->contains('id', $student->batch_id)) {
            $current = Batch::withCount('activeStudents')->find($student->batch_id);
            $departments->firstWhere('id', $current?->department_id)?->batches->push($current);
        }

        return ['student' => $student, 'departments' => $departments, 'exams' => self::EXAMS, 'methods' => Payment::METHODS];
    }

    private function validated(Request $request, ?Student $student = null): array
    {
        $phone = ['regex:/^\+?[0-9\- ]{6,20}$/'];
        $data = $request->validate([
            'name_bn' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'father_name' => ['required', 'string', 'max:255'],
            'mother_name' => ['required', 'string', 'max:255'],
            'dob' => ['nullable', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['পুরুষ', 'মহিলা', 'অন্যান্য'])],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'religion' => ['nullable', 'string', 'max:30'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'nid' => ['nullable', 'string', 'max:40'],
            'marital' => ['nullable', 'string', 'max:20'],
            'father_job' => ['nullable', 'string', 'max:255'],
            'mother_job' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => array_merge(['required'], $phone),
            'emergency_name' => ['nullable', 'string', 'max:255'],
            'emergency_relation' => ['nullable', 'string', 'max:255'],
            'emergency_phone' => array_merge(['nullable'], $phone),
            'phone' => array_merge(['required'], $phone),
            'whatsapp' => array_merge(['nullable'], $phone),
            'email' => ['nullable', 'email', 'max:255'],
            'present_village' => ['required', 'string', 'max:255'],
            'present_po' => ['nullable', 'string', 'max:255'],
            'present_upazila' => ['required', 'string', 'max:255'],
            'present_district' => ['required', 'string', 'max:255'],
            'perm_village' => ['nullable', 'string', 'max:255'],
            'perm_po' => ['nullable', 'string', 'max:255'],
            'perm_upazila' => ['nullable', 'string', 'max:255'],
            'perm_district' => ['nullable', 'string', 'max:255'],
            'edu' => ['nullable', 'array'],
            'edu.*.inst' => ['nullable', 'string', 'max:255'],
            'edu.*.group' => ['nullable', 'string', 'max:100'],
            'edu.*.board' => ['nullable', 'string', 'max:255'],
            'edu.*.year' => ['nullable', 'string', 'max:20'],
            'edu.*.result' => ['nullable', 'string', 'max:50'],
            'prior_knowledge' => ['nullable', 'string', 'max:20'],
            'goal' => ['nullable', 'string', 'max:255'],
            'batch_id' => ['required', 'exists:batches,id'],
            'admission_date' => ['required', 'date'],
            'admission_type' => ['required', 'string', 'max:30'],
            'reference' => ['nullable', 'string', 'max:255'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'installments' => ['required', 'integer', 'min:1', 'max:24'],
            'next_installment_date' => ['nullable', 'date'],
            'next_installment_amount' => ['nullable', 'integer', 'min:0'],
            'first_payment' => ['nullable', 'integer', 'min:0'],
            'method' => ['nullable', Rule::in(Payment::METHODS)],
            'photo' => ['nullable', 'image', 'max:2048'],
            'doc_nid' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'doc_certificate' => ['nullable', 'array'],
            'doc_certificate.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'doc_form' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'regex' => ':attribute সঠিক মোবাইল নম্বর নয়।',
        ], [
            'name_bn' => 'নাম (বাংলা)', 'name_en' => 'নাম (ইংরেজি)', 'father_name' => 'পিতার নাম', 'mother_name' => 'মাতার নাম',
            'dob' => 'জন্ম তারিখ', 'gender' => 'লিঙ্গ', 'guardian_phone' => 'অভিভাবকের মোবাইল', 'phone' => 'স্টুডেন্টের মোবাইল',
            'whatsapp' => 'হোয়াটসঅ্যাপ নম্বর', 'emergency_phone' => 'জরুরি যোগাযোগের মোবাইল', 'email' => 'ইমেইল',
            'present_village' => 'গ্রাম / রোড / বাসা', 'present_upazila' => 'উপজেলা', 'present_district' => 'জেলা',
            'batch_id' => 'ব্যাচ', 'admission_date' => 'ভর্তির তারিখ', 'discount' => 'ছাড়', 'installments' => 'কিস্তির সংখ্যা',
            'first_payment' => 'প্রথম পেমেন্ট', 'photo' => 'ছবি', 'doc_nid' => 'এনআইডি কপি', 'doc_form' => 'ভর্তি ফরম',
        ]);

        if ($request->boolean('same_address')) {
            foreach (['village', 'po', 'upazila', 'district'] as $part) {
                $data['perm_'.$part] = $data['present_'.$part] ?? null;
            }
        }

        $data['education'] = collect(self::EXAMS)->map(fn ($exam, $i) => ['exam' => $exam] + array_map(fn ($v) => $v ?? '', $request->input("edu.$i", [])))
            ->filter(fn ($row) => trim(implode('', array_slice($row, 1))) !== '')->values()->all();
        $data['discount'] = $data['discount'] ?? 0;

        unset($data['edu'], $data['first_payment'], $data['method'], $data['photo'], $data['doc_nid'], $data['doc_certificate'], $data['doc_form']);

        return $data;
    }

    private function storeUploads(Request $request, Student $student): void
    {
        $request->validate([
            'photo' => ['nullable', 'image', 'max:2048'],
            'doc_nid' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'doc_certificate.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'doc_form' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        if ($request->hasFile('photo')) {
            if ($student->photo) {
                Storage::disk('public')->delete($student->photo);
            }
            $student->update(['photo' => $request->file('photo')->store('photos', 'public')]);
            $student->documents()->updateOrCreate(['type' => 'photo'], ['path' => $student->photo, 'original_name' => 'photo']);
        }

        $files = ['nid' => $request->file('doc_nid'), 'form' => $request->file('doc_form')];
        foreach ($files as $type => $file) {
            if ($file) {
                $student->documents()->create(['type' => $type, 'path' => $file->store('documents/'.$student->id, 'local'), 'original_name' => $file->getClientOriginalName()]);
            }
        }
        foreach ((array) $request->file('doc_certificate', []) as $file) {
            $student->documents()->create(['type' => 'certificate', 'path' => $file->store('documents/'.$student->id, 'local'), 'original_name' => $file->getClientOriginalName()]);
        }
    }
}
