<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

use App\Models\Slider;
use App\Models\About;
use App\Models\Advantage;
use App\Models\Service;
use App\Models\Gallery;
use App\Models\Social;
use App\Models\SiteSetting;
use App\Models\AvitoReview;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $siteSettings = SiteSetting::first();
        $closed = $siteSettings?->site_closed;
        if ($closed && !Auth::check()) {
            return view('closed');
        }

        $sliders = Slider::where('active', true)->orderBy('order')->get();
        $about = About::first();
        $advantages = Advantage::where('active', true)->orderBy('order')->orderBy('id')->get();
        $services = Service::where('active', true)->orderBy('order')->orderBy('id')->get();
        $galleries = Gallery::where('active', true)->orderBy('number')->orderBy('id')->take(12)->get();
        $totalGalleries = Gallery::where('active', true)->count();
        $hasMoreGalleries = $totalGalleries > $galleries->count();
        $socials = Social::where('active', true)->orderBy('order')->get();
        $avitoReviews = AvitoReview::where('status', 'published')
            ->orderByRaw('review_date IS NULL')
            ->orderByDesc('review_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
        $personalDataConsentText = $siteSettings?->personal_data_consent_text;

        return view('index', compact(
            'sliders',
            'about',
            'advantages',
            'services',
            'galleries',
            'socials',
            'hasMoreGalleries',
            'avitoReviews',
            'personalDataConsentText'
        ));
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

    /**
     * Experimental public redesign. It intentionally uses the same content as
     * the main page so editors do not need to duplicate anything in the admin.
     */
    public function v2()
    {
        $siteSettings = SiteSetting::first();
        $closed = $siteSettings?->site_closed;
        if ($closed && !Auth::check()) {
            return view('closed');
        }

        $sliders = Slider::where('active', true)->orderBy('order')->get();
        $about = About::first();
        $advantages = Advantage::where('active', true)->orderBy('order')->orderBy('id')->get();
        $services = Service::where('active', true)->orderBy('order')->orderBy('id')->get();
        $galleries = Gallery::where('active', true)->orderBy('number')->orderBy('id')->take(12)->get();
        $socials = Social::where('active', true)->orderBy('order')->get();
        $avitoReviews = AvitoReview::where('status', 'published')
            ->orderByRaw('review_date IS NULL')
            ->orderByDesc('review_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
        $personalDataConsentText = $siteSettings?->personal_data_consent_text;

        return view('v2', compact(
            'sliders',
            'about',
            'advantages',
            'services',
            'galleries',
            'socials',
            'avitoReviews',
            'personalDataConsentText'
        ));
    }
}
