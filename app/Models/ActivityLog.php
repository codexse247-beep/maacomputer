<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One line of "who did what" — written by the LogsActivity trait and for logins / settings changes. */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = ['diff' => 'array'];

    public const ACTIONS = [
        'created' => ['যোগ করেছে', 'b-g'],
        'updated' => ['পরিবর্তন করেছে', 'b-b'],
        'deleted' => ['মুছেছে', 'b-r'],
        'login' => ['লগইন করেছে', 'b-n'],
        'approved' => ['অনুমোদন করেছে', 'b-g'],
        'rejected' => ['বাতিল করেছে', 'b-a'],
        'sent' => ['পাঠিয়েছে', 'b-n'],
    ];

    /** Bangla names for logged record types. */
    public const SUBJECTS = [
        'Student' => 'স্টুডেন্ট', 'Payment' => 'পেমেন্ট', 'Batch' => 'ব্যাচ', 'Department' => 'বিভাগ', 'Teacher' => 'শিক্ষক',
        'Notice' => 'নোটিশ', 'CourseVideo' => 'ক্লাস ভিডিও', 'User' => 'ব্যবহারকারী', 'Expense' => 'খরচ', 'OnlinePayment' => 'অনলাইন পেমেন্ট',
        'Assignment' => 'অ্যাসাইনমেন্ট', 'Quiz' => 'কুইজ', 'Event' => 'ক্যালেন্ডার', 'Setting' => 'সেটিংস', 'Assessment' => 'ফলাফল',
    ];

    /** Never store these values in the log. */
    private const SECRET = ['password', 'remember_token', 'sms_api', 'mail_password'];

    private const IGNORED = ['updated_at', 'created_at', 'remember_token'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public static function record(string $action, Model|string $subject, ?array $changes = null, ?string $label = null): void
    {
        // Only actions done by a logged-in person (staff or student); seeders and console commands are not logged.
        $user = auth('web')->user();
        $student = auth('student')->user();
        if (! $user && ! $student) {
            return;
        }

        $type = $subject instanceof Model ? class_basename($subject) : $subject;
        static::create([
            'user_id' => $user?->id,
            'student_id' => $user ? null : $student?->id,
            'action' => $action,
            'subject_type' => $type,
            'subject_id' => $subject instanceof Model ? $subject->getKey() : null,
            'label' => mb_substr($label ?? ($subject instanceof Model && method_exists($subject, 'activityLabel') ? $subject->activityLabel() : '#'.($subject instanceof Model ? $subject->getKey() : '')), 0, 255),
            'diff' => $changes,
            'ip' => request()?->ip(),
        ]);
    }

    /** Changed fields of a just-updated model as [field => [old, new]] (secrets hidden). */
    public static function changesOf(Model $model): array
    {
        $out = [];
        foreach ($model->getChanges() as $key => $new) {
            if (in_array($key, self::IGNORED, true)) {
                continue;
            }
            $old = $model->getOriginal($key);
            $secret = in_array($key, self::SECRET, true) || str_contains($key, 'password');
            $out[$key] = $secret ? ['••••', '••••'] : [self::short($old), self::short($new)];
        }

        return $out;
    }

    private static function short($value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        $value = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);

        return mb_strlen($value) > 120 ? mb_substr($value, 0, 120).'…' : $value;
    }

    public function actorName(): string
    {
        return $this->user?->name ?? ($this->student ? $this->student->name_bn.' (স্টুডেন্ট)' : 'মুছে ফেলা ব্যবহারকারী');
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action][0] ?? $this->action;
    }

    public function actionTone(): string
    {
        return self::ACTIONS[$this->action][1] ?? 'b-n';
    }

    public function subjectLabel(): string
    {
        return self::SUBJECTS[$this->subject_type] ?? $this->subject_type;
    }
}
