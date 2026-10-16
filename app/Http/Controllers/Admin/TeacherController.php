<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.teachers.index', [
            'teachers' => Teacher::with(['department', 'batches' => fn ($q) => $q->where('status', '!=', 'completed')])->orderByDesc('active')->orderBy('sort')->orderBy('name')->get(),
            'departments' => Department::orderBy('sort')->get(),
            'editing' => $request->integer('edit') ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $teacher = Teacher::create($this->validated($request));
        $this->savePhoto($request, $teacher);

        return redirect()->route('admin.teachers.index')->with('ok', 'শিক্ষক যোগ হয়েছে।');
    }

    public function update(Request $request, Teacher $teacher)
    {
        $teacher->update($this->validated($request));
        $this->savePhoto($request, $teacher);
        $teacher->batches()->update(['teacher' => $teacher->name]);

        return redirect()->route('admin.teachers.index')->with('ok', 'শিক্ষকের তথ্য হালনাগাদ হয়েছে।');
    }

    public function destroy(Teacher $teacher)
    {
        if ($teacher->photo) {
            Storage::disk('public')->delete($teacher->photo);
        }
        $teacher->delete();

        return redirect()->route('admin.teachers.index')->with('ok', 'শিক্ষক মুছে ফেলা হয়েছে। তাঁর ব্যাচগুলোতে নাম থেকে যাবে।');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'subject' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'regex:/^\+?[0-9\- ]{6,20}$/'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ], ['regex' => ':attribute সঠিক মোবাইল নম্বর নয়।'], ['name' => 'নাম', 'phone' => 'মোবাইল', 'photo' => 'ছবি']);

        unset($data['photo']);
        $data['sort'] = $data['sort'] ?? 0;
        $data['show_on_site'] = $request->boolean('show_on_site');
        $data['active'] = $request->boolean('active');
        if (($en = Teacher::englishInput($request, ['name', 'subject', 'bio'])) !== null) {
            $data['en'] = $en;
        }

        return $data;
    }

    private function savePhoto(Request $request, Teacher $teacher): void
    {
        if ($request->hasFile('photo')) {
            if ($teacher->photo) {
                Storage::disk('public')->delete($teacher->photo);
            }
            $teacher->update(['photo' => $request->file('photo')->store('teachers', 'public')]);
        }
    }
}
