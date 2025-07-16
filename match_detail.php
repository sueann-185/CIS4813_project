<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
redirect_if_not_logged_in();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$match_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$is_admin = is_admin();

// Get match details - allow access for involved users or admins
$stmt = $pdo->prepare("
    SELECT m.*, 
           d.title AS donation_title, d.description AS donation_desc, d.image AS donation_image,
           r.title AS request_title, r.description AS request_desc,
           du.full_name AS donor_name, du.email AS donor_email, du.address AS donor_address,
           ru.full_name AS requester_name, ru.email AS requester_email, ru.address AS requester_address
    FROM matches m
    JOIN donations d ON m.donation_id = d.id
    JOIN requests r ON m.request_id = r.id
    JOIN users du ON m.donation_user_id = du.id
    JOIN users ru ON m.request_user_id = ru.id
    WHERE m.id = ? 
    AND (m.donation_user_id = ? OR m.request_user_id = ? OR ? = 1) -- Allow admin access
");
$stmt->execute([$match_id, $user_id, $user_id, $is_admin]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$match) {
    header('Location: dashboard.php');
    exit;
}

$is_donor = ($match['donation_user_id'] == $user_id);
$counterparty = $is_donor ? $match['requester_name'] : $match['donor_name'];

include 'includes/header.php';

// Status badge configuration
$status_badges = [
    'pending' => ['class' => 'bg-warning', 'text' => 'Pending'],
    'admin_review' => ['class' => 'bg-info', 'text' => 'Admin Review'],
    'completed' => ['class' => 'bg-success', 'text' => 'Completed'],
    'rejected' => ['class' => 'bg-danger', 'text' => 'Rejected']
];
$current_status = $match['status'] ?? 'pending';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Match Details #<?= $match_id ?></h2>
        <span class="badge <?= $status_badges[$current_status]['class'] ?> fs-6 p-2">
            <?= $status_badges[$current_status]['text'] ?>
        </span>
    </div>

    <!-- Admin information panel -->
    <?php if ($is_admin): ?>
    <div class="card border-primary shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Admin Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Match ID:</strong> #<?= $match_id ?></p>
                    <p class="mb-1"><strong>Created At:</strong> <?= date('M d, Y H:i', strtotime($match['created_at'])) ?></p>
                    <?php if ($match['admin_approved']): ?>
                        <p class="mb-1"><strong>Approved At:</strong> <?= date('M d, Y H:i', strtotime($match['matched_at'])) ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <?php if ($is_admin && $match['status'] === 'admin_review'): ?>
                        <div class="d-flex justify-content-end gap-2">
                            <form method="POST" action="admin/process_match.php">
                                <input type="hidden" name="match_id" value="<?= $match_id ?>">
                                <button type="submit" name="approve_match" class="btn btn-success">
                                    <i class="bi bi-check-lg me-1"></i> Approve Match
                                </button>
                            </form>
                            <button type="button" class="btn btn-danger" 
                                    data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="bi bi-x-lg me-1"></i> Reject Match
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Match information -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Match Information</h5>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6 mb-4 mb-md-0">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Donation Details</h6>
                        </div>
                        <div class="card-body">
                            <h5 class="mb-3"><?= htmlspecialchars($match['donation_title']) ?></h5>
                            <div class="text-center mb-3">
                                <img src="<?= htmlspecialchars($match['donation_image']) ?>" 
                                     class="img-fluid rounded" 
                                     alt="<?= htmlspecialchars($match['donation_title']) ?>"
                                     style="max-height: 200px;">
                            </div>
                            <p><?= nl2br(htmlspecialchars($match['donation_desc'])) ?></p>
                        </div>
                        <div class="card-footer bg-white">
                            <a href="donation_detail.php?id=<?= $match['donation_id'] ?>" 
                               class="btn btn-outline-primary w-100">
                                <i class="bi bi-box-seam me-1"></i> View Donation
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Request Details</h6>
                        </div>
                        <div class="card-body">
                            <h5 class="mb-3"><?= htmlspecialchars($match['request_title']) ?></h5>
                            <p><?= nl2br(htmlspecialchars($match['request_desc'])) ?></p>
                        </div>
                        <div class="card-footer bg-white">
                            <a href="request_detail.php?id=<?= $match['request_id'] ?>" 
                               class="btn btn-outline-primary w-100">
                                <i class="bi bi-cart me-1"></i> View Request
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4 mb-md-0">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Donor Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="/project/<?= htmlspecialchars(get_profile_image($match['donation_user_id'])) ?>" 
                                     class="rounded-circle me-3" 
                                     width="60" 
                                     height="60" 
                                     alt="Donor">
                                <div>
                                    <h5 class="mb-0"><?= htmlspecialchars($match['donor_name']) ?></h5>
                                    <p class="text-muted mb-0">Donor</p>
                                </div>
                            </div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <i class="bi bi-envelope me-2 text-primary"></i>
                                    <?= htmlspecialchars($match['donor_email']) ?>
                                </li>
                                <li class="list-group-item">
                                    <i class="bi bi-geo-alt me-2 text-primary"></i>
                                    <?= htmlspecialchars($match['donor_address']) ?>
                                </li>
                                <?php if ($is_admin): ?>
                                <!--li class="list-group-item">
                                    <i class="bi bi-person-badge me-2 text-primary"></i>
                                    <a href="admin/users.php?id=<?= $match['donation_user_id'] ?>" 
                                       class="text-decoration-none">
                                        View User Profile
                                    </a>
                                </li-->
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Requester Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="/project/<?= htmlspecialchars(get_profile_image($match['request_user_id'])) ?>" 
                                     class="rounded-circle me-3" 
                                     width="60" 
                                     height="60" 
                                     alt="Requester">
                                <div>
                                    <h5 class="mb-0"><?= htmlspecialchars($match['requester_name']) ?></h5>
                                    <p class="text-muted mb-0">Requester</p>
                                </div>
                            </div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <i class="bi bi-envelope me-2 text-primary"></i>
                                    <?= htmlspecialchars($match['requester_email']) ?>
                                </li>
                                <li class="list-group-item">
                                    <i class="bi bi-geo-alt me-2 text-primary"></i>
                                    <?= htmlspecialchars($match['requester_address']) ?>
                                </li>
                                <?php if ($is_admin): ?>
                                <!--li class="list-group-item">
                                    <i class="bi bi-person-badge me-2 text-primary"></i>
                                    <a href="admin/users.php?id=<?= $match['request_user_id'] ?>" 
                                       class="text-decoration-none">
                                        View User Profile
                                    </a>
                                </li-->
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Message from requester -->
    <?php if (!empty($match['message'])): ?>
    <div class="card border-info shadow-sm mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-chat-text me-2"></i>Match Request Message</h5>
        </div>
        <div class="card-body">
            <div class="border-start border-3 border-info ps-3 py-1">
                <?= nl2br(htmlspecialchars($match['message'])) ?>
            </div>
            <p class="text-muted mt-2 mb-0">
                <small>Sent by <?= htmlspecialchars($match['requester_name']) ?> on <?= date('M d, Y', strtotime($match['created_at'])) ?></small>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Admin notes -->
    <?php if (!empty($match['admin_notes'])): ?>
    <div class="card border-secondary shadow-sm">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="bi bi-journal-text me-2"></i>Admin Notes</h5>
        </div>
        <div class="card-body">
            <div class="border-start border-3 border-secondary ps-3 py-1">
                <?= nl2br(htmlspecialchars($match['admin_notes'])) ?>
            </div>
            <p class="text-muted mt-2 mb-0">
                <small>Last updated on <?= date('M d, Y', strtotime($match['updated_at'])) ?></small>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Action panel for donors -->
    <?php if ($match['status'] === 'pending' && $is_donor): ?>
    <div class="card border-warning shadow-sm mt-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Action Required</h5>
        </div>
        <div class="card-body">
            <p>Please review and confirm this match:</p>
            <div class="d-flex justify-content-between">
                <form method="POST" action="confirm_match.php">
                    <input type="hidden" name="match_id" value="<?= $match_id ?>">
                    <input type="hidden" name="action" value="confirm">
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-check-lg me-1"></i> Confirm Match
                    </button>
                </form>
                <form method="POST" action="confirm_match.php">
                    <input type="hidden" name="match_id" value="<?= $match_id ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="bi bi-x-lg me-1"></i> Reject Match
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Rejection Reason Modal for Admin -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Reject Match #<?= $match_id ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="admin/process_match.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Reason for rejection</label>
                        <textarea class="form-control" name="rejection_reason" rows="4" required 
                                  placeholder="Explain why this match is being rejected..."></textarea>
                        <div class="form-text">
                            This reason will be recorded and visible to administrators.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="match_id" value="<?= $match_id ?>">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="reject_match" class="btn btn-danger">
                        <i class="bi bi-x-lg me-1"></i> Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>