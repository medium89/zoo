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
        $galleries = Gallery::where('active', true)->orderBy('number')->get();
        $socials = Social::where('active', true)->orderBy('order')->get();

        return view('index', compact('sliders', 'about', 'advantages', 'services', 'galleries', 'socials'));
    }
} 
