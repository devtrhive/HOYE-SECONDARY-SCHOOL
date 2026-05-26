<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

try {
    // 1. Stats Counters - Using normalized conditional strings
    $totalApps = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn() ?: 0;
    $pendingApps = $pdo->query("SELECT COUNT(*) FROM applications WHERE LOWER(status) = 'pending'")->fetchColumn() ?: 0;
    $approvedApps = $pdo->query("SELECT COUNT(*) FROM applications WHERE LOWER(status) = 'approved'")->fetchColumn() ?: 0;

    // 2. Core Master Dataset Layout Pipeline query
    $sql = "SELECT id, ref_number, first_name, last_name, email, current_grade, status, created_at,
                   pdf_form_path, birth_cert_path, school_report_path, parent_id_path, proof_res_path
            FROM applications 
            ORDER BY created_at DESC";
    
    $stmt = $pdo->query($sql);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error_msg = "Database Error: " . $e->getMessage();
    $totalApps = 0;
    $pendingApps = 0;
    $approvedApps = 0;
    $applications = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoye Admin | Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .view-panel { display: none; }
        .view-panel.active { display: block; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">

    <div class="min-h-screen flex">
        <aside class="w-64 bg-slate-900 text-white hidden lg:flex flex-col">
            <div class="p-6">
                <h2 class="text-xl font-black tracking-tighter text-blue-400 uppercase">Hoye Secondary</h2>
                <p class="text-xs text-slate-400">Admin Command Center</p>
            </div>
            
            <nav class="flex-1 px-4 space-y-2 mt-4" id="sidebarNav">
                <button onclick="switchPanel('dashboard-view', this)" class="w-full flex items-center p-3 bg-blue-600 rounded-xl text-white font-semibold text-left transition nav-btn">
                    <i class="fas fa-chart-line w-6"></i> Dashboard
                </button>
                <button onclick="switchPanel('applicants-view', this)" class="w-full flex items-center p-3 text-slate-400 hover:bg-slate-800 hover:text-white rounded-xl font-semibold text-left transition nav-btn">
                    <i class="fas fa-user-graduate w-6"></i> All Applicants
                </button>
                <button onclick="switchPanel('documents-view', this)" class="w-full flex items-center p-3 text-slate-400 hover:bg-slate-800 hover:text-white rounded-xl font-semibold text-left transition nav-btn">
                    <i class="fas fa-folder-open w-6"></i> Documents Vault
                </button>
            </nav>

            <div class="p-6 border-t border-slate-800">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-xs font-bold text-white">
                        <?php echo isset($_SESSION['admin_name']) ? substr($_SESSION['admin_name'], 0, 1) : 'A'; ?>
                    </div>
                    <div class="truncate text-sm">
                        <p class="font-bold"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></p>
                        <p class="text-slate-500 text-xs italic">Administrator</p>
                    </div>
                </div>
                <a href="logout.php" class="block w-full text-center py-2 bg-slate-800 hover:bg-red-600 text-white text-xs font-bold rounded-lg transition">
                    LOGOUT
                </a>
            </div>
        </aside>

        <main class="flex-1 p-6 lg:p-10 overflow-y-auto">
            
            <?php if (isset($error_msg)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl mb-6 flex items-center gap-3">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($error_msg); ?></p>
                </div>
            <?php endif; ?>

            <div id="dashboard-view" class="view-panel active">
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 gap-4">
                    <div>
                        <h1 class="text-3xl font-extrabold tracking-tight">Admissions Dashboard</h1>
                        <p class="text-slate-500">Welcome back, here is what is happening with applications today.</p>
                    </div>
                    <button onclick="window.location.reload()" class="flex items-center justify-center bg-white border px-4 py-2 rounded-xl shadow-sm hover:bg-slate-50 transition">
                        <i class="fas fa-sync-alt mr-2 text-blue-500"></i> Refresh Data
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 relative overflow-hidden">
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Total Received</p>
                        <h3 class="text-4xl font-black"><?php echo $totalApps; ?></h3>
                        <div class="absolute -right-2 -bottom-2 opacity-5 text-6xl"><i class="fas fa-file-invoice"></i></div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 relative overflow-hidden">
                        <p class="text-orange-500 text-xs font-bold uppercase tracking-wider mb-1">Pending Review</p>
                        <h3 class="text-4xl font-black text-orange-600"><?php echo $pendingApps; ?></h3>
                        <div class="absolute -right-2 -bottom-2 opacity-10 text-6xl text-orange-500"><i class="fas fa-clock"></i></div>
                    </div>

                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 relative overflow-hidden">
                        <p class="text-green-500 text-xs font-bold uppercase tracking-wider mb-1">Approved Students</p>
                        <h3 class="text-4xl font-black text-green-600"><?php echo $approvedApps; ?></h3>
                        <div class="absolute -right-2 -bottom-2 opacity-10 text-6xl text-green-500"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-slate-50 flex flex-col md:flex-row justify-between items-center gap-4">
                        <h2 class="text-xl font-bold">Recent Submissions</h2>
                        <div class="relative w-full md:w-64">
                            <i class="fas fa-search absolute left-3 top-3 text-slate-400 text-sm"></i>
                            <input type="text" id="adminSearch" placeholder="Search by name or ref..." class="w-full pl-10 pr-4 py-2 bg-slate-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse" id="applicationsTable">
                            <thead class="bg-slate-50 text-slate-400 text-[10px] uppercase font-black tracking-widest">
                                <tr>
                                    <th class="p-6">Reference</th>
                                    <th class="p-6">Applicant</th>
                                    <th class="p-6">Grade</th>
                                    <th class="p-6">Date</th>
                                    <th class="p-6">Status</th>
                                    <th class="p-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php if(empty($applications)): ?>
                                    <tr>
                                        <td colspan="6" class="p-20 text-center text-slate-400">
                                            <i class="fas fa-inbox text-4xl mb-4 block"></i>
                                            No applications found yet.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($applications as $app): ?>
                                    <tr class="group hover:bg-slate-50/50 transition table-row-item">
                                        <td class="p-6">
                                            <span class="font-mono font-bold text-blue-600 bg-blue-50 px-3 py-1 rounded-lg text-sm ref-cell">
                                                <?php echo htmlspecialchars($app['ref_number']); ?>
                                            </span>
                                        </td>
                                        <td class="p-6">
                                            <div class="font-bold text-slate-800 name-cell"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></div>
                                            <div class="text-xs text-slate-400"><?php echo htmlspecialchars($app['email']); ?></div>
                                        </td>
                                        <td class="p-6 text-sm font-medium text-slate-600">
                                            Grade <?php echo htmlspecialchars($app['current_grade']); ?>
                                        </td>
                                        <td class="p-6 text-sm text-slate-400">
                                            <?php echo date('d M Y', strtotime($app['created_at'])); ?>
                                        </td>
                                        <td class="p-6">
                                            <?php 
                                                $statusClasses = [
                                                    'pending' => 'bg-orange-100 text-orange-600',
                                                    'approved' => 'bg-green-100 text-green-600',
                                                    'rejected' => 'bg-red-100 text-red-600'
                                                ];
                                                $status = strtolower($app['status'] ?? 'pending');
                                                $class = $statusClasses[$status] ?? 'bg-slate-100 text-slate-600';
                                            ?>
                                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter <?php echo $class; ?>">
                                                <?php echo htmlspecialchars($app['status']); ?>
                                            </span>
                                        </td>
                                        <td class="p-6 text-right">
                                            <a href="view_application.php?id=<?php echo $app['id']; ?>" class="bg-slate-900 text-white text-xs px-4 py-2 rounded-xl font-bold hover:bg-blue-600 transition shadow-lg shadow-slate-200">
                                                VIEW PROFILE
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="applicants-view" class="view-panel">
                <div class="mb-6">
                    <h1 class="text-3xl font-extrabold tracking-tight">All Enrolled Applicants</h1>
                    <p class="text-slate-500">Complete institutional record of student applications profiles layout matrix.</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if(empty($applications)): ?>
                        <div class="col-span-full bg-white p-12 text-center text-slate-400 rounded-3xl border">
                            <i class="fas fa-user-slash text-4xl mb-3 block"></i> No records available.
                        </div>
                    <?php else: ?>
                        <?php foreach($applications as $app): ?>
                            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex flex-col justify-between">
                                <div>
                                    <div class="flex justify-between items-start mb-4">
                                        <span class="text-[10px] font-black uppercase bg-blue-50 text-blue-600 px-2 py-1 rounded">
                                            Grade <?php echo htmlspecialchars($app['current_grade']); ?>
                                        </span>
                                        <span class="text-xs font-mono font-semibold text-slate-400">
                                            <?php echo htmlspecialchars($app['ref_number']); ?>
                                        </span>
                                    </div>
                                    <h4 class="text-lg font-bold text-slate-800"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></h4>
                                    <p class="text-sm text-slate-500 mb-2 truncate"><i class="far fa-envelope mr-1"></i><?php echo htmlspecialchars($app['email']); ?></p>
                                    <p class="text-xs text-slate-400"><i class="far fa-calendar-alt mr-1"></i> Submitted: <?php echo date('d M Y', strtotime($app['created_at'])); ?></p>
                                </div>
                                <div class="mt-6 pt-4 border-t flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500"><?php echo htmlspecialchars($app['status']); ?></span>
                                    <a href="view_application.php?id=<?php echo $app['id']; ?>" class="text-xs font-bold text-blue-600 hover:text-blue-800 transition">
                                        Open Record <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div id="documents-view" class="view-panel">
                <div class="mb-6">
                    <h1 class="text-3xl font-extrabold tracking-tight">Documents Vault</h1>
                    <p class="text-slate-500">Central localized storage management cluster for tracking student files.</p>
                </div>

                <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-50 text-slate-400 text-[10px] uppercase font-black tracking-widest">
                                <tr>
                                    <th class="p-6">Applicant Name</th>
                                    <th class="p-6">System Application Form</th>
                                    <th class="p-6">Birth Certificate</th>
                                    <th class="p-6">School Report</th>
                                    <th class="p-6">Parent ID File</th>
                                    <th class="p-6">Proof of Residence</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <?php if(empty($applications)): ?>
                                    <tr>
                                        <td colspan="6" class="p-12 text-center text-slate-400">No verification media uploaded yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($applications as $app): ?>
                                        <tr class="hover:bg-slate-50/50 transition">
                                            <td class="p-6 font-bold text-slate-800">
                                                <?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?>
                                                <div class="text-[10px] font-mono font-normal text-slate-400"><?php echo htmlspecialchars($app['ref_number']); ?></div>
                                            </td>
                                            
                                            <td class="p-6">
                                                <?php if(!empty($app['pdf_form_path'])): ?>
                                                    <a href="../storage/uploads/<?php echo htmlspecialchars($app['pdf_form_path']); ?>" target="_blank" class="text-blue-600 hover:underline inline-flex items-center"><i class="far fa-file-pdf mr-1 text-red-500 text-base"></i> View Form</a>
                                                <?php else: ?><span class="text-slate-300 italic text-xs">Missing</span><?php endif; ?>
                                            </td>

                                            <td class="p-6">
                                                <?php if(!empty($app['birth_cert_path'])): ?>
                                                    <a href="../storage/uploads/<?php echo htmlspecialchars($app['birth_cert_path']); ?>" target="_blank" class="text-blue-600 hover:underline inline-flex items-center"><i class="fas fa-id-card mr-1 text-blue-500"></i> Certificate</a>
                                                <?php else: ?><span class="text-slate-300 italic text-xs">Missing</span><?php endif; ?>
                                            </td>

                                            <td class="p-6">
                                                <?php if(!empty($app['school_report_path'])): ?>
                                                    <a href="../storage/uploads/<?php echo htmlspecialchars($app['school_report_path']); ?>" target="_blank" class="text-blue-600 hover:underline inline-flex items-center"><i class="fas fa-graduation-cap mr-1 text-purple-500"></i> Report Card</a>
                                                <?php else: ?><span class="text-slate-300 italic text-xs">Missing</span><?php endif; ?>
                                            </td>

                                            <td class="p-6">
                                                <?php if(!empty($app['parent_id_path'])): ?>
                                                    <a href="../storage/uploads/<?php echo htmlspecialchars($app['parent_id_path']); ?>" target="_blank" class="text-blue-600 hover:underline inline-flex items-center"><i class="fas fa-user-shield mr-1 text-emerald-500"></i> Parent ID</a>
                                                <?php else: ?><span class="text-slate-300 italic text-xs">Missing</span><?php endif; ?>
                                            </td>

                                            <td class="p-6">
                                                <?php if(!empty($app['proof_res_path'])): ?>
                                                    <a href="../storage/uploads/<?php echo htmlspecialchars($app['proof_res_path']); ?>" target="_blank" class="text-blue-600 hover:underline inline-flex items-center"><i class="fas fa-home mr-1 text-amber-500"></i> Proof Res</a>
                                                <?php else: ?><span class="text-slate-300 italic text-xs">Missing</span><?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        // 1. LIVE SEARCH FILTRATION FUNCTION (Fixed Cell Index Processing Mapping)
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('adminSearch');
            
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const value = this.value.toLowerCase().trim();
                    const rows = document.querySelectorAll('#applicationsTable tbody .table-row-item');
                    
                    rows.forEach(row => {
                        const refElement = row.querySelector('.ref-cell');
                        const nameElement = row.querySelector('.name-cell');
                        
                        const refText = refElement ? refElement.textContent.toLowerCase() : '';
                        const nameText = nameElement ? nameElement.textContent.toLowerCase() : '';
                        
                        if (nameText.includes(value) || refText.includes(value)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });

        // 2. SIDEBAR PANEL SWITCH ROUTING CONTROLLER
        function switchPanel(panelId, clickTarget) {
            // Hide all active dashboard views
            document.querySelectorAll('.view-panel').forEach(panel => {
                panel.classList.remove('active');
            });
            
            // Show target panel mapping selection
            const targetPanel = document.getElementById(panelId);
            if (targetPanel) {
                targetPanel.classList.add('active');
            }
            
            // Toggle sidebar active style layouts
            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white');
                btn.classList.add('text-slate-400', 'hover:bg-slate-800', 'hover:text-white');
            });
            
            clickTarget.classList.add('bg-blue-600', 'text-white');
            clickTarget.classList.remove('text-slate-400', 'hover:bg-slate-800', 'hover:text-white');
        }
    </script>
</body>
</html>