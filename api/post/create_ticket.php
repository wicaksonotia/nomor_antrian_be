<?php

/**
 * {"service_type":"Pelayanan","branch_id":1}
 */
require '../../utils.php';
allowCors();
require '../../db.php';

$input = getJsonInput();
$service_type = isset($input['service_type']) ? trim($input['service_type']) : null;
$branch_id = isset($input['branch_id']) ? (int)$input['branch_id'] : null;

try {
    // transaction to avoid race condition
    $pdo->beginTransaction();

    $sql = "SELECT COALESCE(MAX(queue_number),0) AS maxnum 
            FROM queue 
            WHERE DATE(created_at) = CURDATE()
              AND (service_type <=> :stype)
              AND (branch_id <=> :branch)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':stype' => $service_type, ':branch' => $branch_id]);
    $row = $stmt->fetch();
    $next = ((int)$row['maxnum']) + 1;

    $ins = $pdo->prepare("INSERT INTO queue (queue_number, service_type, branch_id, is_called, is_done) VALUES (:qnum, :stype, :branch, 0, 0)");
    $ins->execute([':qnum' => $next, ':stype' => $service_type, ':branch' => $branch_id]);
    $id = $pdo->lastInsertId();

    // log
    $log = $pdo->prepare("INSERT INTO queue_log (queue_id, action, message) VALUES (:qid, 'create', :msg)");
    $log->execute([':qid' => $id, ':msg' => 'Ticket created']);

    $pdo->commit();

    jsonResponse(['success' => true, 'id' => (int)$id, 'queue_number' => (int)$next]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(['success' => false, 'message' => 'Failed to create ticket', 'error' => $e->getMessage()], 500);
}
