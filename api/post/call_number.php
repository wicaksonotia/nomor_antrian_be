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
    // Cek apakah sudah melebihi batas panggilan
    $check = $pdo->prepare("SELECT is_called FROM queue WHERE id = :id");
    $check->execute([':id' => $id]);
    $current = $check->fetchColumn();

    if ($current >= 3) {
        jsonResponse([
            'success' => false,
            'message' => 'Nomor ini sudah mencapai batas panggil ulang (maksimal 3x)'
        ], 400);
        exit;
    }

    $pdo->beginTransaction();

    // Update is_called +1 tapi maksimal 3
    $upd = $pdo->prepare("
        UPDATE queue 
        SET 
            is_called = LEAST(is_called + 1, 3),
            counter = :counter,
            updated_at = NOW() 
        WHERE id = :id
    ");
    $upd->execute([':counter' => $counter, ':id' => $id]);

    // Log
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
