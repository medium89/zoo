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
            'link_text' => 'required|string',
            'text' => 'required|string',
            'order' => 'nullable|integer',
            'active' => 'required|boolean',
        ]);
        Social::create($request->only(['icon', 'title', 'link', 'link_text', 'text', 'order', 'active']));
        return redirect()->route('admin.socials.index')->with('success', 'Контакт добавлен');
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
            'link_text' => 'required|string',
            'text' => 'required|string',
            'order' => 'nullable|integer',
            'active' => 'required|boolean',
        ]);
        $social->update($request->all());
        return redirect()->route('admin.socials.index')->with('success', 'Контакт обновлён');
    }

    public function destroy(Social $social)
    {
        $social->delete();
        return redirect()->route('admin.socials.index')->with('success', 'Контакт удалён');
    }

    public function updateStatus(Request $request)
    {
        foreach ($request->input('statuses', []) as $id => $status) {
            if ($social = Social::find($id)) {
                $social->active = (bool)$status;
                $social->save();
            }
        }
        return back()->with('success', 'Статусы обновлены');
    }
}