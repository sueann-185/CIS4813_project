<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
redirect_if_not_admin();

$error = '';
$success = '';

// Handle donation status updates
if (isset($_POST['update_status'])) {
    $donation_id = (int)$_POST['donation_id'];
    $status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE donations SET status = ? WHERE id = ?");
        $stmt->execute([$status, $donation_id]);
        $success = "Donation status updated successfully!";
    } catch (PDOException $e) {
        $error = "Error updating donation: " . $e->getMessage();
    }
}

// Deal with donation deletion
if (isset($_POST['delete_donation'])) {
    $donation_id = (int)$_POST['donation_id'];

    try {
        // First, obtain the image path to facilitate the deletion of the file
        $stmt = $pdo->prepare("SELECT image FROM donations WHERE id = ?");
        $stmt->execute([$donation_id]);
        $image_path = $stmt->fetchColumn();

        // Delete donation
        $stmt = $pdo->prepare("DELETE FROM donations WHERE id = ?");
        $stmt->execute([$donation_id]);

        // Delete image file
        if ($image_path && file_exists($image_path)) {
            unlink($image_path);
        }

        $success = "Donation deleted successfully!";
    } catch (PDOException $e) {
        $error = "Error deleting donation: " . $e->getMessage();
    }
}

// Obtain all donations
$stmt = $pdo->query("
    SELECT d.*, u.full_name, u.email, c.name AS category_name 
    FROM donations d
    JOIN users u ON d.user_id = u.id
    JOIN categories c ON d.category_id = c.id
    ORDER BY d.created_at DESC
");
$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container py-5">
    <h2 class="mb-4">Donation Management</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Donations</h5>
            <div class="d-flex">
                <input type="text" class="form-control me-2" placeholder="Search donations..." id="searchInput">
                <button class="btn btn-outline-primary" id="searchButton">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($donations)): ?>
                <div class="alert alert-info">No donations found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="donationsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Item</th>
                                <th>Donor</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($donations as $donation): ?>
                                <tr>
                                    <td><?= $donation['id'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="/project/<?= htmlspecialchars($donation['image']) ?>" 
                                                 class="rounded me-2" 
                                                 width="50" 
                                                 height="50" 
                                                 alt="<?= htmlspecialchars($donation['title']) ?>"
                                                 style="object-fit: cover;">
                                            <div>
                                                <strong><?= htmlspecialchars($donation['title']) ?></strong>
                                                <div class="text-muted small text-truncate" style="max-width: 200px;">
                                                    <?= htmlspecialchars($donation['description']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="/project/<?= htmlspecialchars(get_profile_image($donation['user_id'])) ?>" 
                                                 class="rounded-circle me-2" 
                                                 width="30" 
                                                 height="30" 
                                                 alt="Donor">
                                            <div>
                                                <div><?= htmlspecialchars($donation['full_name']) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($donation['email']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?= htmlspecialchars($donation['category_name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $donation['status'] === 'available' ? 'success' : 
                                            ($donation['status'] === 'pending' ? 'warning' : 'info')
                                        ?>">
                                            <?= ucfirst($donation['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($donation['created_at'])) ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="donation_id" value="<?= $donation['id'] ?>">
                                                <select name="status" class="form-select form-select-sm" 
                                                        onchange="this.form.submit()">
                                                    <option value="available" <?= $donation['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                                                    <option value="pending" <?= $donation['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="matched" <?= $donation['status'] === 'matched' ? 'selected' : '' ?>>Matched</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                            
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="donation_id" value="<?= $donation['id'] ?>">
                                                <button type="submit" name="delete_donation" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    const table = document.getElementById('donationsTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    function searchDonations() {
        const searchTerm = searchInput.value.toLowerCase();

        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const cells = row.getElementsByTagName('td');
            let found = false;

            for (let j = 0; j < cells.length; j++) {
                const cellText = cells[j].textContent || cells[j].innerText;
                if (cellText.toLowerCase().indexOf(searchTerm) > -1) {
                    found = true;
                    break;
                }
            }

            row.style.display = found ? '' : 'none';
        }
    }

    searchButton.addEventListener('click', searchDonations);
    //searchInput.addEventListener('keyup', searchDonations);
});
</script>

<?php include '../includes/footer.php'; ?>