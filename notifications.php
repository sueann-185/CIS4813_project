<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/notifications.php';
redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];

// Mark all notifications as read
if (isset($_GET['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $_SESSION['success'] = "All notifications marked as read.";
    header('Location: notifications.php');
    exit;
}

// Get user notifications
$notifications = get_user_notifications($user_id, 50);

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Notifications</h2>
        <div>
            <a href="notifications.php?mark_all_read" class="btn btn-outline-primary">
                <i class="bi bi-check-all me-1"></i> Mark All as Read
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (empty($notifications)): ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-bell fs-1 text-muted mb-3"></i>
                <h4 class="text-muted">No notifications yet</h4>
                <p class="text-muted">You'll see important updates here when they happen.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($notifications as $notification): ?>
                        <li class="list-group-item <?= $notification['is_read'] ? '' : 'bg-light' ?>">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <?php if ($notification['is_read']): ?>
                                        <i class="bi bi-bell fs-4 text-muted"></i>
                                    <?php else: ?>
                                        <i class="bi bi-bell-fill fs-4 text-primary"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-1"><?= htmlspecialchars($notification['message']) ?></h6>
                                        <small class="text-muted">
                                            <?= date('M d, H:i', strtotime($notification['created_at'])) ?>
                                        </small>
                                    </div>

                                    <?php if ($notification['related_id'] && $notification['type'] === 'match_confirmed'): ?>
                                        <div class="mt-2">
                                            <a href="match_detail.php?id=<?= $notification['related_id'] ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                View Match Details
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>