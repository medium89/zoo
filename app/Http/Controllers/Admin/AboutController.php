<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\About;
use Illuminate\Support\Facades\Storage;
use App\Support\ImageProcessor;

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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'text' => 'required|string',
            'image_scale' => 'nullable|integer|min:10|max:100',
            'image_quality' => 'nullable|integer|min:40|max:100',
            'remove_image' => 'nullable|boolean',
        ]);
        if ($request->boolean('remove_image') && $about->image) {
            if (Storage::disk('public')->exists($about->image)) {
                Storage::disk('public')->delete($about->image);
            }
            $about->image = null;
        }
        if ($request->hasFile('image')) {
            if ($about->image && Storage::disk('public')->exists($about->image)) {
                Storage::disk('public')->delete($about->image);
            }
            $path = ImageProcessor::processAndStore($request->file('image'), 'about', (int)$request->input('image_scale',100), (int)$request->input('image_quality',85));
            $about->image = $path;
        }
        $about->text = $request->text;
        $about->save();
        return redirect()->route('admin.about.edit')->with('success', 'Информация обновлена');
    }
}
