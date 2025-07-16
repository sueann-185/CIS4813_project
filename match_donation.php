<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
redirect_if_not_logged_in();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $donation_id = (int)$_POST['donation_id'];
    $request_id = (int)$_POST['request_id'];
    $message = sanitize_input($_POST['message']);
    $user_id = $_SESSION['user_id'];

    // Validation Data
    $errors = [];
    
    // Check donation status
    $stmt = $pdo->prepare("SELECT user_id, status FROM donations WHERE id = ?");
    $stmt->execute([$donation_id]);
    $donation = $stmt->fetch();

    if (!$donation || $donation['status'] !== 'available') {
        $errors[] = "This donation is no longer available for matching.";
    }

    // Check request status
    $stmt = $pdo->prepare("SELECT user_id, status FROM requests WHERE id = ?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();

    if (!$request || $request['status'] !== 'pending' || $request['user_id'] !== $user_id) {
        $errors[] = "This request is not available for matching or does not belong to you.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Create matching records
            $stmt = $pdo->prepare("
                INSERT INTO matches (donation_id, request_id, donation_user_id, request_user_id, status, message)
                VALUES (?, ?, ?, ?, 'pending', ?)
            ");
            $stmt->execute([
                $donation_id,
                $request_id,
                $donation['user_id'],
                $user_id,
                $message
            ]);

            // Update donation and request status
            $stmt = $pdo->prepare("UPDATE donations SET status = 'pending' WHERE id = ?");
            $stmt->execute([$donation_id]);

            $stmt = $pdo->prepare("UPDATE requests SET status = 'pending' WHERE id = ?");
            $stmt->execute([$request_id]);

            // Notify donors
            $notification = "The requester has initiated a match request with you.";
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, message, related_id, type)
                VALUES (?, ?, ?, 'match_request')
            ");
            $stmt->execute([$donation['user_id'], $notification, $request_id]);

            $pdo->commit();

            $_SESSION['success'] = "Match request submitted successfully! It will be reviewed by the donor and administrator.";
            header('Location: donation_detail.php?id=' . $donation_id);
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error creating match: " . $e->getMessage();
            header('Location: donation_detail.php?id=' . $donation_id);
            exit;
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
        header('Location: donation_detail.php?id=' . $donation_id);
        exit;
    }
} else {
    header('Location: donations.php');
    exit;
}
?>