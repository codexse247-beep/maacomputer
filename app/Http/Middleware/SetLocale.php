<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Public website language: ?lang=en / ?lang=bn, remembered in the session. Admin and portal stay Bangla. */
class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        if (in_array($request->query('lang'), ['bn', 'en'], true)) {
            $request->session()->put('lang', $request->query('lang'));
        }
        app()->setLocale($request->session()->get('lang', 'bn'));

        return $next($request);
    }
}
