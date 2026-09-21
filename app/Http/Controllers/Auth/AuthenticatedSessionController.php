<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login form.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Authenticate an active user and start a session.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        $authenticated = Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'status' => 'active',
        ]);

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(self::homeRouteFor($user));
    }

    /**
     * Log the authenticated user out.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Resolve the landing route for the given role.
     */
    public static function homeRouteFor(User $user): string
    {
        return match ($user->role) {
            'admin' => route('admin.dashboard'),
            'kepala' => route('kepala.dashboard'),
            default => route('petugas.dashboard'),
        };
    }
}
