<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
redirect_if_not_logged_in();

$error = '';
$success = '';

// Get classification
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title']);
    $category_id = (int)$_POST['category_id'];
    $description = sanitize_input($_POST['description']);
    
    // Process image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $validation = validate_upload($_FILES['image']);
        if ($validation === true) {
            $upload_dir = 'uploads/donations/';
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $filename;
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = $target_path;
            } else {
                $error = "Failed to upload image.";
            }
        } else {
            $error = $validation;
        }
    }

    // Validating Input
    if (empty($title) || empty($description) || empty($category_id)) {
        $error = "Please fill in all required fields.";
    } elseif (empty($image_path)) {
        $error = "Please upload an image of the item.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO donations (user_id, category_id, title, description, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $category_id, $title, $description, $image_path]);

            $success = "Your donation has been submitted successfully!";
        } catch (PDOException $e) {
            $error = "Error submitting donation: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Donate an Item</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label for="title" class="form-label">Item Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required>
                            <div class="form-text">Briefly describe the item you're donating</div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="" selected disabled>Select a category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="image" class="form-label">Item Image <span class="text-danger">*</span></label>
                                <input class="form-control" type="file" id="image" name="image" accept="image/*" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                            <div class="form-text">
                                Provide details about the item (condition, brand, why you're donating, etc.)
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg py-3">Submit Donation</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-4 text-center">
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Return to the dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>