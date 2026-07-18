<?php

namespace App\Console\Commands;

use App\Enums\PanStatus;
use App\Models\PanForm;
use App\Models\User;
use App\Notifications\PanActivity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Allowances with an effectivity end date get expiry reminders (the doe_to
 * column exists for exactly this). Scheduled daily; each preparer is reminded
 * once per PAN.
 */
class SendExpiryReminders extends Command
{
    protected $signature = 'panda:expiry-reminders {--days=7 : Look-ahead window in days}';

    protected $description = 'Notify HR preparers about PANs whose effectivity ends within the window';

    public function handle(): int
    {
        $window = (int) $this->option('days');

        $expiring = PanForm::query()
            ->whereNotNull('doe_to')
            ->whereBetween('doe_to', [today(), today()->addDays($window)])
            ->whereHas('panRequest', fn ($q) => $q->whereIn('status', [
                PanStatus::Approved->value, PanStatus::Served->value, PanStatus::Filed->value,
            ]))
            ->with('panRequest.employee')
            ->get();

        $preparers = User::where('is_hr_preparer', true)->get();
        $sent = 0;

        foreach ($expiring as $form) {
            $pan = $form->panRequest;

            foreach ($preparers as $preparer) {
                $already = $preparer->notifications()
                    ->where('data', 'like', '%"reference":"'.$pan->reference.'"%')
                    ->where('data', 'like', '%"context":"Expiry reminder"%')
                    ->exists();
                if ($already) {
                    continue;
                }

                $preparer->notify(new PanActivity(
                    'Allowance expiring soon',
                    "{$pan->employee->name}'s {$pan->action_type->label()} ({$pan->reference}) ends {$form->doe_to->format('M j, Y')}.",
                    $pan->reference,
                    'Expiry reminder',
                ));
                $sent++;
            }
        }

        $this->info("{$expiring->count()} expiring PAN(s) in the next {$window} day(s); {$sent} reminder(s) sent.");

        return self::SUCCESS;
    }
}
