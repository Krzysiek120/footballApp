<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\EventHandler;
use App\StatisticsManager;

header('Content-Type: application/json');

// Simple routing
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($method === 'POST' && $path === '/event') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    $handler = new EventHandler(__DIR__ . '/../storage/database.sqlite');

    try {
        $result = $handler->handleEvent($data);
        http_response_code(201);
        echo json_encode($result);
    }
    catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
elseif ($method === 'GET' && $path === '/statistics') {
    $statsManager = new StatisticsManager(__DIR__ . '/../storage/statistics.txt');

    $matchId = $_GET['match_id'] ?? null;
    $teamId = $_GET['team_id'] ?? null;

    try {
        if ($matchId && $teamId) {
            // Get team statistics for specific match
            $stats = $statsManager->getTeamStatistics($matchId, $teamId);
            echo json_encode([
                'match_id' => $matchId,
                'team_id' => $teamId,
                'statistics' => $stats
            ]);
        }
        elseif ($matchId) {
            // Get all team statistics for specific match
            $stats = $statsManager->getMatchStatistics($matchId);
            echo json_encode([
                'match_id' => $matchId,
                'statistics' => $stats
            ]);
        }
        else {
            http_response_code(400);
            echo json_encode(['error' => 'match_id is required']);
        }
    }
    catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
elseif ($method === 'GET' && $path === '/stream') {
    header('Cache-Control: no-cache');
    header('Content-Type: text/event-stream');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');

    $queueFile = __DIR__ . '/../storage/sse_queue.txt';
    if (!file_exists($queueFile)) {
        touch($queueFile);
    }

    $lastPos = filesize($queueFile);
    set_time_limit(0);

    while (true) {
        if (connection_aborted()) {
            break;
        }

        clearstatcache(true, $queueFile);
        $currentPos = filesize($queueFile);

        if ($currentPos > $lastPos) {
            $handle = fopen($queueFile, 'r');
            fseek($handle, $lastPos);

            while (($line = fgets($handle)) !== false) {
                if (trim($line) !== '') {
                    echo "data: " . trim($line) . "\n\n";
                }
            }

            $lastPos = ftell($handle);
            fclose($handle);

            ob_flush();
            flush();
        }

        usleep(500000);
    }
}
else {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}