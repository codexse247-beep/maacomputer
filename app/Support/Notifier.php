<?php

namespace App\Support;

use App\Jobs\SendMessage;
use App\Models\MessageLog;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Support\Facades\Mail;

/**
 * Emails to students (the centre does not use SMS). Switched on and worded in Admin › সেটিংস › ইমেইল.
 * Works with any SMTP account, e.g. a Gmail address with an "app password".
 */
class Notifier
{
    /** Events with an email: key => [label, subject]. */
    public const EVENTS = [
        'admission' => ['ভর্তি সম্পন্ন (আইডি ও পাসওয়ার্ড)', 'ভর্তি সম্পন্ন — পোর্টালের আইডি ও পাসওয়ার্ড'],
        'payment' => ['পেমেন্ট গ্রহণ', 'পেমেন্ট গ্রহণের রসিদ'],
        'due' => ['কিস্তির রিমাইন্ডার', 'কিস্তি পরিশোধের রিমাইন্ডার'],
        'online_approved' => ['অনলাইন পেমেন্ট গৃহীত', 'অনলাইন পেমেন্ট গৃহীত হয়েছে'],
        'online_rejected' => ['অনলাইন পেমেন্ট বাতিল', 'অনলাইন পেমেন্ট যাচাই করা যায়নি'],
        'notice' => ['নোটিশ (নোটিশ পাতায় বেছে নিলে)', 'নতুন নোটিশ'],
    ];

    public const PLACEHOLDERS = '{center} {name} {id} {password} {amount} {due} {date} {receipt} {trx} {reason} {title} {body} {course} {link}';

    /** Email an event's message to a student. Returns false when it can't go (email off, or no address). */
    public static function toStudent(Student $student, string $event, array $vars = []): bool
    {
        $set = Setting::values();
        if (($set['notify_'.$event] ?? '0') !== '1' || ! self::enabled()) {
            return false;
        }

        $vars += [
            'center' => $set['name'],
            'name' => $student->name_bn,
            'id' => $student->student_code,
            'course' => $student->batch?->department?->name ?? '',
            'due' => bn(number_format($student->totalDue())),
            'link' => route('login'),
        ];
        $text = self::fill($set['tpl_'.$event] ?? '', $vars);
        if (trim($text) === '') {
            return false;
        }
        $subject = $set['name'].' — '.self::fill(self::EVENTS[$event][1] ?? '', $vars).(isset($vars['title']) && $event === 'notice' ? ': '.$vars['title'] : '');

        if (! $student->email) {
            MessageLog::create(['channel' => 'email', 'event' => $event, 'to' => '—', 'message' => $subject, 'status' => 'skipped', 'response' => 'স্টুডেন্টের ইমেইল ঠিকানা নেই', 'student_id' => $student->id]);

            return false;
        }

        // The log never keeps a password.
        $logText = isset($vars['password']) ? self::fill($set['tpl_'.$event], ['password' => '******'] + $vars) : $text;
        SendMessage::dispatch($student->email, $subject, $text, $logText, $event, $student->id);

        return true;
    }

    public static function fill(string $template, array $vars): string
    {
        return strtr($template, collect($vars)->mapWithKeys(fn ($v, $k) => ['{'.$k.'}' => (string) $v])->all());
    }

    public static function sendEmail(string $to, string $subject, string $text, ?string $logText = null, ?string $event = null, ?int $studentId = null): MessageLog
    {
        $log = ['channel' => 'email', 'event' => $event, 'to' => $to, 'message' => $logText ?? $text, 'student_id' => $studentId];
        if (! self::configureMail()) {
            return MessageLog::create($log + ['status' => 'skipped', 'response' => 'ইমেইল চালু নেই']);
        }

        try {
            Mail::raw($text, fn ($m) => $m->to($to)->subject($subject));

            return MessageLog::create($log + ['status' => 'sent']);
        } catch (\Throwable $e) {
            return MessageLog::create($log + ['status' => 'failed', 'response' => mb_substr($e->getMessage(), 0, 500)]);
        }
    }

    public static function enabled(): bool
    {
        $set = Setting::values();

        return ($set['mail_enabled'] ?? '0') === '1' && filled($set['mail_host'] ?? null) && filled($set['mail_username'] ?? null);
    }

    /** Point Laravel's mailer at the SMTP account from Settings. False when email is switched off. */
    public static function configureMail(): bool
    {
        if (! self::enabled()) {
            return false;
        }
        if (app()->environment('testing')) {
            return true; // tests use the array mailer from phpunit.xml
        }

        $set = Setting::values();
        $port = (int) ($set['mail_port'] ?: 587);
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $set['mail_host'],
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.scheme' => $port === 465 ? 'smtps' : 'smtp',
            'mail.mailers.smtp.username' => $set['mail_username'],
            'mail.mailers.smtp.password' => $set['mail_password'],
            'mail.from.address' => $set['mail_from'] ?: $set['mail_username'],
            'mail.from.name' => $set['name_en'] ?: $set['name'],
        ]);
        app('mail.manager')->forgetMailers();

        return true;
    }
}
