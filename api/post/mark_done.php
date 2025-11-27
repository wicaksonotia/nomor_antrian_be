<?php
// pelayanan selesai

/**
 * {"id":123}
 */
require '../../utils.php';
allowCors();
require '../../db.php';
// requireApiKey();

$input = getJsonInput();
$id = isset($input['id']) ? (int)$input['id'] : 0;
if (!$id) jsonResponse(['success' => false, 'message' => 'Missing id'], 400);

try {
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE queue SET is_done = 1, updated_at = NOW() WHERE id = ?")->execute([$id]);
    $pdo->prepare("INSERT INTO queue_log (queue_id, action, message) VALUES (?, 'done', 'marked done')")->execute([$id]);
    $pdo->commit();
    jsonResponse(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(['success' => false, 'message' => 'Failed to mark done', 'error' => $e->getMessage()], 500);
}
