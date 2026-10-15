<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Department;
use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NoticeController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.notices.index', [
            'notices' => Notice::with(['batch', 'department'])->latest('published_on')->latest('id')->paginate(20),
            'batches' => Batch::whereIn('status', ['running', 'enrolling'])->orderBy('code')->get(),
            'departments' => Department::orderBy('sort')->get(),
            'notice' => $request->integer('edit') ? Notice::find($request->integer('edit')) : null,
        ]);
    }

    public function store(Request $request)
    {
        Notice::create($this->validated($request));

        return redirect()->route('admin.notices.index')->with('ok', 'নোটিশ প্রকাশ হয়েছে।');
    }

    public function update(Request $request, Notice $notice)
    {
        $notice->update($this->validated($request));

        return redirect()->route('admin.notices.index')->with('ok', 'নোটিশ হালনাগাদ হয়েছে।');
    }

    public function destroy(Notice $notice)
    {
        $notice->delete();

        return redirect()->route('admin.notices.index')->with('ok', 'নোটিশ মুছে ফেলা হয়েছে।');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'tag' => ['required', Rule::in(array_keys(Notice::TAGS))],
            'published_on' => ['required', 'date'],
            'body' => ['nullable', 'string', 'max:5000'],
            'audience' => ['required', Rule::in(['all', 'department', 'batch'])],
            'department_id' => ['nullable', 'required_if:audience,department', 'exists:departments,id'],
            'batch_id' => ['nullable', 'required_if:audience,batch', 'exists:batches,id'],
        ], ['required_if' => ':attribute বেছে নিন।'], ['title' => 'শিরোনাম', 'tag' => 'ধরন', 'published_on' => 'তারিখ', 'department_id' => 'বিভাগ', 'batch_id' => 'ব্যাচ']);

        $audience = $data['audience'];
        unset($data['audience']);
        $data['department_id'] = $audience === 'department' ? $data['department_id'] : null;
        $data['batch_id'] = $audience === 'batch' ? $data['batch_id'] : null;
        // Only notices for everyone can appear on the public website.
        $data['is_public'] = $audience === 'all' && $request->boolean('is_public');

        return $data;
    }
}
