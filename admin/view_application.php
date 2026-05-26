<?php
session_start();
require_once __DIR__ . '/../config.php';

// Security: Kick out if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: index.php"); exit(); }

// Fetch the full student profile
$stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$id]);
$app = $stmt->fetch();

if (!$app) { echo "Application not found."; exit(); }
?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
    <div class="max-w-5xl mx-auto mt-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm">
        Status updated and email notification sent successfully!
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Student | <?php echo htmlspecialchars($app['ref_number'] ?? $app['tracking_number']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-100 p-4 md:p-10">

    <div class="max-w-5xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <a href="index.php" class="text-slate-600 hover:text-blue-600 font-medium transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
            <div class="space-x-2">
                <button onclick="window.print()" class="bg-white px-4 py-2 rounded-lg border shadow-sm hover:bg-slate-50 font-bold transition">
                    <i class="fas fa-print mr-2 text-slate-500"></i> Print App
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border p-8 mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex flex-col md:flex-row items-center gap-6 text-center md:text-left">
                <div class="w-20 h-20 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-3xl font-bold">
                    <?php echo substr($app['first_name'], 0, 1) . substr($app['last_name'], 0, 1); ?>
                </div>
                <div>
                    <h1 class="text-3xl font-black text-slate-800"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></h1>
                    <p class="text-slate-500 font-mono text-sm mt-1">
                        <?php echo htmlspecialchars($app['ref_number'] ?? $app['tracking_number']); ?> • Submitted on <?php echo date('d M Y', strtotime($app['created_at'] ?? $app['submission_date'])); ?>
                    </p>
                </div>
            </div>
            <div class="text-center">
                <p class="text-xs font-bold uppercase text-slate-400 mb-1 tracking-wider">Current Status</p>
                <?php 
                    $status = strtolower($app['status'] ?? 'pending');
                    $badgeClasses = [
                        'pending' => 'bg-orange-100 text-orange-600',
                        'approved' => 'bg-green-100 text-green-600',
                        'rejected' => 'bg-red-100 text-red-600'
                    ];
                    $badgeClass = $badgeClasses[$status] ?? 'bg-slate-100 text-slate-600';
                ?>
                <span class="px-6 py-2 rounded-full text-xs font-black uppercase tracking-wide <?php echo $badgeClass; ?>">
                    <?php echo htmlspecialchars($app['status']); ?>
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <div class="md:col-span-2 space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border p-6">
        <h2 class="text-lg font-bold border-b pb-3 mb-4 text-blue-600">
            <i class="fas fa-user-graduate mr-2"></i> Learner Information
        </h2>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-slate-400 uppercase font-bold">First Name</p>
                <p class="font-semibold text-slate-700">
                    <?php echo htmlspecialchars($app['first_name'] ?? 'Not Provided'); ?>
                </p>
            </div>
            <div>
                <p class="text-xs text-slate-400 uppercase font-bold">Last Name</p>
                <p class="font-semibold text-slate-700">
                    <?php echo htmlspecialchars($app['last_name'] ?? 'Not Provided'); ?>
                </p>
            </div>
            <div>
                <p class="text-xs text-slate-400 uppercase font-bold">Grade Applying For</p>
                <p class="font-semibold text-slate-700">
                    Grade <?php echo htmlspecialchars($app['grade_applying'] ?? 'Not Provided'); ?>
                </p>
            </div>
            <div>
                <p class="text-xs text-slate-400 uppercase font-bold">Current Grade</p>
                <p class="font-semibold text-slate-700">
                    Grade <?php echo htmlspecialchars($app['current_grade'] ?? 'Not Provided'); ?>
                </p>
            </div>
            <div>
                <p class="text-xs text-slate-400 uppercase font-bold">Date of Birth</p>
                <p class="font-semibold text-slate-700">
                    <?php echo htmlspecialchars($app['dob'] ?? 'Not Provided'); ?>
                </p>
            </div>
            <div>
                <p class="text-xs text-slate-400 uppercase font-bold">Email Address</p>
                <p class="font-semibold text-slate-700">
                    <?php echo htmlspecialchars($app['email'] ?? 'Not Provided'); ?>
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border p-6">
        <h2 class="text-lg font-bold border-b pb-3 mb-4 text-blue-600">
            <i class="fas fa-file-pdf mr-2"></i> Application Documents
        </h2>
        <div class="space-y-3">
            <div>
                <p class="text-xs text-slate-400 uppercase font-bold">Generated PDF Contract</p>
                <?php if (!empty($app['pdf_form_path'])): ?>
                    <a href="../storage/uploads/<?php echo urlencode($app['pdf_form_path']); ?>" target="_blank" class="text-sm font-semibold text-blue-600 hover:underline">
                        <i class="fas fa-download mr-1"></i> View Generated PDF Form
                    </a>
                <?php else: ?>
                    <p class="text-sm font-semibold text-slate-500">No PDF Generated</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

            <div class="space-y-6">
                <div class="bg-slate-900 text-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-lg font-bold mb-4"><i class="fas fa-gavel mr-2 text-blue-400"></i> Take Action</h2>
                    <form action="update_status.php" method="POST" class="space-y-4">
                        <input type="hidden" name="app_id" value="<?php echo $app['id']; ?>">
                        <select name="new_status" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white font-medium focus:ring-2 focus:ring-blue-500">
                            <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Keep Pending</option>
                            <option value="approved" <?php echo $status == 'approved' ? 'selected' : ''; ?>>Approve Application</option>
                            <option value="rejected" <?php echo $status == 'rejected' ? 'selected' : ''; ?>>Reject Application</option>
                        </select>
                        <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 font-bold py-3 rounded-xl transition shadow-lg shadow-blue-500/20">
                            Update Status
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border p-6">
                    <h2 class="text-lg font-bold mb-4 text-slate-800"><i class="fas fa-folder-open mr-2 text-blue-500"></i> Verification Files</h2>
                    <div class="space-y-2">
                        
                  <div class="space-y-2">
    
    <?php if(!empty($app['pdf_form_path'])): ?>
        <a href="../storage/uploads/<?php echo htmlspecialchars($app['pdf_form_path']); ?>" target="_blank" class="flex items-center p-3 border rounded-xl hover:bg-slate-50 transition font-medium text-xs text-slate-700">
            <i class="fas fa-file-pdf text-red-500 text-base mr-3"></i> System Admission Form
        </a>
    <?php endif; ?>

    <?php if(!empty($app['birth_cert_path'])): ?>
        <a href="../storage/uploads/<?php echo htmlspecialchars($app['birth_cert_path']); ?>" target="_blank" class="flex items-center p-3 border rounded-xl hover:bg-slate-50 transition font-medium text-xs text-slate-700">
            <i class="fas fa-id-card text-blue-500 text-base mr-3"></i> Learner Birth Cert / ID
        </a>
    <?php else: ?>
        <div class="p-3 border border-dashed rounded-xl text-xs text-red-500 bg-red-50 font-medium"><i class="fas fa-exclamation-triangle mr-1"></i> Missing Birth Cert</div>
    <?php endif; ?>

    <?php if(!empty($app['school_report_path'])): ?>
        <a href="../storage/uploads/<?php echo htmlspecialchars($app['school_report_path']); ?>" target="_blank" class="flex items-center p-3 border rounded-xl hover:bg-slate-50 transition font-medium text-xs text-slate-700">
            <i class="fas fa-chart-bar text-purple-500 text-base mr-3"></i> Latest School Report
        </a>
    <?php else: ?>
        <div class="p-3 border border-dashed rounded-xl text-xs text-red-500 bg-red-50 font-medium"><i class="fas fa-exclamation-triangle mr-1"></i> Missing School Report</div>
    <?php endif; ?>

    <?php if(!empty($app['parent_id_path'])): ?>
        <a href="../storage/uploads/<?php echo htmlspecialchars($app['parent_id_path']); ?>" target="_blank" class="flex items-center p-3 border rounded-xl hover:bg-slate-50 transition font-medium text-xs text-slate-700">
            <i class="fas fa-user-shield text-green-500 text-base mr-3"></i> Parent / Guardian ID Copy
        </a>
    <?php else: ?>
        <div class="p-3 border border-dashed rounded-xl text-xs text-red-500 bg-red-50 font-medium"><i class="fas fa-exclamation-triangle mr-1"></i> Missing Parent ID</div>
    <?php endif; ?>

    <?php if(!empty($app['proof_res_path'])): ?>
        <a href="../storage/uploads/<?php echo htmlspecialchars($app['proof_res_path']); ?>" target="_blank" class="flex items-center p-3 border rounded-xl hover:bg-slate-50 transition font-medium text-xs text-slate-700">
            <i class="fas fa-home text-orange-500 text-base mr-3"></i> Proof of Residence
        </a>
    <?php else: ?>
        <div class="p-3 border border-dashed rounded-xl text-xs text-red-500 bg-red-50 font-medium"><i class="fas fa-exclamation-triangle mr-1"></i> Missing Proof of Res</div>
    <?php endif; ?>

</div>
                </div>
            </div>

        </div>
    </div>

</body>
</html>