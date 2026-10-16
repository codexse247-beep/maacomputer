{{--
    "Sign in with Google" button following Google's branding guidelines
    (developers.google.com/identity/branding-guidelines): official unmodified "G" logo, light theme
    (#FFFFFF fill, #747775 1px border, #1F1F1F text), Roboto Medium 14px/20px, 40px tall,
    12px side padding, 10px between logo and text, Google's own wording in the visitor's language.
    Shown only once Google login is set up in Settings. Needs $who: staff | student
--}}
@if (\App\Http\Controllers\GoogleLoginController::enabled())
    @once
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@500&display=swap" rel="stylesheet">
    <style>
    .gsi-btn{display:flex;align-items:center;justify-content:center;gap:10px;height:40px;padding:0 12px;border:1px solid #747775;border-radius:4px;background:#FFFFFF;color:#1F1F1F;
        font-family:Roboto,'Hind Siliguri',arial,sans-serif;font-size:14px;font-weight:500;line-height:20px;letter-spacing:.25px;text-decoration:none;position:relative;overflow:hidden;cursor:pointer}
    .gsi-btn::after{content:"";position:absolute;inset:0;background:#303030;opacity:0;transition:opacity .218s}
    .gsi-btn:hover::after{opacity:.08}
    .gsi-btn:focus-visible::after,.gsi-btn:active::after{opacity:.12}
    .gsi-btn:hover{box-shadow:0 1px 2px 0 rgba(60,64,67,.30),0 1px 3px 1px rgba(60,64,67,.15)}
    .gsi-btn svg{width:20px;height:20px;flex:none}
    </style>
    @endonce
    <div style="display:flex;align-items:center;gap:12px;color:#8B958E;font-size:14px"><span style="flex:1;height:1px;background:#DFE5DB"></span>{{ __('অথবা') }}<span style="flex:1;height:1px;background:#DFE5DB"></span></div>
    <a class="gsi-btn" href="{{ route('google.redirect', $who) }}">
        <svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/><path fill="none" d="M0 0h48v48H0z"/></svg>
        <span>{{ __('Google দিয়ে সাইন-ইন করুন') }}</span>
    </a>
@endif
