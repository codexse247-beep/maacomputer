<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

/** Who did what in the admin panel (admin only). */
class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $type = array_key_exists((string) $request->query('type'), ActivityLog::SUBJECTS) ? $request->query('type') : null;
        $action = array_key_exists((string) $request->query('action'), ActivityLog::ACTIONS) ? $request->query('action') : null;

        return view('admin.activity.index', [
            'logs' => ActivityLog::with(['user', 'student'])
                ->when($request->integer('user'), fn ($q, $u) => $q->where('user_id', $u))
                ->when($type, fn ($q) => $q->where('subject_type', $type))
                ->when($action, fn ($q) => $q->where('action', $action))
                ->when($request->query('date'), fn ($q, $d) => $q->whereDate('created_at', $d))
                ->latest('id')->paginate(40)->withQueryString(),
            'users' => User::orderBy('name')->get(),
            'type' => $type,
            'action' => $action,
        ]);
    }
}
