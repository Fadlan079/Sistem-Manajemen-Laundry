<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'phone'    => 'required|string|max:20',
            'address'  => 'required|string|max:500',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);     

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
            'role'     => 'pelanggan', // role dikunci di server-side
        ]);

        // Link existing guest orders with the same phone number to this new user
        \App\Models\Order::whereNull('user_id')
            ->where('customer_phone', $user->phone)
            ->update(['user_id' => $user->id]);

        if (!empty($request->address)) {
            $user->addresses()->create([
                'label'      => 'Alamat Utama',
                'address'    => $request->address,
                'is_default' => true,
            ]);
        }

        // event(new Registered($user));

        return redirect(route('login'))->with('status', 'Pendaftaran berhasil. Silakan masuk untuk mulai menggunakan layanan kami.');
    }
}
