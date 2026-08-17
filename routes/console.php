<?php

use Illuminate\Support\Facades\Schedule;

// Allowance expiry reminders (pan_forms.doe_to) — daily, morning before work starts.
Schedule::command('panda:expiry-reminders')->dailyAt('07:00');

// Daily database backup at end of working hours — local disk + Google Drive
// (config/backup.php), newest 14 days kept on both.
Schedule::command('backup:run --only-db')->dailyAt('18:00');
Schedule::command('backup:clean')->dailyAt('18:30');

// Same time as the backup — access-log entries past 30 days are disposable
// once a backup covering them exists. Manual equivalent: Maintenance → Danger
// Zone → Purge Activity Logs.
Schedule::command('panda:purge-access-logs')->dailyAt('18:00');
