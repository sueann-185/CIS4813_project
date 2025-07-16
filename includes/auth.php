<?php
session_start();

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return is_logged_in() && $_SESSION['user_role'] === 'admin';
}

function redirect_if_not_logged_in() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function redirect_if_not_admin() {
    redirect_if_not_logged_in();
    if (!is_admin()) {
        header('Location: dashboard.php');
        exit;
    }
}
?>