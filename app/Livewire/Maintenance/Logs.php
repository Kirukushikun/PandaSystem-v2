<?php

namespace App\Livewire\Maintenance;

use App\Models\AccessLog;
use App\Models\PanRequest;
use App\Models\PanReturn;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Read-only lenses. The access log is the auth table the login controller
 * writes; the audit trail is DERIVED from workflow data (pan_returns carry
 * every backward move with actor + reason; filings close the cycle) — the
 * workflow tables are the record, no separate audit store to drift from them.
 */
#[Layout('layouts.app')]
#[Title('Logs & Audit — PANDA')]
class Logs extends Component
{
    public function exportCsv()
    {
        $logs = AccessLog::latest()->get();

        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['timestamp', 'email', 'success', 'ip_address', 'user_agent']);
            foreach ($logs as $log) {
                fputcsv($out, [
                    $log->created_at->toDateTimeString(), $log->email,
                    $log->success ? 'success' : 'failed', $log->ip_address, $log->user_agent,
                ]);
            }
            fclose($out);
        }, 'panda-access-log-'.now()->format('Y-m-d').'.csv');
    }

    public function render()
    {
        $returns = PanReturn::with(['panRequest', 'returnedBy'])->latest('id')->limit(12)->get()
            ->map(fn (PanReturn $return) => [
                'at' => $return->created_at,
                'module' => match ($return->action) {
                    'return_to_requestor' => 'Div Head',
                    'dispute' => 'Div Head',
                    'return_to_preparer' => 'HR Approve',
                    'reject_final' => 'Final',
                    default => 'HR Prep', // void, mark_unserved
                },
                'text' => "{$return->returnedBy->name} — ".str_replace('_', ' ', $return->action)
                    ." on {$return->panRequest->reference}: \"{$return->reason}\"",
            ]);

        $filings = PanRequest::whereNotNull('filed_at')->latest('filed_at')->limit(6)->get()
            ->map(fn (PanRequest $pan) => [
                'at' => $pan->filed_at,
                'module' => 'HR Prep',
                'text' => "{$pan->reference} ({$pan->employee->name}, {$pan->action_type->label()}) filed",
            ]);

        return view('livewire.maintenance.logs', [
            'accessLogs' => AccessLog::latest()->limit(30)->get(),
            'audit' => $returns->concat($filings)->sortByDesc('at')->take(15)->values(),
        ]);
    }
}
