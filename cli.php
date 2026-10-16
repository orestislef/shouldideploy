<?php
// Plain text endpoint for scripts and CI, same output as upstream /api/cli
// 200 when it's fine to deploy, 423 when not, so `curl --fail` stops the pipeline.
// Upstream uses 425, but Apache 2.4 doesn't know that code and turns it into a 500
require __DIR__ . '/lib.php';

const CLI_CACHE = 'public, max-age=60, s-maxage=300, stale-while-revalidate=3600';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    header('Cache-Control: no-store');
    echo 'Method Not Allowed';
    exit();
}

$ctx = ShouldIDeploy::resolveContext($_GET);

header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($ctx === null) {
    $timezone = ($_GET['tz'] ?? '') ?: Time::DEFAULT_TIMEZONE;
    http_response_code(400);
    header('Cache-Control: no-store');
    echo "Timezone `{$timezone}` does not exist";
    exit();
}

http_response_code($ctx['canDeploy'] ? 200 : 423);
header('Cache-Control: ' . CLI_CACHE);
echo "[shouldideploy] {$ctx['verdict']}: {$ctx['reason']}";
