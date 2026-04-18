<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    /**
     * Store a newly created banner.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image'     => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:4096',
            'is_active' => 'boolean',
        ]);

        $path = $request->file('image')->store('banners', 'public');

        Banner::create([
            'image'     => $path,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Banner berhasil ditambahkan.');
    }

    /**
     * Toggle active / inactive status.
     */
    public function update(Request $request, Banner $banner)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $banner->update(['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Status banner diperbarui.');
    }

    /**
     * Delete a banner and its image file.
     */
    public function destroy(Banner $banner)
    {
        // Remove the image file from storage
        if ($banner->image && Storage::disk('public')->exists($banner->image)) {
            Storage::disk('public')->delete($banner->image);
        }

        $banner->delete();

        return back()->with('success', 'Banner berhasil dihapus.');
    }
}
