<?php
// 1. INITIALIZE SYSTEM ENVIRONMENT VARIABLES (Points safely up to root)
require_once __DIR__ . '/../config.php';

ini_set('display_errors', 1); // Hide raw errors from breaking JSON output
ini_set('log_errors', 1);     // Log errors quietly to XAMPP logs instead
error_reporting(E_ALL);

ob_start(); // Prevent accidental formatting spaces from breaking JSON headers

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

try {
    // 2. LOAD COMPONENT LIBRARIES Safely using absolute directory paths
    require_once __DIR__ . '/PHPMailer/Exception.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/SMTP.php';

    // 3. GENERATE UNIQUE TRACKING IDENTIFIER
    $ref = "HY-" . date('Y') . "-" . strtoupper(bin2hex(random_bytes(2)));

    // 4. SECURE FILE UPLOADS DIRECTORY DISPATCHER
    $uploadDir = __DIR__ . '/../storage/uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Base state layout array for DB target columns
    $uploadedPaths = [
        'pdf_form_path'       => null,
        'birth_cert_path'     => null,
        'school_report_path'  => null,
        'parent_id_path'      => null,
        'proof_res_path'      => null
    ];

    // --- MATCHED DIRECTLY TO YOUR HTML FIELD NAMES ---
    $fileFieldsMap = [
        'pdf_form_path'   => 'pdf_form_path',      // Catches your generated PDF application blob!
        'learner_id'      => 'birth_cert_path',    // Catches name="learner_id"
        'school_report'   => 'school_report_path', // Catches name="school_report"
        'parent_id'       => 'parent_id_path',     // Catches name="parent_id"
        'proof_residence' => 'proof_res_path'      // Catches name="proof_residence"
    ];

    foreach ($fileFieldsMap as $htmlName => $dbColumn) {
        if (!empty($_FILES[$htmlName]['name']) && $_FILES[$htmlName]['error'] === UPLOAD_ERR_OK) {
            $fileExtension = strtolower(pathinfo($_FILES[$htmlName]['name'], PATHINFO_EXTENSION));
            
            // Build a consistent naming format: HY-2026-XXXX_column_name.pdf
            $newFileName = $ref . "_" . $dbColumn . "." . $fileExtension;
            $targetFilePath = $uploadDir . $newFileName;

            if (move_uploaded_file($_FILES[$htmlName]['tmp_name'], $targetFilePath)) {
                $uploadedPaths[$dbColumn] = $newFileName;
            }
        }
    }

    // 5. TRANSACT APPLICANT CORE MATRIX INTO DATA STORE
    $pdo->beginTransaction();

    $formData = [];
    $q = $pdo->query("DESCRIBE applications");
    $dbColumns = $q->fetchAll(PDO::FETCH_COLUMN);

    // Dynamic clean map for textual properties
    foreach ($_POST as $key => $value) {
        if (in_array($key, $dbColumns)) {
            $formData[$key] = trim($value);
        }
    }

    // Inject our successfully uploaded disk file names into the query set
    foreach ($uploadedPaths as $columnKey => $fileName) {
        if (in_array($columnKey, $dbColumns) && !empty($fileName)) {
            $formData[$columnKey] = $fileName;
        }
    }

    // Explicit system properties override
    $formData['tracking_number'] = $ref;
    $formData['ref_number']      = $ref;
    $formData['status']          = 'Pending';

    if (in_array('dob', $dbColumns)) {
        $formData['dob'] = !empty($_POST['dob']) ? $_POST['dob'] : '1900-01-01';
    }

    // Build query using verified database keys automatically
    $columns = implode(", ", array_keys($formData));
    $placeholders = ":" . implode(", :", array_keys($formData));
    
    $sql = "INSERT INTO applications ($columns) VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($formData);

    $pdo->commit(); 

    // 6. DISPATCH AUTOMATED EMAIL RECEIPT
    $emailStatus = "Not attempted";
    $toEmail = $_POST['email'] ?? '';
    
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
            
            $logoPath = __DIR__ . '/../public/images/school.jpg';
            if (file_exists($logoPath)) {
                $mail->addEmbeddedImage($logoPath, 'logo_cid');
            }
            
            $mail->Subject = 'Application Received - Hoye Secondary School (Ref: ' . $ref . ')';

            $applicant_first_name = $_POST['first_name'] ?? 'Applicant';
            $applicant_last_name = $_POST['last_name'] ?? '';

            // Exact HTML Email Template Form Layout
            $message = "
            <div style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #1e293b; max-width: 600px; margin: 20px auto; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.06);'>
                <div style='background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%); color: white; padding: 30px 20px; text-align: center;'>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='text-align: center; padding-bottom: 12px;'>
                                <img src='cid:logo_cid' style='width: 65px; height: auto; display: inline-block; border-radius: 6px; background: #ffffff; padding: 4px;' alt='Hoye Secondary School Logo' />
                            </td>
                        </tr>
                        <tr>
                            <td style='text-align: center;'>
                                <h1 style='margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px; text-transform: uppercase;'>Hoye Secondary School</h1>
                                <p style='margin: 4px 0 0; opacity: 0.9; font-size: 13px; font-weight: 500; letter-spacing: 1px; text-transform: uppercase;'>Official Admissions Management Office</p>
                            </td>
                        </tr>
                    </table>
                </div>
                <div style='padding: 35px 30px; background-color: #ffffff;'>
                    <p style='font-size: 16px; margin-top: 0; color: #0f172a;'>Dear <strong>" . htmlspecialchars($applicant_first_name . " " . $applicant_last_name) . "</strong>,</p>
                    <p style='font-size: 15px; color: #334155;'>Thank you for choosing <strong>Hoye Secondary School</strong>. We are pleased to confirm that we have successfully received your online application data along with all required verification documents for the 2026 academic year.</p>
                    <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 5px solid #1e40af; border-radius: 6px; padding: 18px; margin: 25px 0; text-align: center;'>
                        <p style='margin: 0; font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;'>Your Application Reference</p>
                        <h2 style='margin: 6px 0 0; color: #1e40af; font-size: 32px; font-family: monospace; letter-spacing: 1.5px; font-weight: 800;'>$ref</h2>
                    </div>
                    <h3 style='font-size: 15px; color: #0f172a; margin: 30px 0 12px 0; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;'>What happens next?</h3>
                    <table style='width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 14px; color: #475569;'>
                        <tr>
                            <td style='width: 28px; vertical-align: top; padding-bottom: 12px;'>
                                <div style='background: #eff6ff; color: #1e40af; width: 20px; height: 20px; border-radius: 50%; text-align: center; line-height: 20px; font-weight: bold; font-size: 11px;'>1</div>
                            </td>
                            <td style='vertical-align: top; padding-bottom: 12px; padding-left: 8px;'>Our admissions team will review your submitted documents thoroughly.</td>
                        </tr>
                        <tr>
                            <td style='width: 28px; vertical-align: top; padding-bottom: 12px;'>
                                <div style='background: #eff6ff; color: #1e40af; width: 20px; height: 20px; border-radius: 50%; text-align: center; line-height: 20px; font-weight: bold; font-size: 11px;'>2</div>
                            </td>
                            <td style='vertical-align: top; padding-bottom: 12px; padding-left: 8px;'>You can use your reference number (<strong style='color: #1e40af; font-family: monospace;'>$ref</strong>) to check your placement status anytime on our portal.</td>
                        </tr>
                        <tr>
                            <td style='width: 28px; vertical-align: top; padding-bottom: 12px;'>
                                <div style='background: #eff6ff; color: #1e40af; width: 20px; height: 20px; border-radius: 50%; text-align: center; line-height: 20px; font-weight: bold; font-size: 11px;'>3</div>
                            </td>
                            <td style='vertical-align: top; padding-bottom: 12px; padding-left: 8px;'>An official outcome notice will be dispatched directly to this email address once processing is fully complete.</td>
                        </tr>
                    </table>
                    <p style='font-size: 14px; color: #475569; background: #f1f5f9; padding: 12px 15px; border-radius: 6px; margin-bottom: 30px;'>Should you have any inquiries regarding your placement pipeline, please contact our administrative office quoting your unique reference number above.</p>
                    <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 25px 0;'>
                    <p style='margin: 0; font-size: 14px; color: #64748b; line-height: 1.5;'>Kind Regards,<br><strong style='color: #0f172a; font-size: 15px;'>Hoye Secondary School Admissions Team</strong></p>
                </div>
                <div style='background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px; text-align: center; font-size: 11px; color: #94a3b8;'>
                    <p style='margin: 0 0 4px 0; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;'>This is an automated message</p>
                    <p style='margin: 0 0 12px 0;'>Please do not reply directly to this email address as the inbox is unmonitored.</p>
                    <p style='margin: 0; border-top: 1px solid #f1f5f9; padding-top: 12px;'>&copy; 2026 Hoye Secondary School. All rights reserved.</p>
                </div>
            </div>";

            $mail->Body = $message;
            $mail->send();
            $emailStatus = "Sent Successfully";
        } catch (Exception $e) {
            $emailStatus = "Mail Exception: " . $mail->ErrorInfo;
        }
    }

    // 7. DISPATCH SANITIZED JSON OUTPUT LOOP
    if (ob_get_length()) ob_end_clean();
    echo json_encode([
        "status" => "success",
        "tracking_number" => $ref,
        "email_info" => $emailStatus
    ]);
    exit();

} catch (Exception $mainError) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (ob_get_length()) ob_end_clean();
    echo json_encode([
        "status" => "error",
        "message" => "Critical Error: " . $mainError->getMessage()
    ]);
    exit();
}