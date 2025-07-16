<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirect_if_not_admin();

// 获取分析数据
$stmt = $pdo->query("SELECT COUNT(*) AS total_users FROM users");
$total_users = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) AS active_users FROM users WHERE status = 'active'");
$active_users = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) AS total_donations FROM donations");
$total_donations = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) AS matched_donations FROM donations WHERE status = 'matched'");
$matched_donations = $stmt->fetchColumn();

// Obtain donation data by category
$stmt = $pdo->query("
    SELECT c.name, COUNT(d.id) AS count 
    FROM donations d
    JOIN categories c ON d.category_id = c.id
    GROUP BY c.name
    ORDER BY count DESC
");
$donation_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve the most recently registered user
$stmt = $pdo->query("SELECT full_name, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container py-5">
    <h2 class="mb-4">Analytics Dashboard</h2>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white shadow">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <p class="display-4"><?= $total_users ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-success text-white shadow">
                <div class="card-body">
                    <h5 class="card-title">Active Users</h5>
                    <p class="display-4"><?= $active_users ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-info text-white shadow">
                <div class="card-body">
                    <h5 class="card-title">Total Donations</h5>
                    <p class="display-4"><?= $total_donations ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-warning text-dark shadow">
                <div class="card-body">
                    <h5 class="card-title">Matched Donations</h5>
                    <p class="display-4"><?= $matched_donations ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 mb-4">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Donations by Category</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Users</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php foreach ($recent_users as $user): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($user['full_name']) ?>
                                <span class="badge bg-light text-dark">
                                    <?= date('M d', strtotime($user['created_at'])) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    const categories = <?= json_encode(array_column($donation_categories, 'name')) ?>;
    const counts = <?= json_encode(array_column($donation_categories, 'count')) ?>;

    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: categories,
            datasets: [{
                label: 'Donations by Category',
                data: counts,
                backgroundColor: [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', 
                    '#e74a3b', '#858796', '#5a5c69', '#3a3b45'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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