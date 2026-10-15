<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>@yield('title')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v=5">
@stack('head')
</head>
<body>
<div class="noprint" style="display:flex;justify-content:center;gap:10px;padding:16px">
    <button class="btn p" onclick="window.print()">প্রিন্ট / PDF সংরক্ষণ</button>
    <button class="btn" onclick="window.close()">বন্ধ করুন</button>
</div>
@yield('content')
</body>
</html>
