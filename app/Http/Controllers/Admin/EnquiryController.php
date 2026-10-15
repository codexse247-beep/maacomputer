<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use Illuminate\Http\Request;

/** Admission applications, contact messages and password-reset requests sent from the website. */
class EnquiryController extends Controller
{
    public function index(Request $request)
    {
        $type = array_key_exists((string) $request->query('type'), Enquiry::TYPES) ? $request->query('type') : null;
        $status = $request->query('status', 'new');

        return view('admin.enquiries.index', [
            'enquiries' => Enquiry::with(['department', 'handler'])
                ->when($type, fn ($q) => $q->where('type', $type))
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->latest()->paginate(25)->withQueryString(),
            'type' => $type,
            'status' => $status,
            'counts' => Enquiry::where('status', 'new')->selectRaw('type, COUNT(*) as n')->groupBy('type')->pluck('n', 'type'),
        ]);
    }

    public function update(Request $request, Enquiry $enquiry)
    {
        $done = $request->input('status') === 'done';
        $enquiry->update(['status' => $done ? 'done' : 'new', 'handled_by' => $done ? $request->user()->id : null]);

        return back()->with('ok', $done ? 'সম্পন্ন হিসেবে চিহ্নিত হয়েছে।' : 'আবার নতুন হিসেবে চিহ্নিত হয়েছে।');
    }

    public function destroy(Enquiry $enquiry)
    {
        $enquiry->delete();

        return back()->with('ok', 'মুছে ফেলা হয়েছে।');
    }
}
