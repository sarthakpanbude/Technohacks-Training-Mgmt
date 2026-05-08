<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Enrollment Review";
$activePage = "enrollment_review";

// Handle Filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$where_clauses = [];
$params = [];

if ($search) {
    $where_clauses[] = "(u.full_name LIKE ? OR s.enrollment_no LIKE ? OR s.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status) {
    $where_clauses[] = "s.admission_status = ?";
    $params[] = $status;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Fetch Students with their latest receipt and fee info
$query = "
    SELECT 
        s.id as main_student_id,
        s.enrollment_no,
        u.full_name,
        u.email,
        s.phone,
        s.admission_status,
        b.batch_name,
        sf.total_fee,
        sf.paid_fee,
        sf.pending_fee,
        (SELECT receipt_no FROM invoices WHERE student_id = s.id ORDER BY created_at DESC LIMIT 1) as receipt_no
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN student_fees sf ON sf.student_id = s.enrollment_no
    LEFT JOIN batches b ON s.batch_id = b.id
    $where_sql
    ORDER BY s.created_at DESC
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100 p-4">
    <?php include '../includes/topbar.php'; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_GET['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">Enrollment Review</h3>
            <p class="text-muted small">Verify and manage recently admitted students</p>
        </div>
        <div class="d-flex gap-2">
            <!-- Filter Toggle (Optional, can just show search bar) -->
            <div class="dropdown">
                <button class="btn btn-white border-0 shadow-sm rounded-pill px-4 dropdown-toggle" type="button"
                    data-bs-toggle="dropdown">
                    <i class="fas fa-download me-2 text-primary"></i>Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 p-2">
                    <li><a class="dropdown-item rounded-3"
                            href="actions/export_enrollment.php?format=excel&<?php echo $_SERVER['QUERY_STRING']; ?>"><i
                                class="fas fa-file-excel me-2 text-success"></i>Export to Excel</a></li>
                    <li><a class="dropdown-item rounded-3" href="#" onclick="window.print()"><i
                                class="fas fa-file-pdf me-2 text-danger"></i>Export to PDF</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
        <form method="GET" class="row g-3 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 rounded-start-pill ps-3"><i
                            class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 rounded-end-pill"
                        placeholder="Search by name, enrollment or phone..."
                        value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select rounded-pill">
                    <option value="">All Statuses</option>
                    <option value="enrolled" <?php echo $status == 'enrolled' ? 'selected' : ''; ?>>Enrolled</option>
                    <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="verified" <?php echo $status == 'verified' ? 'selected' : ''; ?>>Verified</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 flex-grow-1">Apply Filters</button>
                <a href="enrollment_review.php" class="btn btn-light rounded-pill px-3" title="Reset"><i
                        class="fas fa-redo"></i></a>
            </div>
        </form>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 border-0 small fw-bold text-uppercase text-muted">Student Details</th>
                        <th class="py-3 border-0 small fw-bold text-uppercase text-muted">Receipt #</th>
                        <th class="py-3 border-0 small fw-bold text-uppercase text-muted">Fee Status</th>
                        <th class="py-3 border-0 small fw-bold text-uppercase text-muted">Batch</th>
                        <th class="py-3 border-0 small fw-bold text-uppercase text-muted">Status</th>
                        <th class="py-3 border-0 small fw-bold text-uppercase text-muted text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td class="px-4 py-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3"
                                        style="width: 40px; height: 40px;">
                                        <?php echo strtoupper(substr($s['full_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold mb-0"><?php echo htmlspecialchars($s['full_name']); ?></div>
                                        <div class="text-muted small"><?php echo $s['enrollment_no']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3">
                                <?php if ($s['receipt_no']): ?>
                                    <span class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill fw-medium">
                                        <i class="fas fa-receipt me-1"></i> <?php echo $s['receipt_no']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">No Receipt</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3">
                                <div class="small">
                                    <div class="text-success fw-bold">Paid:
                                        ₹<?php echo number_format($s['paid_fee'] ?? 0, 0); ?></div>
                                    <div class="text-danger">Pending:
                                        ₹<?php echo number_format($s['pending_fee'] ?? 0, 0); ?></div>
                                </div>
                            </td>
                            <td class="py-3">
                                <span class="small fw-bold <?php echo $s['batch_name'] ? 'text-primary' : 'text-muted'; ?>">
                                    <?php echo $s['batch_name'] ?: 'Not Assigned'; ?>
                                </span>
                            </td>
                            <td class="py-3">
                                <?php
                                $badgeClass = 'bg-success';
                                if ($s['admission_status'] == 'pending')
                                    $badgeClass = 'bg-warning';
                                ?>
                                <span
                                    class="badge <?php echo $badgeClass; ?> bg-opacity-10 text-<?php echo str_replace('bg-', '', $badgeClass); ?> px-3 py-2 rounded-pill text-capitalize">
                                    <?php echo $s['admission_status']; ?>
                                </span>
                            </td>
                            <td class="py-3 text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <div class="btn-group shadow-sm rounded-pill overflow-hidden bg-white border">
                                        <a href="generate_receipt.php?id=<?php echo $s['enrollment_no']; ?>" target="_blank"
                                            class="btn btn-sm btn-white border-0 px-3" title="Print Receipt">
                                            <i class="fas fa-print text-primary"></i>
                                        </a>
                                        <a href="generate_form.php?id=<?php echo $s['enrollment_no']; ?>" target="_blank"
                                            class="btn btn-sm btn-white border-0 px-3 border-start" title="Download PDF">
                                            <i class="fas fa-file-pdf text-danger"></i>
                                        </a>
                                        <?php
                                        $wa_msg = urlencode("Hello " . $s['full_name'] . ", your enrollment is confirmed. Receipt: " . ($s['receipt_no'] ?? 'N/A'));
                                        ?>
                                        <a href="https://wa.me/<?php echo $s['phone']; ?>?text=<?php echo $wa_msg; ?>"
                                            target="_blank" class="btn btn-sm btn-white border-0 px-3 border-start"
                                            title="WhatsApp Share">
                                            <i class="fab fa-whatsapp text-success"></i>
                                        </a>
                                    </div>
                                    <?php if ($s['admission_status'] != 'active'): ?>
                                        <a href="assign_batch.php?student_id=<?php echo $s['main_student_id']; ?>"
                                            class="btn btn-success btn-sm rounded-pill px-3 shadow-sm">
                                            <i class="fas fa-toggle-on me-1"></i> Active
                                        </a>
                                    <?php else: ?>
                                        <span
                                            class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill fw-bold">
                                            <i class="fas fa-check-circle me-1"></i> Active Student
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>


<style>
    .btn-white:hover {
        background-color: #f8f9fa;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, .01);
    }

    @media print {

        .sidebar,
        .topbar,
        .filter-bar,
        .btn-group,
        .dropdown,
        .card.border-0.shadow-sm.p-3.mb-4,
        .main-content>div:first-child {
            display: none !important;
        }

        .main-content {
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
        }

        .card {
            box-shadow: none !important;
            border: 1px solid #eee !important;
        }

        .table {
            width: 100% !important;
        }

        body {
            background: white !important;
        }

        .badge {
            border: 1px solid #ccc !important;
            color: black !important;
            background: transparent !important;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>