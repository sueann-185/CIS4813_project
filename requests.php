<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
include 'includes/header.php';

// Check if only specific user requests are displayed
$user_filter = '';
$params = [];
if (isset($_GET['user']) && is_numeric($_GET['user'])) {
    $user_filter = "WHERE r.user_id = ?";
    $params = [$_GET['user']];
}

// Get request list
$stmt = $pdo->prepare("
    SELECT r.*, u.full_name, u.profile_image, c.name AS category_name 
    FROM requests r
    JOIN users u ON r.user_id = u.id
    JOIN categories c ON r.category_id = c.id
    $user_filter
    ORDER BY r.created_at DESC
");
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Item Requests</h2>
        <?php if (is_logged_in()): ?>
            <a href="create_request.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Create Request
            </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['user'])): ?>
        <div class="alert alert-info mb-4">
            Showing requests for a specific user
        </div>
    <?php endif; ?>

    <div class="row">
        <?php if (empty($requests)): ?>
            <div class="col-12">
                <div class="alert alert-info">No requests available at the moment.</div>
            </div>
        <?php else: ?>
            <?php foreach ($requests as $request): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($request['title']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars(mb_substr($request['description'], 0, 100)) ?>...</p>

                            <div class="d-flex align-items-center mt-3 mb-2">
                                <img src="<?= htmlspecialchars($request['profile_image']) ?>" 
                                     class="rounded-circle me-2" 
                                     width="30" 
                                     height="30" 
                                     alt="Requester">
                                <span><?= htmlspecialchars($request['full_name']) ?></span>
                            </div>

                            <div class="mb-3">
                                <span class="badge bg-info">
                                    <?= htmlspecialchars($request['category_name']) ?>
                                </span>
                                <span class="badge bg-<?= $request['status'] === 'pending' ? 'warning' : 'success' ?>">
                                    <?= ucfirst($request['status']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <a href="request_detail.php?id=<?= $request['id'] ?>" class="btn btn-outline-primary w-100">
                                View Request Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>