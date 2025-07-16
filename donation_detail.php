<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: donations.php');
    exit;
}

$donation_id = (int)$_GET['id'];

// Get donation details
$stmt = $pdo->prepare("
    SELECT d.*, u.full_name, u.email, u.address, u.profile_image, c.name AS category_name 
    FROM donations d
    JOIN users u ON d.user_id = u.id
    JOIN categories c ON d.category_id = c.id
    WHERE d.id = ?
");
$stmt->execute([$donation_id]);
$donation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$donation) {
    header('Location: donations.php');
    exit;
}

// Obtain relevant donations
$stmt = $pdo->prepare("
    SELECT d.*, u.full_name, c.name AS category_name 
    FROM donations d
    JOIN users u ON d.user_id = u.id
    JOIN categories c ON d.category_id = c.id
    WHERE d.category_id = ? AND d.id != ? AND d.status = 'available'
    ORDER BY RAND()
    LIMIT 3
");
$stmt->execute([$donation['category_id'], $donation_id]);
$related_donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtain the matching status of the donation
$match_status = '';
if (is_logged_in()) {
    $stmt = $pdo->prepare("
        SELECT m.status 
        FROM matches m
        WHERE m.donation_id = ? AND (m.donation_user_id = ? OR m.request_user_id = ?)
        ORDER BY m.updated_at DESC
        LIMIT 1
    ");
    $stmt->execute([$donation_id, $_SESSION['user_id'], $_SESSION['user_id']]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($match) {
        $match_status = $match['status'];
    }
}

include 'includes/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="donations.php">Donations</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($donation['title']) ?></li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <img src="<?= htmlspecialchars($donation['image']) ?>" 
                             class="img-fluid rounded" 
                             alt="<?= htmlspecialchars($donation['title']) ?>"
                             style="max-height: 400px;">
                    </div>

                    <h1 class="mb-3"><?= htmlspecialchars($donation['title']) ?></h1>

                    <div class="d-flex align-items-center mb-4">
                        <span class="badge bg-primary me-2"><?= htmlspecialchars($donation['category_name']) ?></span>
                        <span class="badge bg-<?= 
                            $donation['status'] === 'available' ? 'success' : 
                            ($donation['status'] === 'pending' ? 'warning' : 'info')
                        ?>">
                            <?= ucfirst($donation['status']) ?>
                        </span>
                    </div>

                    <div class="mb-5">
                        <h4 class="mb-3">Description</h4>
                        <p class="lead"><?= nl2br(htmlspecialchars($donation['description'])) ?></p>
                    </div>

                    <?php if ($match_status === 'pending'): ?>
                        <div class="alert alert-warning mb-4">
                            <h5><i class="bi bi-hourglass-split me-2"></i> Match Pending</h5>
                            <p class="mb-0">
                                You have a pending match request for this donation. 
                                <a href="dashboard.php">Check your dashboard</a> for updates.
                            </p>
                        </div>
                    <?php elseif ($match_status === 'completed'): ?>
                        <div class="alert alert-success mb-4">
                            <h5><i class="bi bi-check-circle me-2"></i> Match Completed</h5>
                            <p class="mb-0">
                                This donation has been successfully matched with your request. 
                                <a href="match_detail.php?id=<?= $match['id'] ?>">View match details</a>
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if (is_logged_in() && $donation['status'] === 'available' && $donation['user_id'] !== $_SESSION['user_id']): ?>
                        <div class="card border-success mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Match This Donation</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="match_donation.php">
                                    <input type="hidden" name="donation_id" value="<?= $donation_id ?>">

                                    <div class="mb-3">
                                        <label class="form-label">Select a Request to Match</label>
                                        <select class="form-select" name="request_id" required>
                                            <option value="" selected disabled>Select your request</option>
                                            <?php
                                            // Retrieve pending requests from users
                                            $stmt = $pdo->prepare("
                                                SELECT id, title 
                                                FROM requests 
                                                WHERE user_id = ? AND status = 'pending'
                                            ");
                                            $stmt->execute([$_SESSION['user_id']]);
                                            $user_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                            foreach ($user_requests as $request):
                                            ?>
                                                <option value="<?= $request['id'] ?>"><?= htmlspecialchars($request['title']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">
                                            Select one of your pending requests to match with this donation
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="match_message" class="form-label">Message to Donor</label>
                                        <textarea class="form-control" id="match_message" name="message" rows="3" required></textarea>
                                        <div class="form-text">
                                            Explain why this donation matches your request
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-success w-100 py-2">
                                        <i class="bi bi-handshake me-1"></i> Request Match
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($related_donations)): ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Related Donations</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($related_donations as $related): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card h-100">
                                        <img src="<?= htmlspecialchars($related['image']) ?>" 
                                             class="card-img-top" 
                                             alt="<?= htmlspecialchars($related['title']) ?>"
                                             style="height: 150px; object-fit: cover;">
                                        <div class="card-body">
                                            <h6 class="card-title"><?= htmlspecialchars($related['title']) ?></h6>
                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                <span class="badge bg-primary"><?= htmlspecialchars($related['category_name']) ?></span>
                                                <a href="donation_detail.php?id=<?= $related['id'] ?>" class="btn btn-sm btn-outline-primary">
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
                    <h5 class="mb-0">Donor Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <img src="<?= htmlspecialchars($donation['profile_image']) ?>" 
                             class="rounded-circle mb-3" 
                             width="120" 
                             height="120" 
                             alt="Donor">
                        <h4><?= htmlspecialchars($donation['full_name']) ?></h4>
                        <p class="text-muted"><?= htmlspecialchars($donation['email']) ?></p>

                        <div class="d-flex justify-content-center gap-2 mb-3">
                            <span class="badge bg-primary">
                                <i class="bi bi-star-fill me-1"></i> 4.8
                            </span>
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle-fill me-1"></i> 12 Donations
                            </span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="d-flex align-items-center">
                            <i class="bi bi-geo-alt-fill me-2 text-primary"></i> Location
                        </h6>
                        <p class="ms-4"><?= htmlspecialchars($donation['address']) ?></p>
                    </div>

                    <div class="mb-4">
                        <h6 class="d-flex align-items-center">
                            <i class="bi bi-clock-fill me-2 text-primary"></i> Posted
                        </h6>
                        <p class="ms-4"><?= date('F j, Y', strtotime($donation['created_at'])) ?></p>
                    </div>

                    <div class="mb-4">
                        <h6 class="d-flex align-items-center">
                            <i class="bi bi-arrow-repeat me-2 text-primary"></i> Last Updated
                        </h6>
                        <p class="ms-4"><?= date('F j, Y', strtotime($donation['updated_at'])) ?></p>
                    </div>

                    <?php if (is_logged_in() && $donation['user_id'] !== $_SESSION['user_id']): ?>
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#contactModal">
                                <i class="bi bi-envelope me-1"></i> Contact Donor
                            </button>

                            <?php if ($donation['status'] === 'available'): ?>
                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reportModal">
                                    <i class="bi bi-flag me-1"></i> Report Item
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($donation['status'] === 'available'): ?>
                <div class="card border-info shadow-sm mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Item Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="bg-light p-3 rounded me-3">
                                <i class="bi bi-check-circle-fill fs-1 text-success"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Available</h5>
                                <p class="text-muted mb-0">This item is currently available for matching</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card border-secondary shadow-sm mt-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Item Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="bg-light p-3 rounded me-3">
                                <i class="bi bi-x-circle-fill fs-1 text-danger"></i>
                            </div>
                            <div>
                                <h5 class="mb-0"><?= ucfirst($donation['status']) ?></h5>
                                <p class="text-muted mb-0">This item is no longer available</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Contact Modal -->
<div class="modal fade" id="contactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Contact Donor</h5>
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
                <h5 class="modal-title">Report This Item</h5>
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
                            <option value="expired">Item no longer available</option>
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