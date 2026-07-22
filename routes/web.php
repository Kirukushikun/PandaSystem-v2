<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\LoginController;
use App\Models\PanRequest;
use Illuminate\Support\Facades\Gate;
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

    Route::middleware('can:requestor')->group(function () {
        Route::get('/requests', App\Livewire\Requestor\Index::class)->name('requests.index');
        Route::get('/requests/create', App\Livewire\Requestor\Form::class)->name('requests.create');
        Route::get('/requests/{pan}/edit', App\Livewire\Requestor\Form::class)->name('requests.edit');
        Route::get('/requests/{pan}', App\Livewire\Requestor\Show::class)->name('requests.show');
    });

    Route::middleware('can:division_head')->group(function () {
        Route::get('/division', App\Livewire\DivisionHead\Queue::class)->name('division.queue');
        Route::get('/division/{pan}', App\Livewire\DivisionHead\Show::class)->name('division.show');
    });

    Route::middleware('can:hr_preparer')->group(function () {
        Route::get('/preparation', App\Livewire\HrPreparation\Queue::class)->name('preparation.queue');
        Route::get('/preparation/{pan}/edit', App\Livewire\HrPreparation\PrepareForm::class)->name('preparation.edit');
        Route::get('/preparation/{pan}', App\Livewire\HrPreparation\Show::class)->name('preparation.show');
        Route::get('/employees', App\Livewire\HrPreparation\Employees::class)->name('employees.index');
        Route::get('/employees/{employee}/pans', App\Livewire\HrPreparation\EmployeeHistory::class)->name('employees.history');
    });

    Route::get('/hr-approval', App\Livewire\HrApprover\Queue::class)->middleware('can:hr_approver')->name('hr-approval.queue');
    Route::get('/hr-approval/{pan}', App\Livewire\HrApprover\Show::class)->middleware('can:hr_approver')->name('hr-approval.show');
    Route::get('/final-approval', App\Livewire\FinalApprover\Queue::class)->middleware('can:final_approver')->name('final-approval.queue');
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
                $response = Illuminate\Support\Facades\Http::withHeaders(['x-api-key' => config('services.user_api.key')])
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
                        return ['id_decrypts_to' => Illuminate\Support\Facades\Crypt::decryptString($first['id'] ?? '')];
                    } catch (Throwable) {
                        return 'FAILED — APP_KEY mismatch with the auth system, or id is not encrypted';
                    }
                })(),
                'raw' => $body,
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        });

        Route::get('/admin/users', App\Livewire\Admin\Users::class)->name('admin.users');
        Route::get('/admin/users/{user}', App\Livewire\Admin\UserAccess::class)->name('admin.users.access');
        Route::get('/admin/employees', App\Livewire\Admin\Employees::class)->name('admin.employees');

        // Mockup's Maintenance subtabs are separate routes (per CLAUDE.md UI contract)
        Route::redirect('/maintenance', '/maintenance/logs');
        Route::get('/maintenance/logs', App\Livewire\Maintenance\Logs::class)->name('maintenance.logs');
        Route::get('/maintenance/reference', App\Livewire\Maintenance\ReferenceValues::class)->name('maintenance.reference');
        Route::get('/maintenance/backups', App\Livewire\Maintenance\Backups::class)->name('maintenance.backups');
        Route::get('/maintenance/backups/{file}/download', function (string $file) {
            $path = App\Services\BackupService::DIR.'/'.basename($file); // no traversal
            abort_unless(Storage::exists($path), 404);

            return Storage::download($path);
        })->name('maintenance.backups.download');
        Route::get('/maintenance/danger', App\Livewire\Maintenance\DangerZone::class)->name('maintenance.danger');
    });

    // Attachment + print: policy-checked per record — these are the direct-link
    // entry points v1 left open. Looked up by reference, 404 when unknown.
    Route::get('/pan/{pan}/attachment/{attachment}', function (string $pan, int $attachment) {
        $panRequest = PanRequest::where('reference', $pan)->firstOrFail();
        Gate::authorize('view', $panRequest);

        $file = $panRequest->attachments()->find($attachment); // scoped — can't reach another PAN's file by id
        abort_unless($file && Storage::exists($file->path), 404);

        return Storage::download($file->path, $file->original_name);
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
    Route::get('/users/{user}/esign', function (App\Models\User $user) {
        abort_unless($user->esign_path && Storage::exists($user->esign_path), 404);

        return Storage::response($user->esign_path);
    })->name('user.esign');

    Route::get('/help/glossary', App\Livewire\Help\Glossary::class)->name('help.glossary');
});
