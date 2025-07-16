<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_admin();

// 获取统计数据
$stmt = $pdo->query("SELECT COUNT(*) AS total_users FROM users");
$total_users = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) AS active_users FROM users WHERE status = 'active'");
$active_users = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) AS total_donations FROM donations");
$total_donations = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) AS pending_donations FROM donations WHERE status = 'available'");
$pending_donations = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) AS total_requests FROM requests");
$total_requests = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) AS pending_requests FROM requests WHERE status = 'pending'");
$pending_requests = $stmt->fetchColumn();

// 获取最新注册用户
$stmt = $pdo->query("SELECT full_name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取最新捐赠
$stmt = $pdo->query("
    SELECT d.title, d.created_at, u.full_name 
    FROM donations d
    JOIN users u ON d.user_id = u.id
    ORDER BY d.created_at DESC 
    LIMIT 5
");
$recent_donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container py-5">
    <h2 class="mb-4">Admin Dashboard</h2>
    
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-primary border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase text-primary fw-bold small">Total Users</div>
                            <div class="fs-3 fw-bold"><?= $total_users ?></div>
                        </div>
                        <div class="bg-primary text-white p-3 rounded-circle">
                            <i class="bi bi-people fs-2"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-success fw-bold">
                            <i class="bi bi-arrow-up"></i> 12.3%
                        </span>
                        <span class="text-muted ms-2">since last month</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-success border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase text-success fw-bold small">Active Users</div>
                            <div class="fs-3 fw-bold"><?= $active_users ?></div>
                        </div>
                        <div class="bg-success text-white p-3 rounded-circle">
                            <i class="bi bi-person-check fs-2"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-success fw-bold">
                            <i class="bi bi-arrow-up"></i> 8.7%
                        </span>
                        <span class="text-muted ms-2">since last month</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-info border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase text-info fw-bold small">Pending Donations</div>
                            <div class="fs-3 fw-bold"><?= $pending_donations ?></div>
                        </div>
                        <div class="bg-info text-white p-3 rounded-circle">
                            <i class="bi bi-box-seam fs-2"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-danger fw-bold">
                            <i class="bi bi-arrow-down"></i> 3.2%
                        </span>
                        <span class="text-muted ms-2">since last week</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-warning border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase text-warning fw-bold small">Pending Requests</div>
                            <div class="fs-3 fw-bold"><?= $pending_requests ?></div>
                        </div>
                        <div class="bg-warning text-white p-3 rounded-circle">
                            <i class="bi bi-cart fs-2"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-success fw-bold">
                            <i class="bi bi-arrow-up"></i> 5.6%
                        </span>
                        <span class="text-muted ms-2">since last week</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">System Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-4">
                                <div class="bg-light p-3 rounded me-3">
                                    <i class="bi bi-people fs-1 text-primary"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?= $total_users ?></h3>
                                    <p class="text-muted mb-0">Registered Users</p>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center mb-4">
                                <div class="bg-light p-3 rounded me-3">
                                    <i class="bi bi-box-seam fs-1 text-info"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?= $total_donations ?></h3>
                                    <p class="text-muted mb-0">Total Donations</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-4">
                                <div class="bg-light p-3 rounded me-3">
                                    <i class="bi bi-cart fs-1 text-warning"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?= $total_requests ?></h3>
                                    <p class="text-muted mb-0">Total Requests</p>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center">
                                <div class="bg-light p-3 rounded me-3">
                                    <i class="bi bi-arrow-repeat fs-1 text-success"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0"><?= round($total_donations / max($total_requests, 1) * 100) ?>%</h3>
                                    <p class="text-muted mb-0">Donation/Request Ratio</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <canvas id="activityChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Users</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recent_users as $user): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light p-2 rounded me-3">
                                        <i class="bi bi-person fs-4"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0"><?= htmlspecialchars($user['full_name']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                    </div>
                                </div>
                                <span class="badge bg-light text-dark">
                                    <?= date('M d', strtotime($user['created_at'])) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Donations</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recent_donations as $donation): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="mb-0"><?= htmlspecialchars($donation['title']) ?></h6>
                                    <span class="badge bg-light text-dark">
                                        <?= date('M d', strtotime($donation['created_at'])) ?>
                                    </span>
                                </div>
                                <small class="text-muted">By <?= htmlspecialchars($donation['full_name']) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Quick Actions</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <a href="users.php" class="card card-hover text-center text-decoration-none h-100">
                        <div class="card-body">
                            <div class="bg-primary text-white p-3 rounded-circle mb-3 mx-auto" style="width: 70px; height: 70px;">
                                <i class="bi bi-people fs-2"></i>
                            </div>
                            <h6 class="mb-0">User Management</h6>
                        </div>
                    </a>
                </div>
                
                <div class="col-md-3">
                    <a href="donations.php" class="card card-hover text-center text-decoration-none h-100">
                        <div class="card-body">
                            <div class="bg-info text-white p-3 rounded-circle mb-3 mx-auto" style="width: 70px; height: 70px;">
                                <i class="bi bi-box-seam fs-2"></i>
                            </div>
                            <h6 class="mb-0">Donation Moderation</h6>
                        </div>
                    </a>
                </div>
                
                <div class="col-md-3">
                    <a href="requests.php" class="card card-hover text-center text-decoration-none h-100">
                        <div class="card-body">
                            <div class="bg-warning text-white p-3 rounded-circle mb-3 mx-auto" style="width: 70px; height: 70px;">
                                <i class="bi bi-cart fs-2"></i>
                            </div>
                            <h6 class="mb-0">Request Moderation</h6>
                        </div>
                    </a>
                </div>
                
                <div class="col-md-3">
                    <a href="analytics.php" class="card card-hover text-center text-decoration-none h-100">
                        <div class="card-body">
                            <div class="bg-success text-white p-3 rounded-circle mb-3 mx-auto" style="width: 70px; height: 70px;">
                                <i class="bi bi-bar-chart fs-2"></i>
                            </div>
                            <h6 class="mb-0">View Analytics</h6>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('activityChart').getContext('2d');
    
    const labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const donationsData = [12, 19, 15, 22, 18, 25, 30];
    const requestsData = [8, 12, 10, 15, 14, 20, 22];
    const usersData = [5, 8, 6, 10, 12, 15, 18];
    
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Donations',
                    data: donationsData,
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                },
                {
                    label: 'Requests',
                    data: requestsData,
                    borderColor: '#f6c23e',
                    backgroundColor: 'rgba(246, 194, 62, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                },
                {
                    label: 'New Users',
                    data: usersData,
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>