<?php
/**
 * Dynamic Sitemap Generator for M Models
 * Scans for .html files and generates a valid XML sitemap
 */

header("Content-Type: application/xml; charset=utf-8");

$base_url = "https://mmodels.ca/";
$directory = __DIR__;

// Define priorities and frequencies for specific pages
$custom_meta = [
    "index" => ["priority" => "1.0", "freq" => "daily", "slug" => ""],
    "models" => ["priority" => "0.9", "freq" => "weekly"],
    "influencers" => ["priority" => "0.9", "freq" => "weekly"],
    "talent-introduction" => ["priority" => "0.85", "freq" => "weekly"],
    "become-a-model" => ["priority" => "0.9", "freq" => "monthly"],
    "privacy-policy" => ["priority" => "0.3", "freq" => "yearly"],
];

// Default meta for pages not in the list
$default_priority = "0.7";
$default_freq = "monthly";

// Files to exclude
$exclude = ["sitemap.xml", "google-verification.html"];

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

// Scan for .html files
$files = glob($directory . "/*.html");

foreach ($files as $file) {
    $filename = basename($file);
    if (in_array($filename, $exclude)) continue;
    
    $slug = str_replace(".html", "", $filename);
    $meta_key = $slug;
    
    $display_slug = $slug;
    if (isset($custom_meta[$slug]["slug"])) {
        $display_slug = $custom_meta[$slug]["slug"];
    }
    
    $priority = $custom_meta[$slug]["priority"] ?? $default_priority;
    $freq = $custom_meta[$slug]["freq"] ?? $default_freq;
    $lastmod = date("Y-m-d", filemtime($file));
    
    $url = $base_url . $display_slug;

    echo '  <url>' . PHP_EOL;
    echo '    <loc>' . htmlspecialchars($url) . '</loc>' . PHP_EOL;
    echo '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
    echo '    <changefreq>' . $freq . '</changefreq>' . PHP_EOL;
    echo '    <priority>' . $priority . '</priority>' . PHP_EOL;
    echo '  </url>' . PHP_EOL;
}

echo '</urlset>';
