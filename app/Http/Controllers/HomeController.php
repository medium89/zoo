<?php

namespace App\Http\Controllers;

use App\Models\Slider;
use App\Models\About;
use App\Models\Advantage;
use App\Models\Service;
use App\Models\Gallery;
use App\Models\Social;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $setting = SiteSetting::first();
        $closed = $setting?->site_closed;
        if ($closed && !Auth::check()) {
            return view('closed');
        }

        $sliders = Slider::where('active', true)->orderBy('order')->get();
        $about = About::first();
        $advantages = Advantage::where('active', true)->get();
        $services = Service::where('active', true)->get();
        $galleries = Gallery::where('active', true)->orderBy('number')->orderBy('id')->take(12)->get();
        $totalGalleries = Gallery::where('active', true)->count();
        $hasMoreGalleries = $totalGalleries > $galleries->count();
        $socials = Social::where('active', true)->orderBy('order')->get();

        return view('index', compact('sliders', 'about', 'advantages', 'services', 'galleries', 'socials', 'hasMoreGalleries'));
    }

    public function galleryMore(Request $request)
    {
        $limit = max(1, min(24, (int)$request->query('limit', 8)));
        $offset = max(0, (int)$request->query('offset', 0));
        $items = Gallery::where('active', true)
            ->orderBy('number')->orderBy('id')
            ->skip($offset)
            ->take($limit)
            ->get();
        $total = Gallery::where('active', true)->count();
        $hasMore = ($offset + $items->count()) < $total;

        $html = view('partials.gallery_items', ['items' => $items])->render();
        return response()->json(['html' => $html, 'count' => $items->count(), 'hasMore' => $hasMore]);
    }
}
