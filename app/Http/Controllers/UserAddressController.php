<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserAddressController extends Controller
{
    /**
     * Store a newly created address in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->addresses()->count() >= 3) {
            return back()->withErrors(['address' => 'Anda maksimal hanya dapat menyimpan 3 alamat.']);
        }

        $validated = $request->validate([
            'label' => 'required|string|max:50',
            'address' => 'required|string|max:500',
            'is_default' => 'boolean',
        ]);

        if ($validated['is_default'] ?? false) {
            $user->addresses()->update(['is_default' => false]);
        }

        $user->addresses()->create([
            'label' => $validated['label'],
            'address' => $validated['address'],
            'is_default' => $validated['is_default'] ?? ($user->addresses()->count() === 0),
        ]);

        return back()->with('success', 'Alamat berhasil ditambahkan.');
    }

    /**
     * Update the specified address in storage.
     */
    public function update(Request $request, UserAddress $address)
    {
        $user = Auth::user();

        if ($address->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'label' => 'required|string|max:50',
            'address' => 'required|string|max:500',
            'is_default' => 'boolean',
        ]);

        if ($validated['is_default'] ?? false) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address->update($validated);

        return back()->with('success', 'Alamat berhasil diperbarui.');
    }

    /**
     * Remove the specified address from storage.
     */
    public function destroy(UserAddress $address)
    {
        if ($address->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $address->delete();

        // If no default address is left but others exist, make the first one default
        $user = Auth::user();
        if ($user->addresses()->count() > 0 && !$user->addresses()->where('is_default', true)->exists()) {
            $firstAddress = $user->addresses()->first();
            $firstAddress->update(['is_default' => true]);
        }

        return back()->with('success', 'Alamat berhasil dihapus.');
    }
}
