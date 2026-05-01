<?php
// TEMPORARY - DELETE AFTER USE
if (($_GET['token'] ?? '') !== 'hiwash_clear_2025') {
    http_response_code(403); die('Forbidden.');
}

$php  = PHP_BINARY; // path ke php executable
$root = dirname(__DIR__); // satu level di atas /public

$commands = [
    'route:clear',
    'config:clear',
    'cache:clear',
    'view:clear',
];

echo "<pre style='background:#111;color:#0f0;padding:20px;font-size:14px;'>";
echo "Root: $root\n\n";

foreach ($commands as $cmd) {
    $output = [];
    $code   = 0;
    exec("$php $root/artisan $cmd 2>&1", $output, $code);
    $status = $code === 0 ? '✅' : '❌';
    echo "$status php artisan $cmd\n";
    echo implode("\n", $output) . "\n\n";
}

echo "Done! Hapus file ini sekarang.\n</pre>";
