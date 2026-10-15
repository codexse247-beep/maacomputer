<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** Key/value settings edited from Admin › সেটিংস. Placeholders in [brackets] are shown until replaced. */
class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    public const DEFAULTS = [
        'name' => 'মা কম্পিউটার এন্ড ট্রেনিং সেন্টার',
        'name_en' => 'Maa Computer & Training Center',
        'domain' => 'maacomputer.org',
        'area' => 'বারইয়ারহাট পৌরসভা, মীরসরাই, চট্টগ্রাম',
        'address' => '[পূর্ণ ঠিকানা]',
        'phone' => '[মোবাইল নম্বর]',
        'email' => '[ইমেইল ঠিকানা]',
        'hours' => '[শনিবার – বৃহস্পতিবার, সকাল ৯টা – সন্ধ্যা ৭টা]',
        'map_url' => '',
        'about_intro' => 'মা কম্পিউটার এন্ড ট্রেনিং সেন্টার বারইয়ারহাট পৌরসভা, মীরসরাই, চট্টগ্রামে হাতে-কলমে কম্পিউটার প্রশিক্ষণ দেয়। [প্রতিষ্ঠার সাল ও সংক্ষিপ্ত ইতিহাস]',
        'mission' => '[লক্ষ্য লিখুন]',
        'vision' => '[ভিশন লিখুন]',
        'why_us' => 'ব্যবহারিক ক্লাস, ছোট ব্যাচ, সনদ ও ক্যারিয়ার গাইডলাইন [আসল তথ্য দিন]',
        'certified_count' => '[সংখ্যা]',
        'privacy' => "## কী তথ্য সংগ্রহ করা হয়\n[এই অংশের বিবরণ আসল নীতি অনুযায়ী লিখুন]\n\n## তথ্য কীভাবে ব্যবহার হয়\n[এই অংশের বিবরণ আসল নীতি অনুযায়ী লিখুন]\n\n## তথ্যের নিরাপত্তা\n[এই অংশের বিবরণ আসল নীতি অনুযায়ী লিখুন]\n\n## ভর্তি ও ফি ফেরত নীতি\n[এই অংশের বিবরণ আসল নীতি অনুযায়ী লিখুন]\n\n## যোগাযোগ\n[এই অংশের বিবরণ আসল নীতি অনুযায়ী লিখুন]",
        'privacy_date' => '[কার্যকর তারিখ]',
    ];

    public static function values(): array
    {
        return Cache::rememberForever('settings', fn () => array_merge(self::DEFAULTS, static::query()->pluck('value', 'key')->filter(fn ($v) => $v !== null)->all()));
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget('settings');
    }
}
