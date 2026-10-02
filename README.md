# Neat Handwriting

A web app for running handwriting and phonics classes: students and parents, batches and schedules, attendance, progress, and fees. It works on phone, iPad and desktop, and can be installed to the home screen as an app (PWA).

## Stack

- **Laravel 12** (PHP 8.3) with **MySQL** in production (SQLite locally and in tests)
- **Livewire 4** + Alpine.js for app-like screens without a separate frontend
- **Tailwind CSS 4**, built with Vite
- Runs on **Hostinger shared/Business hosting**, see [DEPLOY.md](DEPLOY.md)

## What's in it so far

- Sign-in for the teacher (owner account created from `.env`)
- Database tables for the whole MVP: students, parents, courses/levels/skills, batches and schedules, enrollments, class sessions and attendance, skill progress, assessments, handwriting samples, fee plans, invoices and payments
- Starter curriculum (Print, Cursive, Phonics) and example fee plans
- Screens: **Today** dashboard, **Students** (list, search, add, edit), **Batches** (list), **Fees due** (with WhatsApp reminder links)
- Responsive layout: bottom tab bar on phones and iPad portrait, sidebar on iPad landscape and desktop
- Installable PWA with an offline page

Next up, in order: batch and schedule editing, attendance, progress and samples, then invoices and payments.

## Run it locally

You need PHP 8.3, Composer and Node 20+. On Mac or Windows, [Laravel Herd](https://herd.laravel.com) installs PHP and Composer in one go.

```bash
git clone https://github.com/ashokmsc4/neat-handwriting.git
cd neat-handwriting
composer install
cp .env.example .env        # set OWNER_EMAIL / OWNER_PASSWORD
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed  # creates tables, owner login and starter curriculum
npm install && npm run dev  # keep running for live CSS changes
php artisan serve           # open http://localhost:8000
```

Run the tests with `php artisan test`.

## Changing the UI

Hostinger shared hosting has no Node, so the built CSS/JS in `public/build` is committed. After changing any Blade view, CSS or JS, run `npm run build` and commit `public/build` along with your change.
