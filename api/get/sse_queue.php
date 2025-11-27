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

// Ambil counter dari query string
$counter = isset($_GET['counter']) ? trim($_GET['counter']) : null;
$lastId = 0;
if (isset($_GET['last_id'])) $lastId = (int)$_GET['last_id'];

if (!$counter) {
    echo ": missing counter parameter\n\n";
    @ob_flush(); @flush();
    exit;
}

// Loop SSE
while (true) {
    // Ambil nomor terakhir yang dipanggil untuk counter tertentu
    $stmt = $pdo->prepare("
        SELECT id, queue_number, service_type, counter, updated_at 
        FROM queue 
        WHERE is_called = 1 AND counter = :counter 
        ORDER BY updated_at DESC, id DESC 
        LIMIT 1
    ");
    $stmt->execute([':counter' => $counter]);
    $row = $stmt->fetch();

    if ($row && $row['id'] > $lastId) {
        $lastId = (int)$row['id'];
        $data = [
            'id' => $lastId,
            'queue_number' => (int)$row['queue_number'],
            'service_type' => $row['service_type'],
            'counter' => $row['counter'],
            'updated_at' => $row['updated_at']
        ];

        echo "id: {$lastId}\n";
        echo "event: called\n";
        echo "retry: 1000\n"; // reconnect 1 detik jika putus
        echo "data: " . json_encode($data) . "\n\n";

        @ob_flush(); @flush();
    } else {
        // heartbeat
        echo ": heartbeat\n\n";
        @ob_flush(); @flush();
    }

    // break jika client disconnect
    if (connection_aborted()) break;

    sleep(1);
}
