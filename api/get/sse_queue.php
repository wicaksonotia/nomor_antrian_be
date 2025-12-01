<?php
require '../../utils.php';
require '../../db.php';

// SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header("Access-Control-Allow-Origin: *");

ignore_user_abort(true);
set_time_limit(0);

$counter = isset($_GET['counter']) ? trim($_GET['counter']) : null;

if (!$counter) {
    echo "event: error\n";
    echo "data: {\"error\":\"missing counter\"}\n\n";
    @ob_flush();
    @flush();
    exit;
}

// Pastikan buffer bersih
while (ob_get_level() > 0) {
    ob_end_flush();
}
ob_implicit_flush(true);

while (true) {

    $stmt = $pdo->prepare("
        SELECT id, queue_number, service_type, counter, updated_at
        FROM queue
        WHERE counter = :counter AND is_called > 0
        ORDER BY updated_at DESC, id DESC
        LIMIT 1
    ");
    $stmt->execute([':counter' => $counter]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo "event: called\n";
        echo "data: " . json_encode($row) . "\n\n";
    } else {
        // tetap kirim heartbeat supaya tidak blank
        echo "event: heartbeat\n";
        echo "data: {}\n\n";
    }

    @ob_flush();
    @flush();

    if (connection_aborted()) break;

    sleep(1);
}
