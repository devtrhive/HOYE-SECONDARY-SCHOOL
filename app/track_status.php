<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$ref = $_GET['ref'] ?? '';

if (empty($ref)) {
    echo json_encode(["status" => "error", "message" => "Reference number required"]);
    exit;
}

try {
    // Search using the exact columns from your structure
    $stmt = $pdo->prepare("SELECT first_name, last_name, status FROM applications WHERE ref_number = ?");
    $stmt->execute([$ref]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($application) {
        echo json_encode(["status" => "success", "data" => $application]);
    } else {
        echo json_encode(["status" => "error", "message" => "Not found"]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Database error"]);
}