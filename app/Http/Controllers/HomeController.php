<?php

namespace App\Http\Controllers;

use App\Models\Slider;
use App\Models\About;
use App\Models\Advantage;
use App\Models\Service;
use App\Models\Gallery;
use App\Models\Social;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $closed = Setting::where('key', 'site_closed')->value('value');
        if ($closed && !Auth::check()) {
            return view('closed');
        }

        $sliders = Slider::all();
        $about = About::first();
        $advantages = Advantage::all();
        $services = Service::all();
        $galleries = Gallery::all();
        $socials = Social::orderBy('order')->get();

        return view('index', compact('sliders', 'about', 'advantages', 'services', 'galleries', 'socials'));
    }
} 