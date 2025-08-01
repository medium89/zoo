<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.settings');
    }

    public function settings()
    {
        $settings = SiteSetting::first();

        return view('admin.settings', [
            'settings' => $settings,
        ]);
    }

    public function saveSiteStatus(Request $request)
    {
        $settings = SiteSetting::first();
        if (!$settings) {
            $settings = new SiteSetting();
        }

        $settings->site_closed = $request->has('site_closed');
        $settings->description = $request->input('description');
        $settings->robots = $request->input('robots');
        $settings->charset = $request->input('charset', 'UTF-8');
        $settings->og_title = $request->input('og_title');
        $settings->og_description = $request->input('og_description');
        $settings->og_image = $request->input('og_image');
        $settings->og_url = $request->input('og_url');
        $settings->save();

        return redirect()->route('admin.settings')->with('success', 'Настройки сохранены');
    }
}
