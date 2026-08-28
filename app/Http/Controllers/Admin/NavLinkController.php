<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\NavLink;
use Illuminate\Http\Request;

class NavLinkController extends Controller
{
    public function index()
    {
        $links = NavLink::orderBy('order')->get();
        return view('admin.nav_links.index', compact('links'));
    }

    public function updateStatus(Request $request)
    {
        $active = $request->input('active', []);
        $links = NavLink::all();

        foreach ($links as $link) {
            $link->active = array_key_exists($link->id, $active);
            $link->save();
        }

        return back()->with('success', 'Настройки меню сохранены');
    }
}
