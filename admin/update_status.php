<?php
session_start();
ob_start(); // Prevent any accidental output from breaking the redirect

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../config.php';
require '../app/PHPMailer/Exception.php';
require '../app/PHPMailer/PHPMailer.php';
require '../app/PHPMailer/SMTP.php';

// Security: Only logged-in admins
if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized access");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = $_POST['app_id'];
        $status = $_POST['new_status']; // 'approved' or 'rejected' or 'pending'

        // 1. Update the Database (This will now ALWAYS save)
        $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);

        // 2. Fetch User Info for the Email
        $stmtUser = $pdo->prepare("SELECT first_name, email, ref_number FROM applications WHERE id = ?");
        $stmtUser->execute([$id]);
        $user = $stmtUser->fetch();

        // --- CRITICAL FIX HERE ---
        // Define $toEmail using the data fetched from the database
        $toEmail = $user['email'] ?? ''; 

        if (!empty($toEmail)) {
            try {
                $mail = new PHPMailer(true);
                
                // Debugging mode: Saves the precise transmission handshake logs to your server error file
                $mail->SMTPDebug = 2; 
                $mail->Debugoutput = function($str, $level) {
                    error_log("PHPMailer Debug: $str");
                };

                $mail->isSMTP();
$mail->Host       = SMTP_HOST;
$mail->SMTPAuth   = true;
$mail->Username   = SMTP_USER; 
$mail->Password   = SMTP_PASS;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = SMTP_PORT;
                
                // Set timeout slightly higher to give the network handshake a chance to break through local firewalls
                $mail->Timeout    = 7; 

                // NETWORK FIX: Force XAMPP to ignore missing local SSL certs on your computer
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];

                $mail->setFrom('hoye.sec@gmail.com', 'Hoye Secondary Admissions'); 
                $mail->addAddress($toEmail);
                $mail->isHTML(true);
                

                if ($status === 'approved') {
                    $mail->Subject = "CONGRATULATIONS: Admission Accepted (Ref: " . $user['ref_number'] . ")";
                    $mail->Body    = "
                        <div style='font-family: Arial; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                            <h2 style='color: #16a34a;'>Admission Accepted!</h2>
                            <p>Dear <strong>{$user['first_name']}</strong>,</p>
                            <p>We are thrilled to inform you that your application to <strong>Hoye Secondary School</strong> has been <strong>ACCEPTED</strong>.</p>
                            <p><strong>Next Steps:</strong> Please visit the school administration office within 14 days to finalize the enrollment and collect your stationery list.</p>
                            <p>Reference Number: <strong>{$user['ref_number']}</strong></p>
                            <hr>
                            <p style='font-size: 12px; color: #666;'>Kind Regards,<br>Hoye Admissions Team</p>
                        </div>";
                } else if ($status === 'rejected') {
                    $mail->Subject = "Application Status Update (Ref: " . $user['ref_number'] . ")";
                    $mail->Body    = "
                        <div style='font-family: Arial; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                            <h2 style='color: #dc2626;'>Application Update</h2>
                            <p>Dear <strong>{$user['first_name']}</strong>,</p>
                            <p>Thank you for your interest in Hoye Secondary School. After careful review, we regret to inform you that your application was not successful at this time.</p>
                            <p>We wish you the very best in your future academic endeavors.</p>
                            <hr>
                            <p style='font-size: 12px; color: #666;'>Hoye Admissions Team</p>
                        </div>";
                } else {
                    // If status is set back to 'pending', skip sending an email
                    throw new Exception("Keep pending status - no email notification required.");
                }

                $mail->send();

            } catch (Exception $mailException) {
                // Email failed, but we catch it silently so the script can still redirect!
                error_log("PHPMailer failed: " . $mailException->getMessage());
            }
        }

        // 4. Smooth Redirect using the corrected variable ($id)
        header("Location: view_application.php?id=" . $id . "&msg=updated");
        exit();

    } catch (Exception $e) {
        die("Database Error: " . $e->getMessage());
    }
}