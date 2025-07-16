<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/notifications.php';
redirect_if_not_admin();

// Initialize variables
$error = '';
$success = '';
$redirect_url = 'matches.php'; // Default redirect to the matching management page

// Handle matching approval
if (isset($_POST['approve_match'])) {
    $match_id = (int)$_POST['match_id'];

    try {
        $pdo->beginTransaction();

        // Obtain matching details
        $stmt = $pdo->prepare("
            SELECT m.*, 
                   d.title AS donation_title, 
                   r.title AS request_title,
                   du.full_name AS donor_name, du.email AS donor_email,
                   ru.full_name AS requester_name, ru.email AS requester_email
            FROM matches m
            JOIN donations d ON m.donation_id = d.id
            JOIN requests r ON m.request_id = r.id
            JOIN users du ON m.donation_user_id = du.id
            JOIN users ru ON m.request_user_id = ru.id
            WHERE m.id = ?
        ");
        $stmt->execute([$match_id]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$match) {
            throw new Exception("Match not found or already processed.");
        }

        // Verify whether the matching status is approvable
        if (!in_array($match['status'], ['pending', 'admin_review'])) {
            throw new Exception("This match is not in a state that can be approved.");
        }

        // Update matching status
        $stmt = $pdo->prepare("
            UPDATE matches 
            SET status = 'completed', 
                admin_approved = 1, 
                matched_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$match_id]);

        // Update donation status
        $stmt = $pdo->prepare("
            UPDATE donations 
            SET status = 'matched' 
            WHERE id = ?
        ");
        $stmt->execute([$match['donation_id']]);

        // Update request status
        $stmt = $pdo->prepare("
            UPDATE requests 
            SET status = 'matched' 
            WHERE id = ?
        ");
        $stmt->execute([$match['request_id']]);

        // Send a notification to the donor
        $donor_message = "Your donation '{$match['donation_title']}' has been matched with request '{$match['request_title']}'!";
        // send_notification($match['donation_user_id'], $donor_message, 'match_completed', $match_id);
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, message, related_id, type)
            VALUES (?, ?, ?, 'match_completed')
        ");
        $stmt->execute([$match['donation_user_id'], $donor_message, $match_id]);

        // Send a notification to the requester
        $requester_message = "Your request '{$match['request_title']}' has been matched with donation '{$match['donation_title']}'!";
        // send_notification($match['request_user_id'], $requester_message, 'match_completed', $match_id);
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, message, related_id, type)
            VALUES (?, ?, ?, 'match_completed')
        ");
        $stmt->execute([$match['request_user_id'], $requester_message, $match_id]);
        
        $pdo->commit();
        
        $success = "Match #$match_id approved successfully! Both parties have been notified.";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error approving match: " . $e->getMessage();
    }
}

// Handling match rejection
if (isset($_POST['reject_match'])) {
    $match_id = (int)$_POST['match_id'];
    $rejection_reason = isset($_POST['rejection_reason']) ? sanitize_input($_POST['rejection_reason']) : 'No reason provided';

    try {
        $pdo->beginTransaction();

        // Get matching details
        $stmt = $pdo->prepare("
            SELECT m.*, 
                   d.title AS donation_title, 
                   r.title AS request_title,
                   du.full_name AS donor_name, du.email AS donor_email,
                   ru.full_name AS requester_name, ru.email AS requester_email
            FROM matches m
            JOIN donations d ON m.donation_id = d.id
            JOIN requests r ON m.request_id = r.id
            JOIN users du ON m.donation_user_id = du.id
            JOIN users ru ON m.request_user_id = ru.id
            WHERE m.id = ?
        ");
        $stmt->execute([$match_id]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$match) {
            throw new Exception("Match not found or already processed.");
        }

        // Update matching status
        $stmt = $pdo->prepare("
            UPDATE matches 
            SET status = 'rejected', 
                admin_notes = ? 
            WHERE id = ?
        ");
        $stmt->execute([$rejection_reason, $match_id]);

        // Restore donation status
        $stmt = $pdo->prepare("
            UPDATE donations 
            SET status = 'available' 
            WHERE id = ?
        ");
        $stmt->execute([$match['donation_id']]);

        // Restore request status
        $stmt = $pdo->prepare("
            UPDATE requests 
            SET status = 'pending' 
            WHERE id = ?
        ");
        $stmt->execute([$match['request_id']]);

        // Send a notification to the donor
        $donor_message = "Your donation '{$match['donation_title']}' match request has been rejected. Reason: $rejection_reason";
        // send_notification($match['donation_user_id'], $donor_message, 'match_rejected', $match_id);
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, message, related_id, type)
            VALUES (?, ?, ?, 'match_completed')
        ");
        $stmt->execute([$match['donation_user_id'], $donor_message, $match_id]);
        
        // Send a notification to the requester
        $requester_message = "Your request '{$match['request_title']}' match request has been rejected. Reason: $rejection_reason";
        // send_notification($match['request_user_id'], $requester_message, 'match_rejected', $match_id);
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, message, related_id, type)
            VALUES (?, ?, ?, 'match_completed')
        ");
        $stmt->execute([$match['request_user_id'], $requester_message, $match_id]);
        
        $pdo->commit();
        
        $success = "Match #$match_id rejected successfully. Both parties have been notified.";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error rejecting match: " . $e->getMessage();
    }
}

// Set redirect URL based on source settings
if (isset($_SERVER['HTTP_REFERER'])) {
    $redirect_url = $_SERVER['HTTP_REFERER'];
}

// Set session messages and redirect them
if ($error) {
    $_SESSION['error'] = $error;
} elseif ($success) {
    $_SESSION['success'] = $success;
}

header("Location: $redirect_url");
exit;
?>