<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\SitemapGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AdminController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.dashboard');
    }

    public function settings(SitemapGenerator $sitemapGenerator)
    {
        $settings = SiteSetting::first();
        $robotsPath = public_path('robots.txt');
        $sitemapPath = public_path('sitemap.xml');

        return view('admin.settings', [
            'settings' => $settings,
            'robotsText' => File::exists($robotsPath) ? File::get($robotsPath) : '',
            'sitemapText' => File::exists($sitemapPath) ? File::get($sitemapPath) : $sitemapGenerator->generate(),
        ]);
    }

    public function saveSiteStatus(Request $request)
    {
        $settings = SiteSetting::first() ?? new SiteSetting();

        $settings->site_closed = $request->has('site_closed');
        $settings->title = $request->input('title');
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

    public function saveSeoFiles(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'robots_txt' => 'required|string|max:1000000',
            'sitemap_xml' => 'required|string|max:1000000',
        ]);

        File::put(public_path('robots.txt'), $data['robots_txt']);
        File::put(public_path('sitemap.xml'), $data['sitemap_xml']);

        return redirect()->route('admin.settings')->with('success', 'robots.txt и sitemap.xml сохранены');
    }

    public function rebuildSitemap(SitemapGenerator $sitemapGenerator): RedirectResponse
    {
        $sitemapGenerator->rebuild();

        return redirect()->route('admin.settings')->with('success', 'sitemap.xml пересобран');
    }
}