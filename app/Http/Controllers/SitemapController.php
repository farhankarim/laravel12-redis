<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;

/**
 * SitemapController
 * 
 * Generates XML sitemaps for search engine optimization
 */
class SitemapController extends Controller
{
    /**
     * Generate the sitemap
     */
    public function index()
    {
        $urls = $this->collectUrls();
        
        return response()->view('sitemap.index', ['urls' => $urls], 200)
            ->header('Content-Type', 'text/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    /**
     * Collect all URLs for the sitemap
     */
    private function collectUrls(): Collection
    {
        $urls = collect();

        // Home page
        $urls->push([
            'url' => route('welcome'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'weekly',
            'priority' => '1.0',
        ]);

        // Dashboard pages (with noindex, but included for completeness)
        $urls->push([
            'url' => route('dashboard.queue'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'hourly',
            'priority' => '0.8',
        ]);

        $urls->push([
            'url' => route('dashboard.users'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '0.8',
        ]);

        return $urls;
    }

    /**
     * Get sitemap statistics
     */
    public function sitemap()
    {
        return response()->view('sitemap.main', [
            'lastmod' => now()->toAtomString(),
        ], 200)
            ->header('Content-Type', 'text/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
