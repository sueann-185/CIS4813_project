<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
redirect_if_not_logged_in();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['match_id']) || !isset($_POST['action'])) {
    $_SESSION['error'] = "Invalid request method or missing parameters.";
    header('Location: dashboard.php');
    exit;
}

$match_id = (int)$_POST['match_id'];
$action = $_POST['action']; // 'confirm' or 'reject'
$user_id = $_SESSION['user_id'];

try {
    // Retrieve matching details and verify user permissions
    $stmt = $pdo->prepare("
        SELECT m.*, d.user_id AS donor_id, r.user_id AS requester_id
        FROM matches m
        JOIN donations d ON m.donation_id = d.id
        JOIN requests r ON m.request_id = r.id
        WHERE m.id = ? AND m.status = 'pending'
    ");
    $stmt->execute([$match_id]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$match) {
        throw new Exception("Match not found or already processed.");
    }

    // Verify if the current user is a donor
    if ($match['donation_user_id'] != $user_id && $match['donor_id'] != $user_id) {
        throw new Exception("You are not authorized to perform this action.");
    }

    $pdo->beginTransaction();

    if ($action === 'confirm') {
        // Confirm Match - Set the status to wait for administrator review
        $stmt = $pdo->prepare("UPDATE matches SET status = 'admin_review' WHERE id = ?");
        $stmt->execute([$match_id]);

        // Update donation and request status
        $stmt = $pdo->prepare("UPDATE donations SET status = 'admin_review' WHERE id = ?");
        $stmt->execute([$match['donation_id']]);

        $stmt = $pdo->prepare("UPDATE requests SET status = 'admin_review' WHERE id = ?");
        $stmt->execute([$match['request_id']]);

        // Notify the requester
        $notification = "The donor has confirmed your match request! It's now awaiting admin approval.";
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, message, related_id, type)
            VALUES (?, ?, ?, 'match_confirmed')
        ");
        $stmt->execute([$match['request_user_id'], $notification, $match_id]);
        
        $_SESSION['success'] = "Match confirmed! It's now awaiting admin approval.";
        
    } elseif ($action === 'reject') {
        // Reject Match - Restore Original State
        $stmt = $pdo->prepare("DELETE FROM matches WHERE id = ?");
        $stmt->execute([$match_id]);

        // Restore donation and request status
        $stmt = $pdo->prepare("UPDATE donations SET status = 'available' WHERE id = ?");
        $stmt->execute([$match['donation_id']]);

        $stmt = $pdo->prepare("UPDATE requests SET status = 'pending' WHERE id = ?");
        $stmt->execute([$match['request_id']]);

        // Notify the requester
        $notification = "The donor has rejected your match request. You can request other donations.";
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, message, related_id, type)
            VALUES (?, ?, ?, 'match_rejected')
        ");
        $stmt->execute([$match['request_user_id'], $notification, $match_id]);

        $_SESSION['success'] = "Match rejected successfully. The donation is now available again.";

    } else {
        throw new Exception("Invalid action specified.");
    }

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Error processing match: " . $e->getMessage();
}

// Redirects back to the matching details page or dashboard
if (isset($match_id)) {
    header('Location: match_detail.php?id=' . $match_id);
} else {
    header('Location: dashboard.php');
}
exit;
?>