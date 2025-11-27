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

while (true) {
    // Ambil semua antrian yang belum done
    $stmt = $pdo->prepare("
        SELECT * 
        FROM queue 
        WHERE is_done = 0 
        ORDER BY id ASC 
        LIMIT 100
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "data: " . json_encode($rows) . "\n\n";

    ob_flush();
    flush();
    sleep(2);
}
