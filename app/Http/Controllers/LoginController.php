<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use App\Models\User;
use App\Services\LoginLockoutService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Org-standard external authentication (authentication-implementation-guide.md):
 * credentials are never validated locally — the external Auth API decides, then
 * the local users table decides authorization (no matching row = no access).
 */
class LoginController extends Controller
{
    public function __construct(private LoginLockoutService $lockout) {}

    public function showLogin(): View
    {
        return view('login', [
            'turnstileSiteKey' => config('services.turnstile.site_key'),
            'devAccounts' => $this->devAccounts(),
        ]);
    }

    /**
     * One-click credentials for the seeded role accounts — rendered ONLY in
     * AUTH_FAKE dev mode (never in production), where any password signs in.
     */
    private function devAccounts(): array
    {
        if (! config('services.auth_api.fake') || app()->isProduction()) {
            return [];
        }

        return User::orderBy('id')->get()
            ->map(fn (User $u) => [
                'role' => match (true) {
                    $u->is_admin => 'Admin',
                    $u->is_hr_head => 'HR Head',
                    $u->is_dh_head => 'DH Head',
                    $u->is_final_approver => 'Final Approver',
                    $u->is_hr_approver => 'HR Approver',
                    $u->is_hr_preparer => 'HR Preparer',
                    $u->is_division_head => 'Division Head',
                    $u->is_requestor => 'Requestor',
                    default => null,
                },
                'name' => $u->name,
                'email' => $u->email,
            ])
            ->filter(fn (array $a) => $a['role'] !== null)
            ->sortBy(fn (array $a) => array_search($a['role'], [
                'Requestor', 'Division Head', 'HR Preparer', 'HR Head',
                'DH Head', 'HR Approver', 'Final Approver', 'Admin',
            ]))
            ->values()
            ->all();
    }

    public function postLogin(Request $request): RedirectResponse
    {
        $email = $request->input('email');

        // Cloudflare Turnstile — no-op while TURNSTILE_VERIFY=false
        if (config('services.turnstile.verify')) {
            try {
                $verify = Http::asForm()->timeout(5)->connectTimeout(3)
                    ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                        'secret' => config('services.turnstile.secret'),
                        'response' => $request->input('turnstile_token', ''),
                        'remoteip' => $request->ip(),
                    ]);
            } catch (ConnectionException $e) {
                Log::warning('Turnstile verification unreachable', ['error' => $e->getMessage()]);

                return back()->withErrors(['turnstile_token' => 'Human verification service is unreachable. Please try again shortly.'])->withInput();
            }
            if (! ($verify->json('success') ?? false)) {
                return back()->withErrors(['turnstile_token' => 'Human verification failed. Please complete the challenge and try again.'])->withInput();
            }
        }

        if ($this->lockout->isLocked($email)) {
            return back()->withErrors([
                'email' => 'Account temporarily locked. Please try again in 15 minutes.',
            ])->withInput();
        }

        // Dev fake-auth: the provider's Auth API is unreachable outside office hours,
        // so local development signs in seeded users directly. Never active in production.
        if (config('services.auth_api.fake') && ! app()->isProduction()) {
            return $this->fakeLogin($email, $request);
        }

        try {
            $base_uri = config('services.auth_api.base_uri');
            $api_key = config('services.auth_api.api_key');
            $auth_user_api_key = config('services.auth_api.auth_user_api_key');

            // Step 1 — validate credentials against the external Auth API
            $authResponse = Http::withHeaders([
                'Authorization' => 'Bearer '.$api_key,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->withOptions(['verify' => storage_path('cacert.pem')])
                ->timeout(10)->connectTimeout(5)
                ->post($base_uri.'/api/v1/auth/login', [
                    'email' => $email,
                    'password' => $request->input('password'),
                ]);

            if (! $authResponse->successful()) {
                $attempts = $this->lockout->recordFailure($email);
                $this->logAccess($email, false, $request);

                $left = $this->lockout->maxAttempts() - $attempts;
                $message = $authResponse->json()['message'] ?? 'Incorrect email or password.';

                if ($left > 0) {
                    $message .= " {$left} attempt(s) remaining.";
                }

                return back()->withErrors(['email' => $message])->withInput();
            }

            $data = $authResponse->json();
            $email = $data['email'] ?? $email;

            session([
                'auth_token' => $data['token'] ?? null,
                'token_expires' => $data['expires_at'] ?? null,
                'email' => $email,
            ]);

            // Step 2 — resolve the local user ID from the external User API
            $userResponse = Http::withHeaders([
                'x-api-key' => $auth_user_api_key,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->withOptions(['verify' => storage_path('cacert.pem')])
                ->timeout(10)->connectTimeout(5)
                ->get($base_uri.'/api/v1/users/get-user-id', ['email' => $email]);

            if (! $userResponse->successful()) {
                return back()->withErrors([
                    'email' => 'Failed to retrieve user information from the system.',
                ])->withInput();
            }

            $user = User::find($userResponse->json()['id'] ?? null);

            if (! $user) {
                $this->lockout->recordFailure($email);
                $this->logAccess($email, false, $request);

                return back()->withErrors([
                    'email' => 'You are not authorized to access this system.',
                ])->withInput();
            }

            $this->lockout->clear($email);
            $this->logAccess($email, true, $request);
            Auth::loginUsingId($user->id);
            $request->session()->regenerate();

            return redirect()->intended(Auth::user()->landingRoute());

        } catch (ConnectionException $e) {
            // Network-level failure (timeout, DNS, refused) — the API is down, not the user's fault.
            // Don't count this against their lockout: they didn't get a real chance to authenticate.
            $this->logAccess($email, false, $request);
            Log::warning('Auth API unreachable', ['email' => $email, 'error' => $e->getMessage()]);

            return back()->withErrors([
                'email' => 'Authentication service is currently unreachable. Please try again shortly.',
            ])->withInput();
        } catch (\Throwable $e) {
            $this->lockout->recordFailure($email);
            $this->logAccess($email, false, $request);
            Log::error('Auth login flow failed unexpectedly', ['email' => $email, 'error' => $e->getMessage()]);

            return back()->withErrors([
                'email' => 'Authentication service error. Please try again.',
            ])->withInput();
        }
    }

    /** Same outcome as the real flow — local row = access, everything logged. */
    private function fakeLogin(string $email, Request $request): RedirectResponse
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->lockout->recordFailure($email);
            $this->logAccess($email, false, $request);

            return back()->withErrors([
                'email' => 'You are not authorized to access this system.',
            ])->withInput();
        }

        $this->lockout->clear($email);
        $this->logAccess($email, true, $request);
        Auth::loginUsingId($user->id);
        $request->session()->regenerate();

        return redirect()->intended(Auth::user()->landingRoute());
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function logAccess(string $email, bool $success, Request $request): void
    {
        AccessLog::create([
            'email' => $email,
            'success' => $success,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
