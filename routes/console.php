<?php

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
