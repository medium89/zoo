<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    public function index()
    {
        $galleries = Gallery::orderBy('number')->get();
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
            $nextNumber = (int)Gallery::max('number') + 1;
            foreach ($request->file('images') as $image) {
                $path = $image->store('galleries', 'public');
                Gallery::create([
                    'image'   => $path,
                    'active'  => true,
                    'number'  => $nextNumber++,
                ]);
            }
        }
        return redirect()->route('admin.galleries.index')->with('success', 'Фото добавлены');
    }

    public function destroy(Gallery $gallery)
    {
        if ($gallery->image && Storage::disk('public')->exists($gallery->image)) {
            Storage::disk('public')->delete($gallery->image);
        }
        $gallery->delete();
        return redirect()->route('admin.galleries.index')->with('success', 'Фото удалено');
    }

    public function updateStatus(Request $request)
    {
        foreach ($request->input('statuses', []) as $id => $status) {
            if ($gallery = Gallery::find($id)) {
                $gallery->active = (bool)$status;
                $gallery->save();
            }
        }
        foreach ($request->input('numbers', []) as $id => $number) {
            if ($gallery = Gallery::find($id)) {
                $gallery->number = (int)$number;
                $gallery->save();
            }
        }
        return back()->with('success', 'Данные обновлены');
    }
}
