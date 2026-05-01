<?php
// TEMPORARY - DELETE AFTER USE
if (($_GET['token'] ?? '') !== 'hiwash_clear_2025') {
    http_response_code(403); die('Forbidden.');
}

$root = dirname(__DIR__);

// Cari PHP CLI binary yang benar
$phpCandidates = [
    '/usr/bin/php8.3',
    '/usr/bin/php8.2',
    '/usr/bin/php8.1',
    '/usr/bin/php8.0',
    '/usr/bin/php',
    '/usr/local/bin/php',
    'php8.3',
    'php',
];

$php = null;
foreach ($phpCandidates as $candidate) {
    $test = shell_exec("$candidate -r 'echo 1;' 2>/dev/null");
    if (trim($test) === '1') {
        $php = $candidate;
        break;
    }
}

echo "<pre style='background:#111;color:#0f0;padding:20px;font-size:14px;'>";
echo "Root: $root\n";
echo "PHP CLI: " . ($php ?? 'NOT FOUND') . "\n\n";

if (!$php) {
    echo "❌ Cannot find PHP CLI binary. Try SSH manually.\n";
    echo "\nCommands to run via SSH:\n";
    echo "cd $root\n";
    echo "php artisan route:clear\n";
    echo "php artisan config:clear\n";
    echo "php artisan cache:clear\n";
    echo "</pre>";
    exit;
}

$commands = ['route:clear', 'config:clear', 'cache:clear', 'view:clear'];

foreach ($commands as $cmd) {
    $output = [];
    $code   = 0;
    exec("$php $root/artisan $cmd 2>&1", $output, $code);
    $status = $code === 0 ? '✅' : '❌';
    echo "$status php artisan $cmd\n";
    if (!empty($output)) {
        echo "   " . implode("\n   ", $output) . "\n";
    }
    echo "\n";
}

echo "\n✅ Done! Please delete this file now.\n</pre>";
