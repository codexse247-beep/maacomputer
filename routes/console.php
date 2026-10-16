<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

Artisan::command('academy:admin {email} {name=অ্যাডমিন}', function (string $email, string $name) {
    $password = Str::password(14, symbols: false);
    $user = User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => $password]);

    $this->info(($user->wasRecentlyCreated ? 'Admin created' : 'Password reset').": {$email}");
    $this->line("Password: {$password}");
})->purpose('Create an admin account (or reset its password) and print a new random password');

// Every morning: email students whose installment is due in N days (Settings) or today. Only when switched on.
Artisan::command('fees:remind', function () {
    if (setting('due_auto') !== '1' || ! \App\Support\Notifier::enabled()) {
        $this->line('Automatic reminders are off.');

        return;
    }
    $days = (int) setting('due_days_before');
    $dates = array_unique([now()->toDateString(), now()->addDays($days)->toDateString()]);
    $already = \App\Models\MessageLog::where('event', 'due')->whereDate('created_at', now())->pluck('student_id')->all();

    $sent = \App\Http\Controllers\Admin\DueController::dueStudents()
        ->filter(fn ($s) => $s->next_installment_date && in_array($s->next_installment_date->toDateString(), $dates, true) && ! in_array($s->id, $already, true))
        ->filter(fn ($s) => \App\Http\Controllers\Admin\DueController::send($s))->count();
    $this->info("Reminders sent: $sent");
})->purpose('Email installment reminders (Settings › ইমেইল › স্বয়ংক্রিয় রিমাইন্ডার)');

\Illuminate\Support\Facades\Schedule::command('fees:remind')->dailyAt('09:00');
