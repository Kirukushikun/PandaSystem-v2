<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\LoginController;
use App\Livewire\Admin\UserAccess;
use App\Livewire\Admin\Users;
use App\Livewire\Dev\LegacyPeekIndex;
use App\Livewire\Dev\LegacyPeekShow;
use App\Livewire\DivisionHead\Queue;
use App\Livewire\Help\Glossary;
use App\Livewire\HrPreparation\EmployeeHistory;
use App\Livewire\HrPreparation\Employees;
use App\Livewire\HrPreparation\PrepareForm;
use App\Livewire\Maintenance\Backups;
use App\Livewire\Maintenance\DangerZone;
use App\Livewire\Maintenance\Logs;
use App\Livewire\Maintenance\ReferenceValues;
use App\Livewire\Requestor\Form;
use App\Livewire\Requestor\Index;
use App\Livewire\Requestor\Show;
use App\Models\PanRequest;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Public — org-standard external authentication (authentication-implementation-guide.md)
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'postLogin'])->name('login.post');
Route::get('/app-login/{id}', [AuthenticationController::class, 'app_login'])->name('app.login');

// Everything else requires a signed-in user; each module group additionally
// requires its stage gate, and per-record rules live in the policies.
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Role-aware landing — a one-role account must never land on a module it can't open
    Route::get('/', fn () => redirect(auth()->user()->landingRoute()))->name('home');

    // Dev-only, hard-pinned to user id 61 inside each component's mount() — not a
    // role/permission, so deliberately not gated by a can: group here. Not linked
    // from any nav. See project-overview/legacy-peek-tool-plan.md.
    Route::get('/dev/legacy-peek', LegacyPeekIndex::class)->name('dev.legacy-peek');
    Route::get('/dev/legacy-peek/{employeeNo}', LegacyPeekShow::class)->name('dev.legacy-peek.show');

    Route::middleware('can:requestor')->group(function () {
        Route::get('/requests', Index::class)->name('requests.index');
        Route::get('/requests/create', Form::class)->name('requests.create');
        Route::get('/requests/{pan}/edit', Form::class)->name('requests.edit');
        Route::get('/requests/{pan}', Show::class)->name('requests.show');
    });

    Route::middleware('can:division_head')->group(function () {
        Route::get('/division', Queue::class)->name('division.queue');
        Route::get('/division/{pan}', App\Livewire\DivisionHead\Show::class)->name('division.show');
    });

    Route::middleware('can:proxy_approver')->group(function () {
        Route::get('/proxy-approval', App\Livewire\ProxyApprover\Queue::class)->name('proxy-approval.queue');
    });

    Route::middleware('can:hr_preparer')->group(function () {
        Route::get('/preparation', App\Livewire\HrPreparation\Queue::class)->name('preparation.queue');
        Route::get('/preparation/{pan}/edit', PrepareForm::class)->name('preparation.edit');
        Route::get('/preparation/{pan}', App\Livewire\HrPreparation\Show::class)->name('preparation.show');
        Route::get('/employees', Employees::class)->name('employees.index');
        // Named {employeeNo}, not {employee} — the component's public $employee
        // property (typed Employee) would otherwise collide with Livewire's
        // implicit route-model-binding-by-property-name and 404 on a non-numeric key.
        Route::get('/employees/{employeeNo}/pans', EmployeeHistory::class)->name('employees.history');
    });

    Route::get('/hr-approval', App\Livewire\HrApprover\Queue::class)->middleware('can:hr_approver')->name('hr-approval.queue');
    Route::get('/hr-approval/{pan}', App\Livewire\HrApprover\Show::class)->middleware('can:hr_approver')->name('hr-approval.show');
    Route::get('/final-approval', App\Livewire\FinalApprover\Queue::class)->middleware('can:final_approver')->name('final-approval.queue');
    Route::middleware('can:final_approver')->group(function () {
        Route::get('/final-approval/employees', App\Livewire\FinalApprover\Employees::class)->name('final-approval.employees.index');
        // Named {employeeNo}, not {employee} — see the matching comment on employees.history above.
        Route::get('/final-approval/employees/{employeeNo}/pans', App\Livewire\FinalApprover\EmployeeHistory::class)->name('final-approval.employees.history');
    });
    Route::get('/final-approval/{pan}', App\Livewire\FinalApprover\Show::class)->middleware('can:final_approver')->name('final-approval.show');

    Route::middleware('can:admin')->group(function () {
        // TEMPORARY — inspect the raw User Listing API response while wiring it up.
        // Admin-only + non-production. DELETE before go-live.
        Route::get('/debug/user-api', function () {
            abort_if(app()->isProduction(), 404);

            if (config('services.user_api.endpoint') === '') {
                return response()->json(['error' => 'USER_API_ENDPOINT is empty in .env']);
            }

            try {
                $response = Http::withHeaders(['x-api-key' => config('services.user_api.key')])
                    ->withOptions(['verify' => storage_path('cacert.pem')])
                    ->timeout(10)->connectTimeout(5)
                    ->post(config('services.user_api.endpoint'));
            } catch (Throwable $e) {
                return response()->json(['error' => get_class($e).': '.$e->getMessage()], 502);
            }

            $body = $response->json() ?? $response->body();
            $first = data_get($body, 'data.0') ?? (is_array($body) ? ($body[0] ?? null) : null);

            return response()->json([
                'status' => $response->status(),
                'first_record_decrypt_check' => $first === null ? 'no records' : (function () use ($first) {
                    try {
                        return ['id_decrypts_to' => Crypt::decryptString($first['id'] ?? '')];
                    } catch (Throwable) {
                        return 'FAILED — APP_KEY mismatch with the auth system, or id is not encrypted';
                    }
                })(),
                'raw' => $body,
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        });

        Route::get('/admin/users', Users::class)->name('admin.users');
        Route::get('/admin/users/{user}', UserAccess::class)->name('admin.users.access');

        // Mockup's Maintenance subtabs are separate routes (per CLAUDE.md UI contract)
        Route::redirect('/maintenance', '/maintenance/logs');
        Route::get('/maintenance/logs', Logs::class)->name('maintenance.logs');
        Route::get('/maintenance/reference', ReferenceValues::class)->name('maintenance.reference');
        Route::get('/maintenance/backups', Backups::class)->name('maintenance.backups');
        Route::get('/maintenance/backups/{file}/download', function (string $file) {
            $disk = Storage::disk('backups');
            $path = basename($file); // no traversal
            abort_unless($disk->exists($path), 404);

            return $disk->download($path);
        })->name('maintenance.backups.download');
        Route::get('/maintenance/danger', DangerZone::class)->name('maintenance.danger');
        Route::get('/maintenance/proxy-approver', App\Livewire\Maintenance\ProxyApproverSettings::class)->name('maintenance.proxy-approver');
    });

    // Roster CRUD: admins and HR Preparers both — see manage_roster gate.
    Route::get('/admin/employees', App\Livewire\Admin\Employees::class)
        ->middleware('can:manage_roster')->name('admin.employees');

    // Legacy Records: HR Head and Final Approver only — never a plain HR Preparer.
    // Looked up by employee_no, attachment scoped through the relation (never a
    // bare EmployeeAttachment::find()) — same v1-open-door lesson as pan.attachment.
    Route::get('/employees/{employeeNo}/legacy-records/{attachment}', function (string $employeeNo, int $attachment) {
        abort_unless(auth()->user()->is_hr_head || auth()->user()->is_final_approver, 403);

        $employee = \App\Models\Employee::where('employee_no', $employeeNo)->firstOrFail();
        $file = $employee->employeeAttachments()->find($attachment);
        abort_unless($file && Storage::exists($file->path), 404);

        return Storage::response($file->path, $file->original_name);
    })->name('employees.legacy-record');

    // Attachment + print: policy-checked per record — these are the direct-link
    // entry points v1 left open. Looked up by reference, 404 when unknown.
    Route::get('/pan/{pan}/attachment/{attachment}', function (string $pan, int $attachment) {
        $panRequest = PanRequest::where('reference', $pan)->firstOrFail();
        Gate::authorize('view', $panRequest);

        $file = $panRequest->attachments()->find($attachment); // scoped — can't reach another PAN's file by id
        abort_unless($file && Storage::exists($file->path), 404);

        return Storage::response($file->path, $file->original_name);
    })->name('pan.attachment');

    Route::get('/pan/{pan}/print', function (string $pan) {
        $panRequest = PanRequest::where('reference', $pan)
            ->with(['employee.department', 'form.preparedBy', 'divisionHead', 'hrApprover', 'finalApprover'])
            ->firstOrFail();
        Gate::authorize('view', $panRequest);
        abort_if($panRequest->form === null, 404); // nothing to print until HR has prepared it

        return view('pan-print', ['pan' => $panRequest]);
    })->name('pan.print');

    // Participant e-signatures for the print view (private disk; viewer already
    // passed the print policy — the image route just requires a signed-in user).
    Route::get('/users/{user}/esign', function (User $user) {
        abort_unless($user->esign_path && Storage::exists($user->esign_path), 404);

        return Storage::response($user->esign_path);
    })->name('user.esign');

    Route::get('/help/glossary', Glossary::class)->name('help.glossary');
});
