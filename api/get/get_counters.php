<?php
require '../../utils.php';
allowCors();
require '../../db.php';

$stmt = $pdo->query("SELECT id, counter_name, status, created_at FROM counters ORDER BY id ASC");
$counters = $stmt->fetchAll();

jsonResponse(['success' => true, 'counters' => $counters]);
