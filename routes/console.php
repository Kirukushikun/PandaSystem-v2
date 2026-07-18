<?php

use Illuminate\Support\Facades\Schedule;

// Allowance expiry reminders (pan_forms.doe_to) — daily, morning before work starts.
Schedule::command('panda:expiry-reminders')->dailyAt('07:00');

// Nightly database backup (storage/app/backups, newest 14 kept).
Schedule::command('panda:backup')->dailyAt('01:00');
