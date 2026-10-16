<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Settings and staff accounts are for the "অ্যাডমিন" role only.
        Gate::define('admin', fn (User $user) => $user->isAdmin());
        // Parts of the panel per role (User::AREAS), e.g. @can('area', 'fees').
        Gate::define('area', fn (User $user, string $area) => $user->canAccess($area));

        // Staff password-reset email: Bangla text and our own reset page.
        ResetPassword::createUrlUsing(fn (User $user, string $token) => route('staff.password.reset', ['token' => $token, 'email' => $user->email]));
        ResetPassword::toMailUsing(fn (User $user, string $token) => (new MailMessage)
            ->subject(setting('name').' — পাসওয়ার্ড বদলানোর লিংক')
            ->view(['html' => 'emails.message', 'text' => 'emails.message-text'], [
                'title' => 'পাসওয়ার্ড বদলানোর লিংক',
                'icon' => 'password',
                'body' => "প্রিয় {$user->name},\n\nআপনার অ্যাডমিন অ্যাকাউন্টের পাসওয়ার্ড বদলানোর অনুরোধ পেয়েছি। নিচের বোতামে চাপ দিয়ে নতুন পাসওয়ার্ড দিন।\n\nলিংকটি ৬০ মিনিট কাজ করবে। আপনি অনুরোধ না করে থাকলে কিছু করার দরকার নেই।",
                'button' => ['নতুন পাসওয়ার্ড দিন', route('staff.password.reset', ['token' => $token, 'email' => $user->email])],
                'preheader' => 'পাসওয়ার্ড বদলানোর লিংক — ৬০ মিনিট কাজ করবে',
            ]));
    }
}
