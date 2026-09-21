<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetUserPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class ResetUserPasswordController extends Controller
{
    /**
     * Reset the given user's password on behalf of an admin.
     */
    public function __invoke(ResetUserPasswordRequest $request, User $user): RedirectResponse
    {
        $user->update($request->safe()->only('password'));

        return redirect()->route('admin.dashboard')->with('status', 'password-reset');
    }
}
