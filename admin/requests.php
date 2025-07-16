<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
redirect_if_not_admin();

$error = '';
$success = '';

// Process request status updates
if (isset($_POST['update_status'])) {
    $request_id = (int)$_POST['request_id'];
    $status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE requests SET status = ? WHERE id = ?");
        $stmt->execute([$status, $request_id]);
        $success = "Request status updated successfully!";
    } catch (PDOException $e) {
        $error = "Error updating request: " . $e->getMessage();
    }
}

// Processing request for deletion
if (isset($_POST['delete_request'])) {
    $request_id = (int)$_POST['request_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM requests WHERE id = ?");
        $stmt->execute([$request_id]);
        $success = "Request deleted successfully!";
    } catch (PDOException $e) {
        $error = "Error deleting request: " . $e->getMessage();
    }
}

// Get all requests
$stmt = $pdo->query("
    SELECT r.*, u.full_name, u.email, c.name AS category_name 
    FROM requests r
    JOIN users u ON r.user_id = u.id
    JOIN categories c ON r.category_id = c.id
    ORDER BY r.created_at DESC
");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container py-5">
    <h2 class="mb-4">Request Management</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Item Requests</h5>
            <div class="d-flex">
                <input type="text" class="form-control me-2" placeholder="Search requests..." id="searchInput">
                <button class="btn btn-outline-primary" id="searchButton">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($requests)): ?>
                <div class="alert alert-info">No requests found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="requestsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Request</th>
                                <th>Requester</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?= $request['id'] ?></td>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($request['title']) ?></strong>
                                            <div class="text-muted small text-truncate" style="max-width: 250px;">
                                                <?= htmlspecialchars($request['description']) ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="/project/<?= htmlspecialchars(get_profile_image($request['user_id'])) ?>" 
                                                 class="rounded-circle me-2" 
                                                 width="30" 
                                                 height="30" 
                                                 alt="Requester">
                                            <div>
                                                <div><?= htmlspecialchars($request['full_name']) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($request['email']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= htmlspecialchars($request['category_name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $request['status'] === 'pending' ? 'warning' : 'success' ?>">
                                            <?= ucfirst($request['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($request['created_at'])) ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                                <select name="status" class="form-select form-select-sm" 
                                                        onchange="this.form.submit()">
                                                    <option value="pending" <?= $request['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="matched" <?= $request['status'] === 'matched' ? 'selected' : '' ?>>Matched</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                            
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                                <button type="submit" name="delete_request" class="btn btn-sm btn-danger">
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
    const table = document.getElementById('requestsTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    function searchRequests() {
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

    searchButton.addEventListener('click', searchRequests);
    //searchInput.addEventListener('keyup', searchRequests);
});
</script>

<?php include '../includes/footer.php'; ?>