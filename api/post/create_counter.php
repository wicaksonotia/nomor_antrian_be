<?php

/**
 * {"counter_name":"Loket 1"}
 */
require '../../utils.php';
allowCors();
require '../../db.php';
// requireApiKey();

$input = getJsonInput();
$name = isset($input['counter_name']) ? trim($input['counter_name']) : null;
if (!$name) jsonResponse(['success' => false, 'message' => 'Missing counter_name'], 400);

try {
    $stmt = $pdo->prepare("INSERT INTO counters (counter_name, status) VALUES (:name, 1)");
    $stmt->execute([':name' => $name]);
    jsonResponse(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to create counter', 'error' => $e->getMessage()], 500);
}
