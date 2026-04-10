<?php

namespace App\Services;

/**
 * SEO Service
 * 
 * Manages SEO metadata and structured data across the application
 */
class SeoService
{
    private array $metadata = [];
    private array $structuredData = [];

    public function __construct()
    {
        $this->initializeDefaults();
    }

    /**
     * Initialize default SEO metadata
     */
    private function initializeDefaults(): void
    {
        $appName = config('app.name', 'Laravel');
        
        $this->metadata = [
            'title' => "{$appName} - Redis Queue & User Dashboard",
            'description' => 'Monitor queue status, track email verifications, and manage user data with advanced Redis integration and real-time queue monitoring.',
            'keywords' => 'Laravel, Redis, Queue, Dashboard, Monitoring, PHP Framework, Real-time',
            'author' => $appName,
            'robots' => 'index, follow',
            'theme_color' => '#1b1b18',
        ];

        $this->structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'WebApplication',
            'name' => $appName,
            'description' => $this->metadata['description'],
            'url' => config('app.url'),
            'applicationCategory' => 'UtilityApplication',
        ];
    }

    /**
     * Set page title
     */
    public function setTitle(string $title): self
    {
        $this->metadata['title'] = "{$title} - " . config('app.name', 'Laravel');
        return $this;
    }

    /**
     * Set page description
     */
    public function setDescription(string $description): self
    {
        $this->metadata['description'] = $description;
        return $this;
    }

    /**
     * Set keywords
     */
    public function setKeywords(array|string $keywords): self
    {
        $this->metadata['keywords'] = is_array($keywords) ? implode(', ', $keywords) : $keywords;
        return $this;
    }

    /**
     * Add a keyword
     */
    public function addKeyword(string $keyword): self
    {
        $current = $this->metadata['keywords'] ?? '';
        $this->metadata['keywords'] = $current ? "{$current}, {$keyword}" : $keyword;
        return $this;
    }

    /**
     * Set robots directive
     */
    public function setRobots(string $directive): self
    {
        $this->metadata['robots'] = $directive;
        return $this;
    }

    /**
     * Set canonical URL
     */
    public function setCanonical(string $url): self
    {
        $this->metadata['canonical'] = $url;
        return $this;
    }

    /**
     * Set Open Graph data
     */
    public function setOpenGraph(array $data): self
    {
        $this->metadata['og'] = array_merge($this->metadata['og'] ?? [], $data);
        return $this;
    }

    /**
     * Set Twitter Card data
     */
    public function setTwitterCard(array $data): self
    {
        $this->metadata['twitter'] = array_merge($this->metadata['twitter'] ?? [], $data);
        return $this;
    }

    /**
     * Add structured data
     */
    public function addStructuredData(array $data): self
    {
        $this->structuredData = array_merge($this->structuredData, $data);
        return $this;
    }

    /**
     * Get all metadata
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Get structured data
     */
    public function getStructuredData(): array
    {
        return $this->structuredData;
    }

    /**
     * Get title
     */
    public function getTitle(): string
    {
        return $this->metadata['title'] ?? '';
    }

    /**
     * Get description
     */
    public function getDescription(): string
    {
        return $this->metadata['description'] ?? '';
    }

    /**
     * Get keywords
     */
    public function getKeywords(): string
    {
        return $this->metadata['keywords'] ?? '';
    }

    /**
     * Generate sitemap entry
     */
    public static function generateSitemapEntry(string $url, string $lastmod = '', string $changefreq = 'weekly', string $priority = '0.8'): string
    {
        $entry = "  <url>\n    <loc>" . htmlspecialchars($url) . "</loc>\n";
        
        if ($lastmod) {
            $entry .= "    <lastmod>{$lastmod}</lastmod>\n";
        }
        
        $entry .= "    <changefreq>{$changefreq}</changefreq>\n";
        $entry .= "    <priority>{$priority}</priority>\n";
        $entry .= "  </url>\n";
        
        return $entry;
    }

    /**
     * Get JSON-LD structured data
     */
    public function getJsonLd(): string
    {
        return json_encode($this->structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
