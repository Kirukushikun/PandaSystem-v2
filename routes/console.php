<?php

use Illuminate\Support\Facades\Schedule;

// Allowance expiry reminders (pan_forms.doe_to) — daily, morning before work starts.
Schedule::command('panda:expiry-reminders')->dailyAt('07:00');
