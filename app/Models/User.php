<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'role', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use LogsActivity;

    public const ROLES = ['admin' => 'অ্যাডমিন', 'staff' => 'স্টাফ (অফিস)', 'accounts' => 'অ্যাকাউন্টস', 'teacher' => 'শিক্ষক'];

    /**
     * Parts of the admin panel each role may open (the admin opens everything).
     * academics: courses, batches, teachers · students: view students & batches · admissions: admit/edit students, applications
     * fees: payments, expenses, online payments, dues · classes: attendance, results, videos, assignments, quizzes, calendar
     */
    public const AREAS = [
        'staff' => ['academics', 'students', 'admissions', 'fees', 'classes', 'notices', 'reports'],
        'accounts' => ['students', 'fees', 'reports'],
        'teacher' => ['students', 'classes', 'notices'],
    ];

    /** What each role can do, shown next to the role picker in Settings. */
    public const ROLE_HELP = [
        'admin' => 'সব কিছু, সেটিংস ও ব্যবহারকারীসহ',
        'staff' => 'সেটিংস, গ্যালারি ও অ্যাক্টিভিটি লগ ছাড়া সব',
        'accounts' => 'স্টুডেন্ট দেখা, ফি-পেমেন্ট, খরচ ও রিপোর্ট',
        'teacher' => 'স্টুডেন্ট দেখা, উপস্থিতি, ফলাফল, ভিডিও, অ্যাসাইনমেন্ট-কুইজ, নোটিশ, ক্যালেন্ডার',
    ];

    /** Same default as the database column, so a freshly created user has a role before it is reloaded. */
    protected $attributes = ['role' => 'admin'];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function canAccess(string $area): bool
    {
        return $this->isAdmin() || in_array($area, self::AREAS[$this->role] ?? [], true);
    }

    public function activityLabel(): string
    {
        return $this->name.' ('.$this->email.')';
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
