<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PasswordController extends Controller
{
    /**
     * Show the change password form.
     */
    public function edit(): View
    {
        return view('auth.password.edit');
    }

    /**
     * Update the authenticated user's password.
     */
    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update($request->safe()->only('password'));

        return back()->with('status', 'password-updated');
    }
}
