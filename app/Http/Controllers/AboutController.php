<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\About;
use Illuminate\Support\Facades\Storage;

class AboutController extends Controller
{
    public function edit()
    {
        $about = About::first();
        return view('admin.about.edit', compact('about'));
    }

    public function update(Request $request)
    {
        $about = About::first();
        if (!$about) {
            $about = new About();
        }
        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'text' => 'required|string',
        ]);
        if ($request->hasFile('image')) {
            if ($about->image && Storage::disk('public')->exists($about->image)) {
                Storage::disk('public')->delete($about->image);
            }
            $path = $request->file('image')->store('about', 'public');
            $about->image = $path;
        }
        $about->text = $request->text;
        $about->save();
        return redirect()->route('about.edit')->with('success', 'Информация обновлена');
    }
}
