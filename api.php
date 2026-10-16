<?php
// Only set headers if we're running as a web request
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

require __DIR__ . '/lib.php';

// Main API logic - only run if this file is accessed directly
if (basename($_SERVER['PHP_SELF']) === 'api.php' || php_sapi_name() !== 'cli') {
    $timezone = ($_GET['tz'] ?? '') ?: Time::DEFAULT_TIMEZONE;
    $customDate = $_GET['date'] ?? null;
    $lang = Locales::resolve($_GET['lang'] ?? null);

    // Validate timezone
    if (!Time::zoneExists($timezone)) {
        if (php_sapi_name() !== 'cli') {
            http_response_code(400);
        }
        echo json_encode([
            'error' => [
                'message' => "Timezone `{$timezone}` does not exist",
                'type' => 'Bad Request',
                'code' => 400
            ]
        ]);
        exit();
    }

    // Create time instance
    $parsedDate = $customDate ? date('Y-m-d', strtotime($customDate)) : null;
    $time = new Time($timezone, $parsedDate);

    // Generate response
    $response = [
        'timezone' => $timezone,
        'date' => $customDate
            ? (new DateTime($customDate))->format('c')
            : $time->now()->format('c'),
        'shouldideploy' => ShouldIDeploy::shouldIDeploy($time),
        'message' => ShouldIDeploy::getRandom(ShouldIDeploy::dayHelper($time, $lang))
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>
