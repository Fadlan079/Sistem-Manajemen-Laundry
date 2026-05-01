<?php
/**
 * TEMPORARY: Emergency artisan commands runner for production.
 * DELETE THIS FILE immediately after use!
 * Access: https://hiwash.my.id/emergency_clear.php
 */

// Basic protection
$token = $_GET['token'] ?? '';
if ($token !== 'hiwash_clear_2025') {
    http_response_code(403);
    die('Forbidden.');
}

define('LARAVEL_ROOT', __DIR__);

require LARAVEL_ROOT . '/vendor/autoload.php';
$app = require_once LARAVEL_ROOT . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$results = [];

// Run commands
$commands = [
    'route:clear',
    'config:clear',
    'cache:clear',
    'view:clear',
];

foreach ($commands as $cmd) {
    $exitCode = $kernel->call($cmd);
    $results[$cmd] = $exitCode === 0 ? '✅ OK' : '❌ Failed (code: ' . $exitCode . ')';
}

// Also show registered push routes
$router = $app->make('router');
$pushRoutes = collect($router->getRoutes())->filter(function($route) {
    return str_contains($route->uri(), 'push/');
})->map(fn($r) => $r->methods()[0] . ' /' . $r->uri())->values();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head><title>Emergency Clear</title></head>
<body style="font-family:monospace;padding:20px;background:#1a1a1a;color:#fff;">
    <h2>🔧 HiWash Emergency Cache Clear</h2>
    <h3>Commands:</h3>
    <ul>
        <?php foreach ($results as $cmd => $result): ?>
            <li><?= $cmd ?>: <?= $result ?></li>
        <?php endforeach; ?>
    </ul>
    <h3>Push Routes Registered:</h3>
    <ul>
        <?php if ($pushRoutes->isEmpty()): ?>
            <li style="color:red;">❌ No push routes found! web.php not deployed correctly.</li>
        <?php else: ?>
            <?php foreach ($pushRoutes as $r): ?>
                <li style="color:lime;">✅ <?= htmlspecialchars($r) ?></li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
    <p style="color:orange;margin-top:20px;">⚠️ DELETE this file from server immediately!</p>
</body>
</html>
