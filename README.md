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
- Screens: **Today** dashboard, **Students** (list, search, add, edit, and a profile with attendance history, a tap-to-update skill checklist and handwriting photos with a before/after view), **Batches** (add, edit, weekly schedule, assign students), **Attendance** (tap a class on Today, everyone starts present, mark absent/late/excused, add a class note), **Fees** (monthly invoices created automatically from each batch's fee plan, extra charges with discounts, mark paid by cash/UPI/bank, part payments, printable receipts, WhatsApp reminders and receipts)
- **Progress**: monthly check with a 1 to 5 score per skill and a history with averages
- **Dashboard**: active students, attendance this month, fees outstanding and collected, birthdays this week
- **Reports** (More → Reports): monthly attendance, collections chart, attendance by batch, CSV downloads of students, attendance, payments and outstanding fees
- **Settings** (More): class name/phone/address (shown on receipts), fee due day, receipt prefix, courses/levels/skills editor, fee plans, account and password
- **Parent registration link** (More → Registration link): a public form parents fill in from a WhatsApp link; new sign-ups show on Today and Students and become students with one tap. The link can be closed or replaced.
- **Backups**: nightly zip of all data and photos (last 14 kept), a "Back up now" button and downloads in More → Backups
- Responsive layout: bottom tab bar on phones and iPad portrait, sidebar on iPad landscape and desktop
- Installable PWA with an offline page

Ideas for later (phase 2): parent portal, report card PDFs, enquiries and trial classes, class pack tracking, offline attendance.

Handwriting photos are shrunk in the browser before upload, stored outside the public folder in `storage/app/private/samples`, and only served to signed-in users.

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
