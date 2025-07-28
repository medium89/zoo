<?php

namespace App\Http\Controllers;

use App\Models\Social;
use Illuminate\Http\Request;

class SocialController extends Controller
{
    public function index()
    {
        $socials = Social::orderBy('order')->get();
        return view('admin.socials.index', compact('socials'));
    }

    public function create()
    {
        return view('admin.socials.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'icon' => 'required|string',
            'title' => 'required|string',
            'link' => 'required|string',
            'text' => 'required|string',
            'order' => 'nullable|integer',
        ]);
        Social::create($request->only(['icon', 'title', 'link', 'text', 'order']));
        return redirect()->route('socials.index')->with('success', 'Контакт добавлен');
    }

    public function edit(Social $social)
    {
        return view('admin.socials.edit', compact('social'));
    }

    public function update(Request $request, Social $social)
    {
        $request->validate([
            'icon' => 'required|string',
            'title' => 'required|string',
            'link' => 'required|string',
            'text' => 'required|string',
            'order' => 'nullable|integer',
        ]);
        $social->update($request->all());
        return redirect()->route('socials.index')->with('success', 'Контакт обновлён');
    }

    public function destroy(Social $social)
    {
        $social->delete();
        return redirect()->route('socials.index')->with('success', 'Контакт удалён');
    }
} 