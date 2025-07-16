<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Obtain user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    redirect('login.php');
}

// Update Profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize_input($_POST['full_name']);
    $gender = sanitize_input($_POST['gender']);
    $address = sanitize_input($_POST['address']);

    // Process password updates
    $password_update = '';
    if (!empty($_POST['password'])) {
        if ($_POST['password'] !== $_POST['confirm_password']) {
            $error = "Passwords do not match.";
        } else {
            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $password_update = ", password_hash = :password_hash";
        }
    }

    // Process avatar updates
    $profile_image = $user['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $validation = validate_upload($_FILES['profile_image']);
        if ($validation === true) {
            $upload_dir = 'uploads/profile/';
            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $filename;
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_path)) {
                // Delete old avatar
                if ($profile_image && file_exists($profile_image) && strpos($profile_image, 'assets/images/default-profile') === false) {
                    unlink($profile_image);
                }
                $profile_image = $target_path;
            }
        } else {
            $error = $validation;
        }
    }

    if (empty($error)) {
        try {
            $sql = "UPDATE users SET full_name = :full_name, gender = :gender, 
                    address = :address, profile_image = :profile_image";

            if (!empty($password_update)) {
                $sql .= $password_update;
            }

            $sql .= " WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $params = [
                ':full_name' => $full_name,
                ':gender' => $gender,
                ':address' => $address,
                ':profile_image' => $profile_image,
                ':id' => $user_id
            ];

            if (!empty($password_update)) {
                $params[':password_hash'] = $password_hash;
            }

            $stmt->execute($params);

            // Update user information in the session
            $_SESSION['full_name'] = $full_name;
            $_SESSION['profile_image'] = $profile_image;

            // Retrieve user information to ensure the display of the latest data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $success = "Profile updated successfully!";
        } catch (PDOException $e) {
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <img src="<?= htmlspecialchars($user['profile_image'] ?: 'assets/images/default-profile.png') ?>" 
                         alt="Profile" class="rounded-circle mb-3" width="150" height="150"
                         style="object-fit: cover; border: 3px solid #dee2e6;">
                    <h4 class="mb-1"><?= htmlspecialchars($user['full_name']) ?></h4>
                    <p class="text-muted mb-2"><?= htmlspecialchars($user['email']) ?></p>
                    <p class="mb-0">
                        <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($user['status']) ?>
                        </span>
                        <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?>">
                            <?= ucfirst($user['role']) ?>
                        </span>
                    </p>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Account Information</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-medium">Member Since</span>
                            <span><?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-medium">Last Updated</span>
                            <span><?= date('M d, Y', strtotime($user['updated_at'])) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-medium">Status</span>
                            <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'secondary' ?>">
                                <?= ucfirst($user['status']) ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-medium">Role</span>
                            <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?>">
                                <?= ucfirst($user['role']) ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Edit Profile</h5>
                        <span class="text-muted small">ID: <?= $user['id'] ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $success ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="full_name" class="form-label fw-medium">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?= htmlspecialchars($user['full_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-medium">Email Address</label>
                                <input type="email" class="form-control" id="email" 
                                       value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="form-label fw-medium">Gender</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="male" 
                                               value="male" <?= $user['gender'] === 'male' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="male">Male</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="female" 
                                               value="female" <?= $user['gender'] === 'female' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="female">Female</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="other" 
                                               value="other" <?= $user['gender'] === 'other' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="other">Other</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="profile_image" class="form-label fw-medium">Profile Image</label>
                                <input class="form-control" type="file" id="profile_image" name="profile_image" accept="image/*">
                                <div class="form-text">Max size: 5MB, allowed types: JPG, PNG, GIF</div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="address" class="form-label fw-medium">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="address" name="address" rows="3" required><?= htmlspecialchars($user['address']) ?></textarea>
                        </div>
                        
                        <div class="card border-light mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Change Password</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label fw-medium">New Password</label>
                                        <input type="password" class="form-control" id="password" name="password">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="confirm_password" class="form-label fw-medium">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                </div>
                                <p class="text-muted mb-0">Leave blank to keep current password</p>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <button type="submit" class="btn btn-primary px-4 py-2">
                                <i class="bi bi-save me-1"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>