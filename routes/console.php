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
