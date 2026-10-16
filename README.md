# মা কম্পিউটার এন্ড ট্রেনিং সেন্টার — Maa Computer & Training Center

Website, student portal and admin panel of Maa Computer & Training Center, Baraiyarhat, Mirsharai, Chattogram.

**Live site:** https://maacomputer.org (also https://www.maacomputer.org) · English: https://maacomputer.org/?lang=en

| Part | Address |
|---|---|
| Public website | https://maacomputer.org |
| Student portal & admin login | https://maacomputer.org/login |
| Certificate check | https://maacomputer.org/verify |
| Sitemap (Google) | https://maacomputer.org/sitemap.xml |

## Features

- **Website** (Bangla, with an English version): courses and fees, online admission, gallery with photo/YouTube lightbox, instructors, FAQ, notices and holidays, certificate verification, contact.
- **Admin panel** with roles (admin, office staff, accounts, teacher) and an activity log: courses, batches, students and admissions, fees and receipts, dues and email reminders, expenses and profit report, bKash/Nagad payment approval, attendance, results, class videos with notes, assignments, MCQ quizzes, calendar, notices, reports, gallery, settings.
- **Student portal:** dashboard, class videos with progress, assignments and quizzes, calendar, fees and online payment, receipts, results, ID card, certificate, profile.

## Running it

Laravel 13 on PHP 8.3 with MariaDB. On the centre's PC it is served by Caddy and php-cgi and reached from the internet through a Cloudflare Tunnel (no open ports); the server scripts live outside this repository.

```sh
composer install
cp .env.example .env        # then set the database and APP_URL
php artisan key:generate
php artisan migrate --seed
php artisan academy:admin you@example.com   # creates an admin and prints its password
```

Background work (emails, the daily fee reminder) needs `php artisan queue:work` and `php artisan schedule:work` running.

## Tests

```sh
php artisan optimize:clear   # tests refuse to run while the live config is cached
php artisan test
php artisan optimize
```
