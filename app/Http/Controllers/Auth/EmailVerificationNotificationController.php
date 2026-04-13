<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }

    /**
     * Send a new email verification notification for a guest.
     */
    public function resendGuest(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);
        $user = \App\Models\User::where('email', $request->email)->first();

        if ($user && !$user->hasVerifiedEmail()) {
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Exception $e) {
                return back()->withErrors(['email' => 'Gagal mengirim email: ' . $e->getMessage()]);
            }
        }

        return back()->with('status', 'Tautan verifikasi baru telah dikirim ke email Anda.');
    }
}
