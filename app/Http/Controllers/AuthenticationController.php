<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

/**
 * App-to-app login (org standard): a trusted internal system redirects here with
 * an encrypted user ID — no password flow. Both systems must share APP_KEY.
 */
class AuthenticationController extends Controller
{
    public function app_login(?string $id = null)
    {
        if (Auth::check()) {
            return redirect()->intended(Auth::user()->landingRoute());
        }

        try {
            $decryptedId = Crypt::decryptString($id);
        } catch (\Exception $e) {
            return 'Login Error [0]. Invalid or tampered ID.';
        }

        $user = User::find($decryptedId);

        if (! $user) {
            return 'Login Error [2]. No access to this system.';
        }

        if (Auth::loginUsingId($user->id)) {
            return redirect()->intended(Auth::user()->landingRoute());
        }

        return 'Login Error [1]. System login error.';
    }
}
