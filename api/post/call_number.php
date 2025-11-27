<?php

/**
 * POST JSON: {"id":1,"counter":"Loket 1"}
 */
require '../../utils.php';
allowCors();
require '../../db.php';

$input = getJsonInput();
$id = isset($input['id']) ? (int)$input['id'] : 0;
$counter = isset($input['counter']) ? trim($input['counter']) : null;

if (!$id || !$counter) {
    jsonResponse(['success' => false, 'message' => 'Missing id or counter'], 400);
}

try {
    $pdo->beginTransaction();

    // 1️⃣ Update nomor antrian untuk loket tertentu
    $upd = $pdo->prepare("
        UPDATE queue 
        SET is_called = 1, counter = :counter, updated_at = NOW() 
        WHERE id = :id
    ");
    $upd->execute([':counter' => $counter, ':id' => $id]);

    // 2️⃣ Log aksi ke queue_log
    $log = $pdo->prepare("
        INSERT INTO queue_log (queue_id, action, message) 
        VALUES (:qid, 'called', :msg)
    ");
    $log->execute([
        ':qid' => $id,
        ':msg' => "Called at {$counter}"
    ]);

    $pdo->commit();

    jsonResponse(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse([
        'success' => false,
        'message' => 'Failed to call number',
        'error' => $e->getMessage()
    ], 500);
}
