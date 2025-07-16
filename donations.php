<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
include 'includes/header.php';

// Get donation list
$stmt = $pdo->prepare("
    SELECT d.*, u.full_name, u.profile_image, c.name AS category_name 
    FROM donations d
    JOIN users u ON d.user_id = u.id
    JOIN categories c ON d.category_id = c.id
    WHERE d.status = 'available'
    ORDER BY d.created_at DESC
");
$stmt->execute();
$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-5">
    <h2 class="mb-4">Available Donations</h2>

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

<?php include 'includes/footer.php'; ?>