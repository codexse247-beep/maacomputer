<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Department;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/** Holidays, exams and events — shown in the student portal (and on the website when public). */
class EventController extends Controller
{
    public function index(Request $request)
    {
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('month')) ? Carbon::createFromFormat('Y-m-d', $request->query('month').'-01') : now()->startOfMonth();

        return view('admin.events.index', [
            'month' => $month,
            'events' => Event::with(['department', 'batch'])->between($month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString())->orderBy('starts_on')->get(),
            'upcoming' => Event::with(['department', 'batch'])->where(fn ($q) => $q->where('starts_on', '>=', now()->toDateString())->orWhere('ends_on', '>=', now()->toDateString()))->orderBy('starts_on')->take(15)->get(),
            'event' => $request->integer('edit') ? Event::find($request->integer('edit')) : null,
            'departments' => Department::orderBy('sort')->get(),
            'batches' => Batch::whereIn('status', ['running', 'enrolling'])->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $event = Event::create($this->validated($request));

        return redirect()->route('admin.events.index', ['month' => $event->starts_on->format('Y-m')])->with('ok', 'ক্যালেন্ডারে যোগ হয়েছে।');
    }

    public function update(Request $request, Event $event)
    {
        $event->update($this->validated($request));

        return redirect()->route('admin.events.index', ['month' => $event->starts_on->format('Y-m')])->with('ok', 'হালনাগাদ হয়েছে।');
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return back()->with('ok', 'মুছে ফেলা হয়েছে।');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(Event::TYPES))],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'audience' => ['required', Rule::in(['all', 'department', 'batch'])],
            'department_id' => ['nullable', 'required_if:audience,department', 'exists:departments,id'],
            'batch_id' => ['nullable', 'required_if:audience,batch', 'exists:batches,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ], ['required_if' => ':attribute বেছে নিন।', 'after_or_equal' => 'শেষের তারিখ শুরুর তারিখের আগে হতে পারে না।'], [
            'title' => 'শিরোনাম', 'starts_on' => 'তারিখ', 'ends_on' => 'শেষের তারিখ', 'department_id' => 'বিভাগ', 'batch_id' => 'ব্যাচ',
        ]);

        $audience = $data['audience'];
        unset($data['audience']);
        $data['department_id'] = $audience === 'department' ? $data['department_id'] : null;
        $data['batch_id'] = $audience === 'batch' ? $data['batch_id'] : null;
        $data['is_public'] = $audience === 'all' && $request->boolean('is_public');

        return $data;
    }
}
