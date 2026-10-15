<?php

// Contact details shown on the public website. Set them in .env (see ACADEMY_* keys).
return [
    'address' => env('ACADEMY_ADDRESS', '[পূর্ণ ঠিকানা]'),
    'phone' => env('ACADEMY_PHONE', '[মোবাইল নম্বর]'),
    'email' => env('ACADEMY_EMAIL', '[ইমেইল ঠিকানা]'),
    'hours' => env('ACADEMY_HOURS', '[খোলার সময়]'),
];
