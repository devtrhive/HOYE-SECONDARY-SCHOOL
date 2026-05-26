<?php
header('Content-Type: application/json');
require_once 'config.php';

$id = $_GET['id'] ?? '';
$phone = $_GET['phone'] ?? '';

if (empty($id) || empty($phone)) {
    echo json_encode(["status" => "error", "message" => "ID and Phone required"]);
    exit;
}

try {
    // CHANGED: Using mobile_number to match your database structure
    $stmt = $pdo->prepare("SELECT ref_number FROM applications WHERE id_number = ? AND mobile_number = ?");
    $stmt->execute([$id, $phone]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($res) {
        echo json_encode(["status" => "success", "ref_number" => $res['ref_number']]);
    } else {
        echo json_encode(["status" => "error", "message" => "No matching application found"]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server Error"]);
}