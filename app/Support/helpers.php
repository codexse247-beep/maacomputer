<?php

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

if (! function_exists('bn')) {
    /** Convert ASCII digits to Bangla digits. */
    function bn($value): string
    {
        return strtr((string) $value, ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯']);
    }
}

if (! function_exists('taka')) {
    /** Format an amount as ৳১২,০০০. */
    function taka($amount): string
    {
        $amount = (int) round((float) $amount);
        $sign = $amount < 0 ? '−' : '';

        return $sign.'৳'.bn(number_format(abs($amount)));
    }
}

if (! function_exists('bn_date')) {
    /** Format a date as ১৩ অক্টোবর ২০২৬ (or without the year). */
    function bn_date($date, bool $withYear = true): string
    {
        if (! $date) {
            return '—';
        }
        $date = $date instanceof CarbonInterface ? $date : Carbon::parse($date);
        $months = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];
        $text = bn($date->day).' '.$months[$date->month - 1];

        return $withYear ? $text.' '.bn($date->year) : $text;
    }
}

if (! function_exists('bn_weekday')) {
    function bn_weekday($date): string
    {
        $date = $date instanceof CarbonInterface ? $date : Carbon::parse($date);

        return ['রবিবার', 'সোমবার', 'মঙ্গলবার', 'বুধবার', 'বৃহস্পতিবার', 'শুক্রবার', 'শনিবার'][$date->dayOfWeek];
    }
}

if (! function_exists('bn_ordinal')) {
    /** ১ম, ২য়, ৩য়, ৪র্থ, ৫ম, ৬ষ্ঠ … */
    function bn_ordinal(int $n): string
    {
        $suffix = match (true) {
            in_array($n, [1, 5, 7, 8, 9, 10], true) => 'ম',
            in_array($n, [2, 3], true) => 'য়',
            $n === 4 => 'র্থ',
            $n === 6 => 'ষ্ঠ',
            default => 'তম',
        };

        return bn($n).$suffix;
    }
}

if (! function_exists('initial')) {
    /** First visible letter of a name, skipping the "মো." prefix. */
    function initial(?string $name): string
    {
        $name = trim(preg_replace('/^(মো\.|মোঃ|md\.?)\s*/iu', '', (string) $name));

        return mb_substr($name, 0, 1) ?: '?';
    }
}

if (! function_exists('logo_url')) {
    function logo_url(): string
    {
        return file_exists(public_path('images/logo.png')) ? asset('images/logo.png') : asset('images/logo.svg');
    }
}

if (! function_exists('setting')) {
    /** A site setting from Admin › সেটিংস (falls back to the default placeholder). */
    function setting(string $key): string
    {
        return (string) (\App\Models\Setting::values()[$key] ?? '');
    }
}

if (! function_exists('grade_for')) {
    /** Bangladesh-style letter grade for a percentage. */
    function grade_for(?float $percent): array
    {
        if ($percent === null) {
            return ['—', 'b-n'];
        }

        return match (true) {
            $percent >= 80 => ['A+', 'b-g'],
            $percent >= 70 => ['A', 'b-g'],
            $percent >= 60 => ['A-', 'b-b'],
            $percent >= 50 => ['B', 'b-b'],
            $percent >= 40 => ['C', 'b-a'],
            default => ['F', 'b-r'],
        };
    }
}
