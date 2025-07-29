<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $siteClosed = Setting::where('key', 'site_closed')->value('value');
        return view('admin.dashboard', [
            'siteClosed' => (bool) $siteClosed,
        ]);
    }

    public function saveSiteStatus(Request $request)
    {
        Setting::updateOrCreate(
            ['key' => 'site_closed'],
            ['value' => $request->has('site_closed') ? '1' : '0']
        );

        return redirect()->route('admin.index')->with('success', 'Настройки сохранены');
    }
}