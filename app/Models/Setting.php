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

        // English website text (empty = the Bangla text is shown)
        'area_en' => 'Baraiyarhat Municipality, Mirsharai, Chattogram', 'address_en' => '', 'hours_en' => '', 'about_intro_en' => '',
        'mission_en' => '', 'vision_en' => '', 'why_us_en' => '', 'privacy_en' => '', 'privacy_date_en' => '',

        // "Log in with Google" (Google Cloud OAuth client); both empty = no Google button
        'google_client_id' => '', 'google_client_secret' => '',

        // Online fee payment (portal) — receiving numbers; a method is offered only when its number is set
        'bkash_number' => '', 'nagad_number' => '', 'rocket_number' => '',
        'pay_note' => 'উপরের নম্বরে "সেন্ড মানি" করুন, তারপর নিচের ফর্মে ট্রানজেকশন আইডি (TrxID) দিন। অফিস যাচাই করলে রসিদ পোর্টালে দেখা যাবে।',

        // Email (SMTP, e.g. a Gmail address with an app password). The centre does not use SMS.
        'mail_enabled' => '0', 'mail_host' => 'smtp.gmail.com', 'mail_port' => '587', 'mail_username' => '', 'mail_password' => '', 'mail_from' => '',

        // Which emails go out, and their words (placeholders: see Notifier::PLACEHOLDERS)
        'notify_admission' => '1', 'tpl_admission' => "প্রিয় {name},\n\n{center}-এ আপনার ভর্তি সম্পন্ন হয়েছে ({course})।\n\nস্টুডেন্ট পোর্টালে লগইন করুন:\nআইডি: {id}\nপাসওয়ার্ড: {password}\nলিংক: {link}\n\nপ্রথমবার লগইনের পর পাসওয়ার্ড বদলে নিন।\n\nধন্যবাদ,\n{center}",
        'notify_payment' => '1', 'tpl_payment' => "প্রিয় {name},\n\nআপনার ৳{amount} পেমেন্ট গ্রহণ করা হয়েছে।\nরসিদ নং: {receipt}\nতারিখ: {date}\nবাকি বকেয়া: ৳{due}\n\nরসিদ পোর্টালে দেখতে ও প্রিন্ট করতে পারবেন: {link}\n\nধন্যবাদ,\n{center}",
        'notify_due' => '1', 'tpl_due' => "প্রিয় {name},\n\nআপনার পরবর্তী কিস্তি ৳{amount} পরিশোধের তারিখ {date}।\nমোট বকেয়া: ৳{due}\n\nঅফিসে অথবা পোর্টাল থেকে বিকাশ/নগদে পরিশোধ করতে পারবেন: {link}\n\nধন্যবাদ,\n{center}",
        'notify_online_approved' => '1', 'tpl_online_approved' => "প্রিয় {name},\n\nআপনার অনলাইন পেমেন্ট ৳{amount} যাচাই করে গ্রহণ করা হয়েছে।\nরসিদ নং: {receipt}\nবাকি বকেয়া: ৳{due}\n\nধন্যবাদ,\n{center}",
        'notify_online_rejected' => '1', 'tpl_online_rejected' => "প্রিয় {name},\n\nআপনার অনলাইন পেমেন্ট (TrxID {trx}) যাচাই করা যায়নি।\nকারণ: {reason}\n\nঅনুগ্রহ করে অফিসে যোগাযোগ করুন।\n\n{center}",
        'notify_notice' => '1', 'tpl_notice' => "প্রিয় {name},\n\n{title}\n\n{body}\n\nপোর্টালে সব নোটিশ দেখুন: {link}\n\n{center}",
        // Automatic installment reminders: every morning, this many days before the date and on the date
        'due_auto' => '0', 'due_days_before' => '2',
    ];

    /** Settings never written to the activity log in clear, and kept when the form field is left blank. */
    public const SECRETS = ['mail_password', 'google_client_secret'];

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
