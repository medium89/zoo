<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    public function index()
    {
        $galleries = Gallery::all();
        return view('admin.galleries.index', compact('galleries'));
    }

    public function create()
    {
        return view('admin.galleries.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
        ]);
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('galleries', 'public');
                Gallery::create(['image' => $path]);
            }
        }
        return redirect()->route('galleries.index')->with('success', 'Фото добавлены');
    }

    public function destroy(Gallery $gallery)
    {
        if ($gallery->image && Storage::disk('public')->exists($gallery->image)) {
            Storage::disk('public')->delete($gallery->image);
        }
        $gallery->delete();
        return redirect()->route('galleries.index')->with('success', 'Фото удалено');
    }
}
