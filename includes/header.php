<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= defined('PAGE_TITLE') ? PAGE_TITLE : 'DonationHub - Share What You Have' ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/project/assets/css/style.css">

    <!-- Favicon -->
    <link rel="icon" href="/project/assets/images/favicon.ico" type="image/x-icon">

    <?php if (function_exists('page_specific_header')) page_specific_header(); ?>

    <style>
    .nav-item {
        margin-left: 1rem;
    }
    </style>
</head>
<body>
    <!-- Navigation bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand" href="/project">
                <i class="bi bi-heart-fill me-1"></i> DonationHub
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="/project">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="/project/donations.php">Donations</a></li>
                    <li class="nav-item"><a class="nav-link" href="/project/requests.php">Requests</a></li>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="/project/dashboard.php">Dashboard</a></li>
                        <!--li class="nav-item"><a class="nav-link" href="/project/create_donation.php">Donate Item</a></li>
                        <li class="nav-item"><a class="nav-link" href="/project/create_request.php">Request Item</a></li-->
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                Admin Panel
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/project/admin/users.php">User Management</a></li>
                                <li><a class="dropdown-item" href="/project/admin/categories.php">Category Management</a></li>
                                <li><a class="dropdown-item" href="/project/admin/donations.php">Donations</a></li>
                                <li><a class="dropdown-item" href="/project/admin/requests.php">Requests</a></li>
                                <li><a class="dropdown-item" href="/project/admin/matches.php">Matches</a></li>
                                <li><a class="dropdown-item" href="/project/admin/analytics.php">Analytics</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/project/includes/notifications.php'; ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <img src="/project/<?= htmlspecialchars($_SESSION['profile_image'] ?: 'assets/images/default-profile.jpg') ?>" alt="Profile" class="rounded-circle" width="30" height="30">
                                <?= htmlspecialchars($_SESSION['full_name']) ?>
                                <?php if ($unread_count = get_unread_notifications_count($_SESSION['user_id'])): ?>
                                <span class="position-absolute top-1 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $unread_count ?>
                                </span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                <li>
                                    <a class="dropdown-item position-relative" href="/project/notifications.php">
                                        Notifications
                                        <?php if ($unread_count): ?>
                                        <span class="position-absolute top-50 end-0 translate-middle-y badge rounded-pill bg-danger me-2">
                                            <?= $unread_count ?>
                                        </span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/project/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="/project/login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="/project/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main content area -->
    <main class="container py-2 mt-2">