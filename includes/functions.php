<?php
require_once 'config.php';

// Clean up input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Redirection function
function redirect($url) {
    header("Location: $url");
    exit;
}

// Generate error message
function display_error($message) {
    return '<div class="alert alert-danger">' . $message . '</div>';
}

// Generate success message
function display_success($message) {
    return '<div class="alert alert-success">' . $message . '</div>';
}

// Check if the file upload is valid
function validate_upload($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'File upload error: ' . $file['error'];
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return 'File size exceeds maximum allowed size (5MB)';
    }

    if (!in_array($file['type'], ALLOWED_TYPES)) {
        return 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
    }

    return true;
}

// Get user profile picture
function get_profile_image($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}
?>