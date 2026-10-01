<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\SitemapGenerator;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(SitemapGenerator $sitemapGenerator): Response
    {
        return response(
            $sitemapGenerator->generate(),
            200,
            ['Content-Type' => 'application/xml; charset=UTF-8']
        );
    }
}