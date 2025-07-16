<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
redirect_if_not_admin();

$error = '';
$success = '';

// Handle match approval
if (isset($_POST['approve_match'])) {
    $match_id = (int)$_POST['match_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Get match details
        $stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
        $stmt->execute([$match_id]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$match) {
            throw new Exception("Match not found");
        }

        // Update match status to 'completed'
        $stmt = $pdo->prepare("UPDATE matches SET status = 'completed', admin_approved = 1 WHERE id = ?");
        $stmt->execute([$match_id]);

        // Update donation status to 'matched'
        $stmt = $pdo->prepare("UPDATE donations SET status = 'matched' WHERE id = ?");
        $stmt->execute([$match['donation_id']]);

        // Update request status to 'matched'
        $stmt = $pdo->prepare("UPDATE requests SET status = 'matched' WHERE id = ?");
        $stmt->execute([$match['request_id']]);
        
        $pdo->commit();
        $success = "Match approved successfully! The donation and request have been marked as matched.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error approving match: " . $e->getMessage();
    }
}

// Handle match rejection
if (isset($_POST['reject_match'])) {
    $match_id = (int)$_POST['match_id'];
    $reason = isset($_POST['rejection_reason']) ? sanitize_input($_POST['rejection_reason']) : 'No reason provided';

    try {
        $pdo->beginTransaction();

        // Get match details
        $stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
        $stmt->execute([$match_id]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$match) {
            throw new Exception("Match not found");
        }

        // Update match status to 'rejected' instead of deleting
        $stmt = $pdo->prepare("UPDATE matches SET status = 'rejected', admin_notes = ? WHERE id = ?");
        $stmt->execute([$reason, $match_id]);

        // Restore donation status based on its previous state
        $stmt = $pdo->prepare("UPDATE donations SET status = 'available' WHERE id = ?");
        $stmt->execute([$match['donation_id']]);

        // Restore request status based on its previous state
        $stmt = $pdo->prepare("UPDATE requests SET status = 'pending' WHERE id = ?");
        $stmt->execute([$match['request_id']]);

        $pdo->commit();
        $success = "Match rejected successfully. The donation and request have been made available again.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error rejecting match: " . $e->getMessage();
    }
}

// Get all matches with enhanced status filtering
$status_filter = '';
$params = [];

// Add status filter if selected
if (isset($_GET['status']) && in_array($_GET['status'], ['pending', 'admin_review', 'completed', 'rejected'])) {
    $status_filter = "WHERE m.status = ?";
    $params = [$_GET['status']];
}

$stmt = $pdo->prepare("
    SELECT m.*, 
           d.title AS donation_title, 
           r.title AS request_title,
           du.full_name AS donor_name,
           ru.full_name AS requester_name
    FROM matches m
    JOIN donations d ON m.donation_id = d.id
    JOIN requests r ON m.request_id = r.id
    JOIN users du ON m.donation_user_id = du.id
    JOIN users ru ON m.request_user_id = ru.id
    $status_filter
    ORDER BY m.created_at DESC
");
$stmt->execute($params);
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container py-5">
    <h2 class="mb-4">Match Management</h2>

    <!-- Status Filter Section -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Filter Matches</h5>
                <span class="badge bg-primary">
                    Total: <?= count($matches) ?>
                </span>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= isset($_GET['status']) && $_GET['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="admin_review" <?= isset($_GET['status']) && $_GET['status'] === 'admin_review' ? 'selected' : '' ?>>Admin Review</option>
                        <option value="completed" <?= isset($_GET['status']) && $_GET['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="rejected" <?= isset($_GET['status']) && $_GET['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-funnel me-1"></i> Apply Filter
                    </button>
                    <a href="matches.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Status Messages -->
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <!-- Match List -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Match List</h5>
            <div class="d-flex align-items-center">
                <span class="me-3 small text-muted">
                    Showing: <?= isset($_GET['status']) ? ucfirst($_GET['status']) : 'All' ?>
                </span>
                <input type="text" class="form-control form-control-sm me-2" placeholder="Search..." id="searchInput">
                <button class="btn btn-sm btn-outline-primary" id="searchButton">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>

        <div class="card-body">
            <?php if (empty($matches)): ?>
                <div class="alert alert-info text-center py-4">
                    <i class="bi bi-info-circle fs-1 text-primary mb-3"></i>
                    <h4>No matches found</h4>
                    <p class="mb-0">Try adjusting your filters or check back later.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="matchesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Donation</th>
                                <th>Request</th>
                                <th>Donor</th>
                                <th>Requester</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matches as $match): ?>
                                <tr>
                                    <td><?= $match['id'] ?></td>
                                    <td>
                                        <a href="../donation_detail.php?id=<?= $match['donation_id'] ?>" 
                                           class="text-decoration-none" 
                                           title="View donation details">
                                            <?= htmlspecialchars($match['donation_title']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="../request_detail.php?id=<?= $match['request_id'] ?>" 
                                           class="text-decoration-none" 
                                           title="View request details">
                                            <?= htmlspecialchars($match['request_title']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($match['donor_name']) ?></td>
                                    <td><?= htmlspecialchars($match['requester_name']) ?></td>
                                    <td>
                                        <?php
                                        $status_badge = [
                                            'pending' => 'warning',
                                            'admin_review' => 'info',
                                            'completed' => 'success',
                                            'rejected' => 'danger'
                                        ];
                                        ?>
                                        <span class="badge bg-<?= $status_badge[$match['status']] ?>">
                                            <?= ucfirst($match['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($match['created_at'])) ?></td>
                                    <td>
                                        <!-- Action buttons based on status -->
                                        <?php if ($match['status'] === 'admin_review' || $match['status'] === 'pending'): ?>
                                            <!-- Approve button -->
                                            <form method="POST" class="d-inline-block mb-1">
                                                <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                                <button type="submit" name="approve_match" class="btn btn-sm btn-success">
                                                    <i class="bi bi-check-lg"></i> Approve
                                                </button>
                                            </form>

                                            <!-- Reject button with modal trigger -->
                                            <button type="button" class="btn btn-sm btn-danger reject-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#rejectModal"
                                                    data-match-id="<?= $match['id'] ?>">
                                                <i class="bi bi-x-lg"></i> Reject
                                            </button>

                                            <a href="../match_detail.php?id=<?= $match['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary"
                                               title="View match details">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        <?php else: ?>
                                            <!-- View details for completed/rejected matches -->
                                            <a href="../match_detail.php?id=<?= $match['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary"
                                               title="View match details">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        <?php endif; ?>
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

<!-- Single Rejection Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Match</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="match_id" id="rejectMatchId" value="">
                    <div class="mb-3">
                        <label class="form-label">Reason for rejection</label>
                        <textarea class="form-control" name="rejection_reason" rows="3" required></textarea>
                        <div class="form-text">
                            This reason will be visible to the users involved.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="reject_match" class="btn btn-danger">
                        Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    const table = document.getElementById('matchesTable');

    if (table) {
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

        function searchMatches() {
            const searchTerm = searchInput.value.toLowerCase();

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;

                // Skip the first cell (ID) and last cell (Actions)
                for (let j = 1; j < cells.length - 1; j++) {
                    const cellText = cells[j].textContent || cells[j].innerText;
                    if (cellText.toLowerCase().includes(searchTerm)) {
                        found = true;
                        break;
                    }
                }

                row.style.display = found ? '' : 'none';
            }
        }

        searchButton.addEventListener('click', searchMatches);
        //searchInput.addEventListener('keyup', searchMatches);
    }

    // Reject button handling - use a single modal for all rejections
    const rejectButtons = document.querySelectorAll('.reject-btn');
    const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
    const rejectMatchIdInput = document.getElementById('rejectMatchId');

    rejectButtons.forEach(button => {
        button.addEventListener('click', function() {
            const matchId = this.getAttribute('data-match-id');
            rejectMatchIdInput.value = matchId;
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>