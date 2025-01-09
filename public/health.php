<?php

require __DIR__ . '/../vendor/autoload.php';

$type = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'json';

try {
    switch ($type) {
        case 'combat':
            $socket = new \Game\WebSocket\CombatSocket();
            break;
        case 'market':
            $socket = new \Game\WebSocket\MarketSocket();
            break;
        default:
            throw new Exception("Invalid socket type");
    }

    $health = $socket->getHealthCheck()->getStatus();
    $isHealthy = $socket->getHealthCheck()->isHealthy();

    if ($format === 'prometheus') {
        header('Content-Type: text/plain');
        echo "# HELP websocket_up Socket server status\n";
        echo "# TYPE websocket_up gauge\n";
        echo "websocket_up{type=\"$type\"} " . ($isHealthy ? "1" : "0") . "\n";
        // Add more metrics...
        foreach ($health as $key => $value) {
            if (is_numeric($value)) {
                echo "websocket_{$key}{type=\"$type\"} $value\n";
            }
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'healthy' => $isHealthy,
            'status' => $health
        ]);
    }

    http_response_code($isHealthy ? 200 : 503);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 