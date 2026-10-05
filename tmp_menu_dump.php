<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$svc = app(App\Services\MenuCacheService::class);
$tree = $svc->getCategoryTree(getSessionLanguage());
$find = function($nodes, $slug) use (&$find) {
    foreach ($nodes as $node) {
        if (($node['slug'] ?? '') === $slug) {
            return $node;
        }
        if (!empty($node['children'])) {
            $found = $find($node['children'], $slug);
            if ($found) {
                return $found;
            }
        }
    }
    return null;
};
$node = $find($tree, 'certificate-courses');
foreach (($node['children'] ?? []) as $child) {
    echo ($child['slug'] ?? '') . ' | ' . ($child['name'] ?? '') . PHP_EOL;
}
