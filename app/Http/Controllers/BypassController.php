<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Break-glass admin login (bypass-feature-guide.md): the external Auth API is
 * unavailable outside provider hours, so a trusted operator can authenticate as
 * the designated admin with a shared secret. Disabled while BYPASS_SECRET is
 * empty. Failures redirect silently to /login — scanners get no confirmation
 * the endpoint exists. Every attempt lands in access_logs like a normal login.
 */
class BypassController extends Controller
{
    public function show(Request $request)
    {
        if (Auth::check()) {
            return redirect()->intended(route('requests.index'));
        }

        return view('bypass');
    }

    public function authenticate(Request $request)
    {
        $secret = config('app.bypass_secret');

        if (! $secret || ! hash_equals($secret, (string) $request->input('p'))) {
            $this->logAccess('(bypass)', false, $request);

            return redirect('/login'); // silent failure
        }

        $user = $this->bypassUser();

        if (! $user) {
            $this->logAccess('(bypass)', false, $request);

            return redirect('/login');
        }

        Auth::loginUsingId($user->id);
        $request->session()->regenerate();
        $this->logAccess($user->email, true, $request);

        return redirect()->intended(route('requests.index'));
    }

    /** Configured BYPASS_USER_ID, or the first admin account as a fallback. */
    private function bypassUser(): ?User
    {
        if ($id = config('app.bypass_user_id')) {
            return User::find($id);
        }

        return User::where('is_admin', true)->orderBy('id')->first();
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
