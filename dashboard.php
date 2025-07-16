<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];

// Obtain user donations
$stmt = $pdo->prepare("
    SELECT d.*, c.name AS category_name 
    FROM donations d
    JOIN categories c ON d.category_id = c.id
    WHERE d.user_id = ?
    ORDER BY d.created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$user_donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtain user requests
$stmt = $pdo->prepare("
    SELECT r.*, c.name AS category_name 
    FROM requests r
    JOIN categories c ON r.category_id = c.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$user_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get matching records
$stmt = $pdo->prepare("
    SELECT m.*, d.title AS donation_title, r.title AS request_title
    FROM matches m
    JOIN donations d ON m.donation_id = d.id
    JOIN requests r ON m.request_id = r.id
    WHERE d.user_id = ? OR r.user_id = ?
    ORDER BY m.matched_at DESC
    LIMIT 5
");
$stmt->execute([$user_id, $user_id]);
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Your Dashboard</h2>
        <div>
            <a href="create_donation.php" class="btn btn-primary me-2">Donate Item</a>
            <a href="create_request.php" class="btn btn-outline-primary">Request Item</a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="row mb-4">
                <div class="col-md-6 mb-4 mb-md-0">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Recent Donations</h5>
                                <a href="donations.php?user=<?= $user_id ?>" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>

                            <?php if (empty($user_donations)): ?>
                                <div class="alert alert-info mb-0">You haven't donated any items yet.</div>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($user_donations as $donation): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <div>
                                                <h6 class="mb-1"><?= htmlspecialchars($donation['title']) ?></h6>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-primary me-2"><?= htmlspecialchars($donation['category_name']) ?></span>
                                                    <span class="badge bg-<?= 
                                                        $donation['status'] === 'available' ? 'success' : 
                                                        ($donation['status'] === 'pending' ? 'warning' : 'info')
                                                    ?>">
                                                        <?= ucfirst($donation['status']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <a href="donation_detail.php?id=<?= $donation['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                View
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Recent Requests</h5>
                                <a href="requests.php?user=<?= $user_id ?>" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>

                            <?php if (empty($user_requests)): ?>
                                <div class="alert alert-info mb-0">You haven't requested any items yet.</div>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($user_requests as $request): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <div>
                                                <h6 class="mb-1"><?= htmlspecialchars($request['title']) ?></h6>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-info me-2"><?= htmlspecialchars($request['category_name']) ?></span>
                                                    <span class="badge bg-<?= $request['status'] === 'pending' ? 'warning' : 'success' ?>">
                                                        <?= ucfirst($request['status']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <a href="request_detail.php?id=<?= $request['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                View
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Recent Matches</h5>
                        <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>

                    <?php if (empty($matches)): ?>
                        <div class="alert alert-info mb-0">You don't have any matches yet.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Donation</th>
                                        <th>Request</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($matches as $match): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($match['donation_title']) ?></td>
                                            <td><?= htmlspecialchars($match['request_title']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $match['status'] === 'pending' ? 'warning' : 'success' ?>">
                                                    <?= ucfirst($match['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($match['matched_at'])) ?></td>
                                            <td>
                                                <a href="match_detail.php?id=<?= $match['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">Quick Actions</h5>

                    <div class="d-grid gap-3">
                        <a href="create_donation.php" class="btn btn-primary btn-lg py-3">
                            <i class="bi bi-box-seam me-2"></i> Donate an Item
                        </a>

                        <a href="create_request.php" class="btn btn-outline-primary btn-lg py-3">
                            <i class="bi bi-cart-plus me-2"></i> Request an Item
                        </a>

                        <a href="profile.php" class="btn btn-light btn-lg py-3">
                            <i class="bi bi-person me-2"></i> Edit Profile
                        </a>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">Account Summary</h5>

                    <div class="d-flex align-items-center mb-4">
                        <img src="<?= htmlspecialchars($_SESSION['profile_image'] ?: 'assets/images/default-profile.png') ?>" 
                             alt="Profile" class="rounded-circle me-3" width="80" height="80">
                        <div>
                            <h5 class="mb-1"><?= htmlspecialchars($_SESSION['full_name']) ?></h5>
                            <p class="text-muted mb-0"><?= htmlspecialchars($_SESSION['email']) ?></p>
                            <span class="badge bg-<?= $_SESSION['user_role'] === 'admin' ? 'danger' : 'primary' ?>">
                                <?= ucfirst($_SESSION['user_role']) ?>
                            </span>
                        </div>
                    </div>

                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Total Donations</span>
                            <span class="badge bg-primary rounded-pill">
                                <?= count($user_donations) ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Total Requests</span>
                            <span class="badge bg-info rounded-pill">
                                <?= count($user_requests) ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Successful Matches</span>
                            <span class="badge bg-success rounded-pill">
                                <?= count(array_filter($matches, function($match) { 
                                    return $match['status'] === 'completed'; 
                                })) ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Member Since</span>
                            <span>
                                <?= date('M Y', strtotime('-1 year')) // 实际应用中应从数据库获取 ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>