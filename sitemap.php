<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');
$urls = [
    [site_url(ltrim(route('home'), '/')), '1.0'],
    [site_url(ltrim(route('shop'), '/')), '0.9'],
    [site_url(ltrim(route('about'), '/')), '0.6'],
    [site_url(ltrim(route('contact'), '/')), '0.5'],
    [site_url(ltrim(route('faq'), '/')), '0.5'],
];
foreach (products_active() as $p) {
    $urls[] = [site_url(ltrim(route('product', $p['slug']), '/')), '0.8'];
}
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as [$loc, $prio]) {
    echo '  <url><loc>' . e($loc) . '</loc><priority>' . $prio . '</priority></url>' . "\n";
}
echo '</urlset>';
