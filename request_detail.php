<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: requests.php');
    exit;
}

$request_id = (int)$_GET['id'];

// Get request details
$stmt = $pdo->prepare("
    SELECT r.*, u.full_name, u.email, u.address, u.profile_image, c.name AS category_name 
    FROM requests r
    JOIN users u ON r.user_id = u.id
    JOIN categories c ON r.category_id = c.id
    WHERE r.id = ?
");
$stmt->execute([$request_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    header('Location: requests.php');
    exit;
}

// Obtain relevant requests
$stmt = $pdo->prepare("
    SELECT r.*, u.full_name, c.name AS category_name 
    FROM requests r
    JOIN users u ON r.user_id = u.id
    JOIN categories c ON r.category_id = c.id
    WHERE r.category_id = ? AND r.id != ? AND r.status = 'pending'
    ORDER BY RAND()
    LIMIT 3
");
$stmt->execute([$request['category_id'], $request_id]);
$related_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="requests.php">Requests</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($request['title']) ?></li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h1 class="mb-3"><?= htmlspecialchars($request['title']) ?></h1>

                    <div class="d-flex align-items-center mb-4">
                        <span class="badge bg-info me-2"><?= htmlspecialchars($request['category_name']) ?></span>
                        <span class="badge bg-<?= $request['status'] === 'pending' ? 'warning' : 'success' ?>">
                            <?= ucfirst($request['status']) ?>
                        </span>
                    </div>

                    <div class="mb-5">
                        <h4 class="mb-3">Description</h4>
                        <p class="lead"><?= nl2br(htmlspecialchars($request['description'])) ?></p>
                    </div>

                    <?php if (is_logged_in() && $request['status'] === 'pending' && $request['user_id'] !== $_SESSION['user_id']): ?>
                        <div class="card border-primary mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Offer This Item</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <input type="hidden" name="request_id" value="<?= $request_id ?>">

                                    <div class="mb-3">
                                        <label for="message" class="form-label">Message to Requester</label>
                                        <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                                        <div class="form-text">
                                            Explain how you can help and what item you can offer
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 py-2">
                                        Send Offer to Requester
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($related_requests)): ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Related Requests</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($related_requests as $related): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h6 class="card-title"><?= htmlspecialchars($related['title']) ?></h6>
                                            <p class="card-text small"><?= htmlspecialchars(mb_substr($related['description'], 0, 80)) ?>...</p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-info"><?= htmlspecialchars($related['category_name']) ?></span>
                                                <a href="request_detail.php?id=<?= $related['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    View
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Requester Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <img src="<?= htmlspecialchars($request['profile_image']) ?>" 
                             class="rounded-circle mb-3" 
                             width="120" 
                             height="120" 
                             alt="Requester">
                        <h4><?= htmlspecialchars($request['full_name']) ?></h4>
                        <p class="text-muted"><?= htmlspecialchars($request['email']) ?></p>

                        <div class="d-flex justify-content-center gap-2 mb-3">
                            <span class="badge bg-info">
                                <i class="bi bi-hand-thumbs-up-fill me-1"></i> 4.5
                            </span>
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle-fill me-1"></i> 8 Requests
                            </span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="d-flex align-items-center">
                            <i class="bi bi-geo-alt-fill me-2 text-primary"></i> Location
                        </h6>
                        <p class="ms-4"><?= htmlspecialchars($request['address']) ?></p>
                    </div>

                    <div class="mb-4">
                        <h6 class="d-flex align-items-center">
                            <i class="bi bi-clock-fill me-2 text-primary"></i> Posted
                        </h6>
                        <p class="ms-4"><?= date('F j, Y', strtotime($request['created_at'])) ?></p>
                    </div>

                    <?php if (is_logged_in() && $request['user_id'] !== $_SESSION['user_id']): ?>
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#contactModal">
                                <i class="bi bi-envelope me-1"></i> Contact Requester
                            </button>

                            <?php if ($request['status'] === 'pending'): ?>
                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reportModal">
                                    <i class="bi bi-flag me-1"></i> Report Request
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contact Modal -->
<div class="modal fade" id="contactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Contact Requester</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label class="form-label">Your Email</label>
                        <input type="email" class="form-control" value="<?= is_logged_in() ? htmlspecialchars($_SESSION['email']) : '' ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Report This Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label class="form-label">Reason for Reporting</label>
                        <select class="form-select" required>
                            <option value="" selected disabled>Select a reason</option>
                            <option value="spam">Spam or misleading</option>
                            <option value="prohibited">Prohibited item</option>
                            <option value="offensive">Offensive content</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Additional Details</label>
                        <textarea class="form-control" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger w-100">Submit Report</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>