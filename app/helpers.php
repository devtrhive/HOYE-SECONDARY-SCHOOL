<?php
// Start session for security tokens
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generates a unique, professional Application Tracking Number
 */
function generateTrackingNumber($pdo) {
    $year = date('Y');
    // Generate a 4-character random string
    $random = strtoupper(bin2hex(random_bytes(2))); 
    $trackingNum = "ADM-$year-$random";

    // Double check that it's unique in the database
    $stmt = $pdo->prepare("SELECT id FROM applications WHERE ref_number = ?");
    $stmt->execute([$trackingNum]);
    
    if ($stmt->fetch()) {
        return generateTrackingNumber($pdo); // Loop if exists
    }

    return $trackingNum;
}