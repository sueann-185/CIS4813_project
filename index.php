<?php
// public/index.php
define('PAGE_TITLE', 'DonationHub - Share What You Have');
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get the latest donations
$stmt = $pdo->prepare("SELECT d.*, u.full_name, u.profile_image, c.name AS category_name 
                      FROM donations d
                      JOIN users u ON d.user_id = u.id
                      JOIN categories c ON d.category_id = c.id
                      WHERE d.status = 'available'
                      ORDER BY d.created_at DESC
                      LIMIT 6");
$stmt->execute();
$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get the latest requests
$stmt = $pdo->prepare("SELECT r.*, u.full_name, u.profile_image, c.name AS category_name 
                      FROM requests r
                      JOIN users u ON r.user_id = u.id
                      JOIN categories c ON r.category_id = c.id
                      WHERE r.status = 'pending'
                      ORDER BY r.created_at DESC
                      LIMIT 6");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-3">Share What You Have, Help Those in Need</h1>
                <p class="lead mb-4">Join our community to donate items you no longer need or request items that could help you or others.</p>
                <div class="d-flex flex-wrap gap-3">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="register.php" class="btn btn-light btn-lg px-4 py-2">Join Now</a>
                        <a href="login.php" class="btn btn-outline-light btn-lg px-4 py-2">Login</a>
                    <?php else: ?>
                        <a href="create_donation.php" class="btn btn-light btn-lg px-4 py-2">Donate an Item</a>
                        <a href="create_request.php" class="btn btn-outline-light btn-lg px-4 py-2">Request an Item</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img src="assets/images/hero-illustration.svg" alt="Donation Illustration" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row mb-5">
        <div class="col-md-8 mx-auto text-center">
            <h2 class="mb-3">How It Works</h2>
            <p class="lead text-muted">Our platform makes it easy to donate items you no longer need or request items that could help you.</p>
        </div>
    </div>

    <div class="row text-center">
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="icon-circle bg-primary text-white mb-4 mx-auto">
                        <i class="bi bi-person-plus fs-1"></i>
                    </div>
                    <h4 class="card-title">Create Account</h4>
                    <p class="card-text">Sign up as a donor or recipient to start using our platform.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="icon-circle bg-success text-white mb-4 mx-auto">
                        <i class="bi bi-box-seam fs-1"></i>
                    </div>
                    <h4 class="card-title">List Items</h4>
                    <p class="card-text">Post items you want to donate or request items you need.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="icon-circle bg-info text-white mb-4 mx-auto">
                        <i class="bi bi-arrow-repeat fs-1"></i>
                    </div>
                    <h4 class="card-title">Connect & Share</h4>
                    <p class="card-text">Find matches and arrange for pickup or delivery of items.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-light py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Recent Donations</h2>
            <a href="donations.php" class="btn btn-outline-primary">View All</a>
        </div>

        <div class="row">
            <?php if (empty($donations)): ?>
                <div class="col-12">
                    <div class="alert alert-info">No donations available at the moment.</div>
                </div>
            <?php else: ?>
                <?php foreach ($donations as $donation): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="position-relative">
                                <img src="<?= htmlspecialchars($donation['image']) ?>" 
                                     class="card-img-top" 
                                     alt="<?= htmlspecialchars($donation['title']) ?>"
                                     style="height: 200px; object-fit: cover;">
                                <span class="badge bg-primary position-absolute top-0 end-0 m-2">
                                    <?= htmlspecialchars($donation['category_name']) ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($donation['title']) ?></h5>
                                <p class="card-text text-truncate"><?= htmlspecialchars($donation['description']) ?></p>

                                <div class="d-flex align-items-center mt-3">
                                    <img src="<?= htmlspecialchars($donation['profile_image']) ?>" 
                                         class="rounded-circle me-2" 
                                         width="30" 
                                         height="30" 
                                         alt="Donor">
                                    <span><?= htmlspecialchars($donation['full_name']) ?></span>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="donation_detail.php?id=<?= $donation['id'] ?>" class="btn btn-outline-primary w-100">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Recent Requests</h2>
        <a href="requests.php" class="btn btn-outline-primary">View All</a>
    </div>

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
                            <p class="card-text text-truncate"><?= htmlspecialchars($request['description']) ?></p>

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
                                View Request
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php

require_once 'includes/footer.php';