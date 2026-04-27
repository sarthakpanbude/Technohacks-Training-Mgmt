<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Reports & Analytics";
$activePage = "reports";

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Reports & Analytics</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm"><i class="fas fa-file-pdf me-2"></i>Export PDF</button>
            <button class="btn btn-outline-secondary btn-sm"><i class="fas fa-file-excel me-2"></i>Export Excel</button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="stat-card">
                <h6 class="fw-bold mb-4">Fee Collection (Last 6 Months)</h6>
                <canvas id="feeReportChart" height="250"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card">
                <h6 class="fw-bold mb-4">Course-wise Enrollment</h6>
                <canvas id="courseReportChart" height="250"></canvas>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fee Report
    new Chart(document.getElementById('feeReportChart'), {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Revenue',
                data: [120000, 150000, 110000, 180000, 160000, 210000],
                backgroundColor: '#2563eb'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });

    // Course Report
    new Chart(document.getElementById('courseReportChart'), {
        type: 'pie',
        data: {
            labels: ['Web Dev', 'Data Science', 'Python', 'Java'],
            datasets: [{
                data: [45, 25, 20, 10],
                backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#ef4444']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { position: 'bottom' }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
