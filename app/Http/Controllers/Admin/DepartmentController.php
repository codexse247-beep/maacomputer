<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::orderBy('sort')->orderBy('id')
            ->withCount('batches')
            ->withCount(['students' => fn ($q) => $q->where('students.status', 'active')])
            ->with(['batches' => fn ($q) => $q->withCount('activeStudents')->latest('start_date')])
            ->get();

        return view('admin.departments.index', [
            'departments' => $departments,
            'batchCount' => $departments->sum('batches_count'),
            'studentCount' => $departments->sum('students_count'),
        ]);
    }

    public function create()
    {
        return view('admin.departments.form', ['department' => new Department]);
    }

    public function store(Request $request)
    {
        Department::create($this->validated($request));

        return redirect()->route('admin.departments.index')->with('ok', 'বিভাগ যোগ হয়েছে।');
    }

    public function edit(Department $department)
    {
        return view('admin.departments.form', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        $department->update($this->validated($request, $department));

        return redirect()->route('admin.departments.show', $department)->with('ok', 'বিভাগের তথ্য হালনাগাদ হয়েছে।');
    }

    public function destroy(Department $department)
    {
        if ($department->batches()->exists()) {
            return back()->withErrors(['department' => 'এই বিভাগে ব্যাচ আছে। আগে ব্যাচগুলো মুছুন বা সরান।']);
        }
        $department->delete();

        return redirect()->route('admin.departments.index')->with('ok', 'বিভাগ মুছে ফেলা হয়েছে।');
    }

    private function validated(Request $request, ?Department $department = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'alpha_num', Rule::unique('departments')->ignore($department)],
            'name' => ['required', 'string', 'max:255'],
            'duration' => ['nullable', 'string', 'max:50'],
            'blurb' => ['nullable', 'string', 'max:500'],
            'course_name' => ['nullable', 'string', 'max:255'],
            'course_fee' => ['required', 'integer', 'min:0'],
            'admission_fee' => ['required', 'integer', 'min:0'],
            'modules' => ['nullable', 'string', 'max:255'],
            'topics' => ['nullable', 'string', 'max:255'],
            'learn' => ['nullable', 'string', 'max:2000'],
            'routine_note' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ], [], [
            'code' => 'কোড', 'name' => 'বিভাগের নাম', 'duration' => 'মেয়াদ', 'course_fee' => 'কোর্স ফি', 'admission_fee' => 'ভর্তি ফি',
        ]);
        $data['code'] = strtoupper($data['code']);
        $data['sort'] = $data['sort'] ?? 0;

        return $data;
    }
}
