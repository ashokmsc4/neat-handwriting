<?php

use App\Services\Backup;
use App\Services\Billing;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('fees:bill {month? : Month to bill, e.g. 2026-10 (defaults to this month)}', function (Billing $billing) {
    $month = $this->argument('month') ? Carbon::createFromFormat('Y-m', $this->argument('month')) : now();
    $count = $billing->generateMonthly($month->startOfMonth());

    $this->info("Created {$count} invoice(s) for {$month->format('F Y')}.");
})->purpose('Create monthly fee invoices for students on monthly plans');

// Runs from the Hostinger cron (php artisan schedule:run every minute).
// Daily rather than only on the 1st, so a missed cron run or a late joiner is still billed.
Schedule::command('fees:bill')->dailyAt('06:00');

Artisan::command('backup:run', function (Backup $backup) {
    $this->info('Backup saved: storage/app/private/'.$backup->create());
})->purpose('Back up all data and handwriting photos to a zip file');

Artisan::command('backup:restore {file : Path to a backup zip}', function (Backup $backup) {
    if (! $this->confirm('This replaces ALL current data with the backup. Continue?')) {
        return 1;
    }

    $backup->restore($this->argument('file'));
    $this->info('Restored.');
})->purpose('Replace all data with the contents of a backup zip');

Schedule::command('backup:run')->dailyAt('02:00');
