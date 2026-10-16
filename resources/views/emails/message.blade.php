{{--
    Branded HTML email (works in Gmail, Outlook, Yahoo and phone apps): tables + inline styles, PNG icons only.
    Needs: $title, $icon (file in public/images/email), $body (plain text; blank line = new paragraph), optional $button = [label, url], $preheader.
--}}
@php
    $img = fn (string $file) => asset('images/email/'.$file);
    $font = "'Hind Siliguri','Nirmala UI','Segoe UI',Arial,sans-serif";
    // Plain text → safe HTML paragraphs, with links made clickable.
    $paragraphs = collect(preg_split("/\n\s*\n/", trim(str_replace("\r\n", "\n", $body))))->map(function ($p) {
        $html = nl2br(e($p), false);

        return preg_replace('~(https?://[^\s<]+)~u', '<a href="$1" style="color:#176008;font-weight:600;text-decoration:underline;word-break:break-all">$1</a>', $html);
    });
    $contacts = collect([
        ['i-pin', trim(setting('area').(str_contains(setting('address'), '[') ? '' : ', '.setting('address')))],
        ['i-phone', str_contains(setting('phone'), '[') ? null : setting('phone')],
        ['i-mail', str_contains(setting('email'), '[') ? null : setting('email')],
        ['i-globe', setting('domain')],
    ])->filter(fn ($c) => filled($c[1]));
@endphp
<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<title>{{ $title }}</title>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  @media (max-width: 620px) { .card { width: 100% !important; border-radius: 0 !important; } .pad { padding-left: 22px !important; padding-right: 22px !important; } }
  a { color: #176008; }
</style>
</head>
<body style="margin:0;padding:0;background:#EEF4EA;font-family:{{ $font }};">
{{-- Inbox preview line --}}
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent">{{ $preheader ?? $title }}&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#EEF4EA;">
<tr><td align="center" style="padding:28px 12px;">

    <table role="presentation" class="card" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 6px 24px rgba(14,58,5,.08);">

        {{-- Header: logo + centre name --}}
        <tr><td style="background:#0E3A05;padding:22px 32px;" class="pad">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                <td style="padding-right:14px;vertical-align:middle;">
                    <img src="{{ asset('images/logo.png') }}" width="56" height="56" alt="{{ setting('name_en') }}" style="display:block;width:56px;height:56px;border-radius:50%;background:#ffffff;border:2px solid #ffffff;">
                </td>
                <td style="vertical-align:middle;font-family:{{ $font }};">
                    <div style="font-size:18px;font-weight:700;line-height:1.35;color:#ffffff;">{{ setting('name') }}</div>
                    <div style="font-size:12px;font-weight:600;line-height:1.4;color:#B9D9AD;letter-spacing:.02em;">{{ setting('name_en') }}</div>
                </td>
            </tr></table>
        </td></tr>
        <tr><td style="height:5px;background:#C8102E;font-size:0;line-height:0;">&nbsp;</td></tr>

        {{-- Icon + title --}}
        <tr><td align="center" style="padding:34px 32px 6px;" class="pad">
            <img src="{{ $img($icon.'.png') }}" width="64" height="64" alt="" style="display:block;width:64px;height:64px;">
        </td></tr>
        <tr><td align="center" style="padding:12px 32px 4px;font-family:{{ $font }};" class="pad">
            <h1 style="margin:0;font-size:23px;line-height:1.4;font-weight:700;color:#0E3A05;">{{ $title }}</h1>
        </td></tr>

        {{-- Message --}}
        <tr><td style="padding:18px 40px 8px;font-family:{{ $font }};font-size:16px;line-height:1.75;color:#24312A;" class="pad">
            @foreach ($paragraphs as $p)
                <p style="margin:0 0 14px;">{!! $p !!}</p>
            @endforeach
        </td></tr>

        {{-- Button --}}
        @if (! empty($button))
        <tr><td align="center" style="padding:6px 32px 34px;" class="pad">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                <td style="background:#176008;border-radius:12px;">
                    <a href="{{ $button[1] }}" style="display:inline-block;padding:14px 30px;font-family:{{ $font }};font-size:16px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:12px;">{{ $button[0] }} &rarr;</a>
                </td>
            </tr></table>
        </td></tr>
        @else
        <tr><td style="height:20px;font-size:0;line-height:0;">&nbsp;</td></tr>
        @endif

        {{-- Footer: contact details --}}
        <tr><td style="background:#F5F8F3;border-top:1px solid #E1E8DD;padding:22px 32px;font-family:{{ $font }};" class="pad">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                @foreach ($contacts as [$icon2, $text])
                <tr>
                    <td width="26" style="padding:4px 0;vertical-align:top;"><img src="{{ $img($icon2.'.png') }}" width="16" height="16" alt="" style="display:block;width:16px;height:16px;margin-top:3px;"></td>
                    <td style="padding:4px 0;font-size:14px;line-height:1.6;color:#55635A;">
                        @if ($icon2 === 'i-globe')<a href="{{ route('home') }}" style="color:#176008;font-weight:600;text-decoration:none;">{{ $text }}</a>@else{{ $text }}@endif
                    </td>
                </tr>
                @endforeach
            </table>
        </td></tr>
    </table>

    <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:100%;">
        <tr><td align="center" style="padding:16px 20px 0;font-family:{{ $font }};font-size:12px;line-height:1.6;color:#8B958E;">
            এই ইমেইলটি {{ setting('name') }}-এর ওয়েবসাইট থেকে স্বয়ংক্রিয়ভাবে পাঠানো হয়েছে। প্রশ্ন থাকলে অফিসে যোগাযোগ করুন।
        </td></tr>
    </table>

</td></tr>
</table>
</body>
</html>
